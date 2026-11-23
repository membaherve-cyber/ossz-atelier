@extends('layouts.app')

@section('title', __('shell.view.users'))

@section('content')
                <div class="space-y-4" x-data="{ createOpen: {{ $errors->any() && old('formId') === 'user-create' ? 'true' : 'false' }} }">

    
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ __('users.title') }}</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('users.description') }}</p>
        </div>
        @if (\App\Services\Permissions::allows($me, 'users.manage.staff'))
                    <button type="button" @click="createOpen = true"
                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/></svg>
                {{ __('users.createAccount') }}
            </button>
            @endif
    </div>

    
    @if (count($users) === 0)
            <div class="flex flex-col items-center justify-center gap-2 py-12 px-4 text-center">
                <div class="rounded-full bg-muted p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 text-muted-foreground" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="font-medium">{{ __('users.emptyTitle') }}</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[1020px] text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <th class="px-4 py-3">{{ __('users.col.userId') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.user') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.role') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.jobTitle') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.phone') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.status') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('users.col.activeTasks') }}</th>
                        <th class="px-4 py-3">{{ __('users.col.created') }}</th>
                        @if (\App\Services\Permissions::allows($me, 'users.manage.staff'))
                                                    <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                                            @endif
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($users as $user)
                                                                    <tr class="{{ $user->is_active ? '' : 'opacity-60' }} transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md border bg-muted/50 px-2 py-1 font-mono text-xs font-semibold tracking-wide text-foreground">
                                    {{ $user->code }}
                                </span>
                            </td>
                            <td class="max-w-52 px-4 py-3">
                                <p class="truncate font-medium">{{ $user->name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-badges.role :role="$user->role" />
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $user->job_title ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ $user->phone ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm text-emerald-700">
                                        <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                        {{ __('users.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm text-red-600">
                                        <span class="size-1.5 rounded-full bg-red-500" aria-hidden="true"></span>
                                        {{ __('users.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums">
                                <span class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                                    {{ $user->activeTaskCount }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                {{ $user->created_at->locale(app()->getLocale())->translatedFormat('j M Y') }}
                            </td>
                            @if (\App\Services\Permissions::allows($me, 'users.manage.staff'))
                                                            <td class="px-4 py-3">
                                    <div class="flex items-center justify-end">
                                        @if (\App\Services\Permissions::allows($me, 'users.manage.all') || $user->role === 'STAFF')
                                            <div x-data="{ open: {{ $errors->any() && old('formId') === 'user-edit-'.$user->id ? 'true' : 'false' }} }">
                                                <button type="button" @click="open = true"
                                                        aria-label="{{ \App\Support\Trans::fill('users.editAria', ['name' => $user->name]) }}"
                                                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                                </button>
                                                <div x-show="open" style="display: none;"
                                                     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
                                                     aria-label="{{ __('users.editTitle') }}"
                                                     @keydown.escape.window="open = false">
                                                    <div class="fixed inset-0 bg-black/50" @click="open = false" aria-hidden="true"></div>
                                                    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                                                        <div class="relative w-full max-w-lg rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                                                            <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                                                                <div class="min-w-0">
                                                                    <h2 class="text-base font-semibold">{{ __('users.editTitle') }}</h2>
                                                                    <p class="mt-0.5 truncate text-sm text-muted-foreground">{{ $user->email }}</p>
                                                                </div>
                                                                <button type="button" @click="open = false"
                                                                        aria-label="{{ __('common.close') }}"
                                                                        class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                                </button>
                                                            </div>
                                                            <form method="POST" action="{{ route('users.update', $user->id) }}">
                                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                                                                <input type="hidden" name="formId" value="user-edit-{{ $user->id }}" />
                                                                <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4">
                                                                    <div class="grid gap-4 sm:grid-cols-2">
                                                                        <div class="sm:col-span-2">
                                                                            <label class="mb-1.5 block text-sm font-medium">{{ __('users.email') }}</label>
                                                                            <p class="rounded-md border bg-muted/40 px-3 py-2 text-sm text-muted-foreground">{{ $user->email }}</p>
                                                                        </div>
                                                                        <div>
                                                                            <label for="edit-name-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.fullName') }}</label>
                                                                            <input type="text" id="edit-name-{{ $user->id }}" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('name')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="edit-role-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.roleLabel') }}</label>
                                                                            <select id="edit-role-{{ $user->id }}" name="role"
                                                                                    class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                                                                @if (! \App\Services\Permissions::allows($me, 'users.manage.all'))
                                                                                    <option value="STAFF" {{ old('role', $user->role) === 'STAFF' ? 'selected' : '' }}>{{ __('labels.role.STAFF') }}</option>
                                                                                @else
                                                                                    <option value="MANAGEMENT" {{ old('role', $user->role) === 'MANAGEMENT' ? 'selected' : '' }}>{{ __('labels.role.MANAGEMENT') }}</option>
                                                                                    <option value="ADMIN" {{ old('role', $user->role) === 'ADMIN' ? 'selected' : '' }}>{{ __('labels.role.ADMIN') }}</option>
                                                                                    <option value="STAFF" {{ old('role', $user->role) === 'STAFF' ? 'selected' : '' }}>{{ __('labels.role.STAFF') }}</option>
                                                                                @endif
                                                                            </select>
                                                                            @error('role')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="edit-phone-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.phoneLabel') }}</label>
                                                                            <input type="text" id="edit-phone-{{ $user->id }}" name="phone" value="{{ old('phone', $user->phone ?? '') }}" maxlength="40"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('phone')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div>
                                                                            <label for="edit-job-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.jobTitleLabel') }}</label>
                                                                            <input type="text" id="edit-job-{{ $user->id }}" name="jobTitle" value="{{ old('jobTitle', $user->job_title ?? '') }}" maxlength="120" placeholder="{{ __('users.jobTitlePlaceholder') }}"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                            @error('jobTitle')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <span class="mb-1.5 block text-sm font-medium">{{ __('users.isActiveLabel') }}</span>
                                                                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                                                                <input type="hidden" name="isActive" value="0" />
                                                                                <input type="checkbox" name="isActive" value="1" {{ old('isActive', $user->is_active) ? 'checked' : '' }}
                                                                                       class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                                                                {{ __('users.active') }}
                                                                            </label>
                                                                        </div>
                                                                        <div class="sm:col-span-2 border-t border-border pt-4">
                                                                            <label for="edit-password-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.newPasswordOptional') }}</label>
                                                                            <div class="relative" x-data="{ show: false }">
                                                                                <input type="password" :type="show ? 'text' : 'password'" id="edit-password-{{ $user->id }}" name="password" value="" autocomplete="new-password" placeholder="{{ __('users.passwordPlaceholder') }}"
                                                                                       class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 pr-10 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                                <button type="button" @click="show = !show"
                                                                                        :aria-label="show ? '{{ __('common.hidePassword') }}' : '{{ __('common.showPassword') }}'"
                                                                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="! show" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="show" style="display: none" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                                                                                </button>
                                                                            </div>
                                                                            <p class="mt-1 text-xs text-muted-foreground">{{ __('users.passwordOptionalHint') }}</p>
                                                                            @error('password')
                                                                                <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>
                                                                        <div class="sm:col-span-2">
                                                                            <label for="edit-password-confirm-{{ $user->id }}" class="mb-1.5 block text-sm font-medium">{{ __('users.confirmNewPassword') }}</label>
                                                                            <input type="password" id="edit-password-confirm-{{ $user->id }}" name="password_confirmation" value="" autocomplete="new-password"
                                                                                   class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                                                                    <button type="button" @click="open = false"
                                                                            class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                        {{ __('common.cancel') }}
                                                                    </button>
                                                                    <button type="submit"
                                                                            class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                                        {{ __('common.saveChanges') }}
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                                                            </div>
                                </td>
                                                    @endif
                    </tr>
                                    @endforeach
                                    </tbody>
            </table>
        </div>
        @endif
    
    
            <div x-show="createOpen" style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
             aria-label="{{ __('users.createAccount') }}"
             @keydown.escape.window="createOpen = false">
            <div class="fixed inset-0 bg-black/50" @click="createOpen = false" aria-hidden="true"></div>
            <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
                <div class="relative w-full max-w-lg rounded-lg border border-border bg-card text-card-foreground shadow-xl">
                    <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">{{ __('users.createAccount') }}</h2>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ __('users.createDescription') }}</p>
                        </div>
                        <button type="button" @click="createOpen = false"
                                aria-label="{{ __('common.close') }}"
                                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('users.store') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" autocomplete="off">                        <input type="hidden" name="formId" value="user-create" />
                        <div class="max-h-[calc(85vh-11rem)] overflow-y-auto px-5 py-4"
                             x-data="{ role: '{{ old('role', 'STAFF') }}' }">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="new-name" class="mb-1.5 block text-sm font-medium">{{ __('users.fullName') }}</label>
                                    <input type="text" id="new-name" name="name" value="{{ old('name') }}" maxlength="120" placeholder="{{ __('users.namePlaceholder') }}" required
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('name')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new-email" class="mb-1.5 block text-sm font-medium">{{ __('users.email') }}</label>
                                    <input type="email" id="new-email" name="email" value="{{ old('email') }}" placeholder="name@osszcollection.com" required
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('email')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new-password" class="mb-1.5 block text-sm font-medium">{{ __('users.password') }}</label>
                                    <div class="relative" x-data="{ show: false }">
                                        <input type="password" :type="show ? 'text' : 'password'" id="new-password" name="password" value="" autocomplete="new-password" placeholder="{{ __('users.passwordPlaceholder') }}" required
                                               class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 pr-10 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                        <button type="button" @click="show = !show"
                                                :aria-label="show ? '{{ __('common.hidePassword') }}' : '{{ __('common.showPassword') }}'"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="! show" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="show" style="display: none" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new-password-confirm" class="mb-1.5 block text-sm font-medium">{{ __('users.confirmPassword') }}</label>
                                    <input type="password" id="new-password-confirm" name="password_confirmation" value="" autocomplete="new-password" required
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                </div>
                                <div>
                                    <label for="new-role" class="mb-1.5 block text-sm font-medium">{{ __('users.roleRequired') }}</label>
                                    <select id="new-role" name="role" x-model="role"
                                            class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                        @if (! \App\Services\Permissions::allows($me, 'users.manage.all'))
                                            <option value="STAFF" {{ old('role', 'STAFF') === 'STAFF' ? 'selected' : '' }}>{{ __('labels.role.STAFF') }}</option>
                                        @else
                                            <option value="MANAGEMENT" {{ old('role', 'STAFF') === 'MANAGEMENT' ? 'selected' : '' }}>{{ __('labels.role.MANAGEMENT') }}</option>
                                            <option value="ADMIN" {{ old('role', 'STAFF') === 'ADMIN' ? 'selected' : '' }}>{{ __('labels.role.ADMIN') }}</option>
                                            <option value="STAFF" {{ old('role', 'STAFF') === 'STAFF' ? 'selected' : '' }}>{{ __('labels.role.STAFF') }}</option>
                                        @endif
                                    </select>
                                    @error('role')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new-phone" class="mb-1.5 block text-sm font-medium">{{ __('users.phoneLabel') }}</label>
                                    <input type="text" id="new-phone" name="phone" value="{{ old('phone') }}" maxlength="40"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('phone')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new-job" class="mb-1.5 block text-sm font-medium">{{ __('users.jobTitleLabel') }}</label>
                                    <input type="text" id="new-job" name="jobTitle" value="{{ old('jobTitle') }}" maxlength="120" placeholder="{{ __('users.jobTitlePlaceholder') }}"
                                           class="h-9 w-full rounded-md border border-input bg-card px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50" />
                                    @error('jobTitle')
                                        <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="mb-1.5 block text-sm font-medium">{{ __('users.isActiveLabel') }}</span>
                                    <label class="flex min-h-11 items-center gap-2 text-sm">
                                        <input type="hidden" name="isActive" value="0" />
                                        <input type="checkbox" name="isActive" value="1" {{ old('isActive', true) ? 'checked' : '' }}
                                               class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                        {{ __('users.active') }}
                                    </label>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="flex gap-2 rounded-lg border bg-muted/40 p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                                        <p class="text-xs text-muted-foreground">
                                            <span x-show="role === 'MANAGEMENT'" style="display: none">{{ __('users.roleHint.management') }}</span>
                                            <span x-show="role === 'ADMIN'" style="display: none">{{ __('users.roleHint.admin') }}</span>
                                            <span x-show="role === 'STAFF'">{{ __('users.roleHint.staff') }} {{ __('users.roleHint.staffExtra') }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 rounded-b-lg border-t bg-muted/30 px-5 py-3">
                            <button type="button" @click="createOpen = false"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-input bg-card px-4 text-sm font-medium shadow-sm transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ __('common.cancel') }}
                            </button>
                            <button type="submit"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ __('users.createAccount') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
            @endsection
