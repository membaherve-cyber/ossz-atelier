<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductionStage;
use App\Services\Audit;
use App\Support\Atelier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Configurable production checklist stages (spec §3: /stages).
 *
 * Ported from the Next.js reference implementation:
 * - GET/POST src/app/api/atelier/stages/route.ts — the stage list (sorted
 *   by sort order, then name) and stage creation with slug-derived unique
 *   codes (the -2/-3 suffix loop) and the normalizeAppliesTo / slugify
 *   helpers;
 * - PATCH src/app/api/atelier/stages/[id]/route.ts — partial updates.
 * Stages are never deleted (immutable task history references them), so
 * deactivation is the only removal and the stage CODE never changes
 * (stages.editDescription). appliesTo accepts "ALL", a comma-separated
 * category string or a checkbox array (appliesTo[]) and always normalizes
 * to "ALL" or a unique list of valid uppercase categories.
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class StageController
{
    /**
     * GET /stages (route perm production.stages.configure) — the full
     * stage configuration ordered by sort order, then name (exact port of
     * the reference list endpoint).
     */
    public function index(): View
    {
        $stages = ProductionStage::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('stages.index', ['stages' => $stages]);
    }

    /**
     * POST /stages (route perm production.stages.configure) — create a
     * checklist stage. The code is derived from the slugified name
     * (non-alphanumerics collapse to dashes; a name without a single
     * letter or digit is rejected with stages.invalidNameTitle) and made
     * unique by appending -2, -3, ... on clash. Weight is a decimal
     * between 0.1 and 10 (default 1); an absent/blank sort order defaults
     * to max(sort_order) + 1 (view contract; the reference API default was
     * 0); isActive defaults to true.
     */
    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();

        $this->normalizeIsActiveCheckbox($request);

        $this->rejectBlank($request, [
            'name' => __('validation.required', ['attribute' => __('stages.nameLabel')]),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('production_stages', 'name')],
            'weight' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $base = $this->slugify($data['name']);

        if ($base === '') {
            throw ValidationException::withMessages([
                'name' => __('stages.invalidNameTitle'),
            ]);
        }

        // Unique code from the slugified name (append -2, -3, ... on
        // clash) — exact port of the reference suffix loop.
        $taken = ProductionStage::query()->pluck('code')->all();
        $code = $base;
        $suffix = 2;

        while (in_array($code, $taken, true)) {
            $code = $base.'-'.($suffix++);
        }

        $weight = ($data['weight'] ?? null) === null ? 1.0 : (float) $data['weight'];

        // View contract: absent/blank sort order defaults to max + 1.
        $sortOrder = ($data['sortOrder'] ?? null) === null
            ? (int) ProductionStage::query()->max('sort_order') + 1
            : (int) $data['sortOrder'];

        $appliesTo = $this->normalizeAppliesTo($request->input('appliesTo'));
        $isActive = $request->has('isActive') ? $request->boolean('isActive') : true;

        $stage = ProductionStage::query()->create([
            'code' => $code,
            'name' => $data['name'],
            'sort_order' => $sortOrder,
            'weight' => $weight,
            'applies_to' => $appliesTo,
            'is_active' => $isActive,
        ]);

        Audit::log($me, 'STAGE_CREATED', 'STAGE', $stage->id, [
            'name' => $stage->name,
            'appliesTo' => $stage->applies_to,
        ]);

        return redirect()
            ->back()
            ->with('success', __('stages.createdToastTitle'));
    }

    /**
     * POST /stages/{id} (route perm production.stages.configure) — update
     * a stage (rename, re-weight, re-order, re-scope, activate/deactivate),
     * ported from the PATCH handler of stages/[id]/route.ts. Every field
     * is optional; the code NEVER changes; present-but-blank weight /
     * sortOrder values are treated as absent (the columns are NOT NULL —
     * no silent 0 writes); a renaming must still yield a usable slug. The
     * audit entry records the changed fields under their Prisma camelCase
     * names with their new values.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $stage = ProductionStage::query()->findOrFail($id);

        $this->normalizeIsActiveCheckbox($request);

        $this->rejectBlank($request, [
            'name' => __('validation.required', ['attribute' => __('stages.nameLabel')]),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:80', Rule::unique('production_stages', 'name')->ignore($stage->id)],
            'weight' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        // A renaming must still yield a usable slug (reference PATCH).
        if (array_key_exists('name', $data) && $data['name'] !== $stage->name && $this->slugify($data['name']) === '') {
            throw ValidationException::withMessages([
                'name' => __('stages.invalidNameTitle'),
            ]);
        }

        $update = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $data['name'];
        }

        if (array_key_exists('weight', $data) && $data['weight'] !== null) {
            $update['weight'] = (float) $data['weight'];
        }

        if (array_key_exists('sortOrder', $data) && $data['sortOrder'] !== null) {
            $update['sort_order'] = (int) $data['sortOrder'];
        }

        if ($request->has('isActive')) {
            $update['is_active'] = $request->boolean('isActive');
        }

        if ($request->has('appliesTo')) {
            $update['applies_to'] = $this->normalizeAppliesTo($request->input('appliesTo'));
        }

        $stage->fill($update);
        $changedColumns = array_keys($stage->getDirty());

        if ($changedColumns !== []) {
            $stage->save();
        }

        Audit::log($me, 'STAGE_UPDATED', 'STAGE', $stage->id, [
            'name' => $stage->name,
            'changed' => $this->changedMap($stage, $changedColumns),
        ]);

        return redirect()
            ->back()
            ->with('success', __('stages.updatedToastTitle'));
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    /**
     * Bare HTML checkboxes post "on"; normalized to "1" before validation
     * so the boolean rule accepts them and boolean() reads them back.
     */
    private function normalizeIsActiveCheckbox(Request $request): void
    {
        if ($request->input('isActive') === 'on') {
            $request->merge(['isActive' => '1']);
        }
    }

    /**
     * Pre-validation guard for the NOT NULL name column: with
     * ConvertEmptyStringsToNull active, a submitted-but-blank input arrives
     * as null, which the nullable update rules would swallow before hitting
     * a NOT NULL database column. Blank submissions are rejected with the
     * framework validation.required line; absent fields stay untouched.
     *
     * @param  array<string, string>  $fields
     */
    private function rejectBlank(Request $request, array $fields): void
    {
        $messages = [];

        foreach ($fields as $field => $message) {
            if ($request->has($field) && blank($request->input($field))) {
                $messages[$field] = $message;
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * Lowercase slug port: non-alphanumerics collapse to dashes, edge
     * dashes are trimmed (slugify() in stages/route.ts).
     */
    private function slugify(string $name): string
    {
        $slug = strtolower($name);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Normalize an appliesTo payload: null, "" and "ALL" mean every
     * category; a comma-separated string ("FULL,CUTTING") or a checkbox
     * array (appliesTo[]) is uppercased, de-duplicated and filtered to the
     * valid task categories; an empty result or all six categories at once
     * collapse back to "ALL" (normalizeAppliesTo() in stages/route.ts).
     *
     * @param  string|list<string>|null  $value
     */
    private function normalizeAppliesTo(string|array|null $value): string
    {
        if ($value === null || $value === '') {
            return Atelier::APPLIES_TO_ALL;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '' || strtoupper($value) === Atelier::APPLIES_TO_ALL) {
                return Atelier::APPLIES_TO_ALL;
            }

            $value = explode(',', $value);
        }

        $categories = array_values(array_unique(array_map(
            static fn (string $category): string => strtoupper(trim($category)),
            $value
        )));

        $valid = array_values(array_filter(
            $categories,
            static fn (string $category): bool => in_array($category, Atelier::TASK_CATEGORIES, true)
        ));

        if ($valid === [] || count($valid) === count(Atelier::TASK_CATEGORIES)) {
            return Atelier::APPLIES_TO_ALL;
        }

        return implode(',', $valid);
    }

    /**
     * Audit "changed" map: the changed columns under their Prisma
     * (camelCase) field names with their new values, in the reference's
     * field order (name, weight, sortOrder, isActive, appliesTo only).
     *
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function changedMap(ProductionStage $stage, array $columns): array
    {
        $fields = [
            'name' => 'name',
            'weight' => 'weight',
            'sort_order' => 'sortOrder',
            'is_active' => 'isActive',
            'applies_to' => 'appliesTo',
        ];

        $changed = [];

        foreach ($fields as $column => $field) {
            if (in_array($column, $columns, true)) {
                $changed[$field] = $stage->{$column};
            }
        }

        return $changed;
    }
}
