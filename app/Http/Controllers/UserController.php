<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductionTask;
use App\Models\User;
use App\Services\Audit;
use App\Services\Codes;
use App\Services\Permissions;
use App\Support\Atelier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff account management (spec §3: /users).
 *
 * Ported from the Next.js reference implementation:
 * - GET/POST src/app/api/atelier/users/route.ts — the account directory
 *   (ALL users, active and inactive) and account creation (holders of
 *     users.manage.all may create any role; accounts without it may only
 *     create STAFF; unique email compared in its canonical
 *     lowercase/trimmed form; sequential USR-### codes);
 * - PATCH src/app/api/atelier/users/[id]/route.ts — profile edits, role
 *   changes, activation toggles and password resets, with the reference's
 *   safeguards ported verbatim:
 *     - users.manage.all holders may manage any account, other roles only
 *       STAFF accounts (target and resulting role);
 *     - nobody may deactivate their own account;
 *     - the last active MANAGEMENT account cannot be demoted or deactivated;
 *     - password resets are audited separately (USER_PASSWORD_RESET) and
 *       the password itself is never recorded.
 * Emails are not editable after creation (the reference update schema has
 * no email field).
 *
 * Deliberately does not extend a base Controller class, mirroring
 * LoginController / LocaleController.
 */
class UserController
{
    /**
     * GET /users (route perm users.view) — account directory with the
     * number of non-terminal tasks currently assigned to each account
     * (activeTaskCount). ALL users are listed, inactive ones included,
     * grouped by role (MANAGEMENT, ADMIN, STAFF — portable CASE, not a
     * MySQL-only FIELD()) and then by name, exactly like the reference
     * list endpoint with includeInactive defaulting to true.
     */
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['assignedTasks as activeTaskCount' => function (Builder $query): void {
                $query->whereNotIn('status', ProductionTask::TERMINAL_STATUSES);
            }])
            ->orderByRaw("CASE role WHEN 'MANAGEMENT' THEN 1 WHEN 'ADMIN' THEN 2 WHEN 'STAFF' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'me' => $request->user(),
        ]);
    }

    /**
     * POST /users (route perm users.manage.staff) — create an account. The
     * email is lowercased and trimmed BEFORE validation so the unique rule
     * compares the canonical form; passwords are hashed and never audited.
     * An ADMIN actor may only create STAFF accounts (reference 403). The
     * email race (UniqueConstraintViolationException) surfaces as an
     * inline validation error on the email field — the web equivalent of
     * the reference 409 response. isActive defaults to true (the reference
     * hard-codes it) and accepts an explicit override.
     */
    public function store(Request $request): RedirectResponse
    {
        $me = $request->user();

        // Canonical email BEFORE validation (reference z.preprocess).
        $email = trim((string) $request->input('email', ''));
        if ($email !== '') {
            $request->merge(['email' => strtolower($email)]);
        }

        $this->normalizeIsActiveCheckbox($request);

        $this->rejectBlank($request, [
            'name' => __('validation.required', ['attribute' => __('users.fullName')]),
            'role' => __('validation.required', ['attribute' => __('users.roleLabel')]),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(Atelier::ROLES)],
            'phone' => ['nullable', 'string', 'max:40'],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        // Without users.manage.all only STAFF accounts may be created
        // (both ADMIN and any future restricted role).
        if (! Permissions::allows($me, 'users.manage.all') && $data['role'] !== 'STAFF') {
            abort(403);
        }

        $isActive = $request->has('isActive') ? $request->boolean('isActive') : true;

        try {
            $user = User::query()->create([
                'code' => Codes::user(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['jobTitle'] ?? null,
                'password_hash' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => $isActive,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Email (or code) race — same inline error as the reference 409.
            throw ValidationException::withMessages([
                'email' => __('validation.unique', ['attribute' => __('users.email')]),
            ]);
        }

        Audit::log($me, 'USER_CREATED', 'USER', $user->id, [
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()
            ->back()
            ->with('success', __('users.createdToastTitle'));
    }

    /**
     * POST /users/{id}/update (route perm users.manage.staff) — edit an
     * account: profile fields, role, activation state and optional password
     * reset, ported from the PATCH handler of users/[id]/route.ts. Every
     * field is optional; present-null clears the nullable phone/jobTitle
     * columns. Password resets are hashed, audited separately as
     * USER_PASSWORD_RESET (no details) and excluded from the USER_UPDATED
     * changedFields list. The toast is picked by what changed:
     * resetToastTitle (password only), reactivated/deactivatedToastTitle
     * (isActive), updatedToastTitle otherwise.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $me = $request->user();
        $target = User::query()->findOrFail($id);

        $this->normalizeIsActiveCheckbox($request);

        $this->rejectBlank($request, [
            'name' => __('validation.required', ['attribute' => __('users.fullName')]),
            'role' => __('validation.required', ['attribute' => __('users.roleLabel')]),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', Rule::in(Atelier::ROLES)],
            'isActive' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Optional activation flag: has() + boolean() so a form that omits
        // the field leaves the stored value untouched while a toggle form
        // (hidden 0 / checkbox 1) flips it.
        $isActive = $request->has('isActive') ? $request->boolean('isActive') : null;

        // Without users.manage.all the manager is restricted to staff
        // accounts (both target and resulting role) — reference 403s.
        if (! Permissions::allows($me, 'users.manage.all')) {
            if ($target->role !== 'STAFF') {
                abort(403);
            }

            if (array_key_exists('role', $data) && $data['role'] !== 'STAFF') {
                abort(403);
            }
        }

        // You cannot deactivate yourself.
        if ($isActive === false && $target->id === $me->id) {
            return redirect()
                ->back()
                ->with('error', __('users.selfDeactivateError'));
        }

        // Protect the last active Management account from deactivation /
        // demotion (exact port of the reference guard).
        $demotes = array_key_exists('role', $data)
            && $data['role'] !== 'MANAGEMENT'
            && $target->role === 'MANAGEMENT';
        $deactivates = $isActive === false && $target->is_active;

        if (($demotes || $deactivates) && $target->role === 'MANAGEMENT') {
            $activeManagers = User::query()
                ->where('role', 'MANAGEMENT')
                ->where('is_active', true)
                ->count();

            if ($activeManagers <= 1) {
                return redirect()
                    ->back()
                    ->with('error', __('users.lastManagementError'));
            }
        }

        $update = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $data['name'];
        }

        if (array_key_exists('phone', $data)) {
            $update['phone'] = $data['phone'] ?? null;
        }

        if (array_key_exists('jobTitle', $data)) {
            $update['job_title'] = $data['jobTitle'] ?? null;
        }

        if (array_key_exists('role', $data)) {
            $update['role'] = $data['role'];
        }

        if ($isActive !== null) {
            $update['is_active'] = $isActive;
        }

        $passwordProvided = array_key_exists('password', $data) && $data['password'] !== null;

        if ($passwordProvided) {
            $update['password_hash'] = Hash::make($data['password']);
        }

        $target->fill($update);
        $changedColumns = array_keys($target->getDirty());

        if ($changedColumns !== []) {
            $target->save();
        }

        if ($passwordProvided) {
            // Separate audit entry; the password itself is never recorded.
            Audit::log($me, 'USER_PASSWORD_RESET', 'USER', $target->id);
        }

        $changedFields = $this->changedFields($changedColumns);

        if ($changedFields !== []) {
            Audit::log($me, 'USER_UPDATED', 'USER', $target->id, [
                'email' => $target->email,
                'changedFields' => $changedFields,
            ]);
        }

        if ($passwordProvided && $changedFields === []) {
            $toast = __('users.resetToastTitle');
        } elseif (in_array('is_active', $changedColumns, true)) {
            $toast = $target->is_active
                ? __('users.reactivatedToastTitle')
                : __('users.deactivatedToastTitle');
        } else {
            $toast = __('users.updatedToastTitle');
        }

        return redirect()
            ->back()
            ->with('success', $toast);
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
     * Pre-validation guard for the NOT NULL name/role columns: with
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
     * Audit field names use the Prisma camelCase spellings of the changed
     * user columns (jobTitle, isActive, …), mirroring the changedFields
     * array of the reference PATCH handler. The password hash is never
     * recorded.
     *
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function changedFields(array $columns): array
    {
        $fields = [];

        foreach ($columns as $column) {
            if ($column === 'password_hash') {
                continue;
            }

            $fields[] = match ($column) {
                'job_title' => 'jobTitle',
                'is_active' => 'isActive',
                default => $column, // name, phone, role
            };
        }

        return $fields;
    }
}
