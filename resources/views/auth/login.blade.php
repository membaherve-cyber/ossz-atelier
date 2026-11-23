<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('login.title') }} — OSSZ ATELIER</title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/alpine.min.js" defer></script>
    <style>[x-cloak]{display:none !important;}</style>
</head>
<body class="bg-background text-foreground antialiased">

<div class="relative min-h-screen flex flex-col lg:flex-row bg-background">

    {{-- Brand hero --}}
    <div class="relative hidden lg:flex lg:w-[45%] flex-col justify-between p-10 text-sidebar-foreground bg-[url('/img/atelier-hero.png')] bg-cover bg-center">
        <div class="absolute inset-0 bg-black/75" aria-hidden="true"></div>
        <div class="relative">
            <img src="/img/ossz-logo.png" alt="{{ __('login.logoAlt') }}" class="h-14 w-auto rounded-lg ring-1 ring-white/20">
        </div>
        <div class="relative">
            <h1 class="font-display text-4xl font-bold leading-tight max-w-md">{{ __('login.heroTitle') }}</h1>
            <p class="mt-4 text-stone-300 max-w-md text-sm leading-relaxed">{{ __('login.heroBody') }}</p>
        </div>
        <p class="relative text-xs text-stone-400">{{ __('login.heroFooter') }}</p>
    </div>

    {{-- Language pills (title attribute intentionally shows the OTHER locale's
         label, exactly like the captured live login page) --}}
    <div role="group" aria-label="{{ __('shell.languageAria') }}" title="{{ __('shell.languageTitle', [], app()->getLocale() === 'fr' ? 'en' : 'fr') }}" class="absolute right-4 top-4 z-10 flex items-center gap-0.5 rounded-full border bg-background p-0.5 select-none shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden sm:block size-3.5 ml-1 text-muted-foreground" aria-hidden="true"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
        <a href="{{ route('locale.switch', 'en') }}" class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide transition-colors {{ app()->getLocale() === 'en' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
        <a href="{{ route('locale.switch', 'fr') }}" class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide transition-colors {{ app()->getLocale() === 'fr' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}" @if (app()->getLocale() === 'fr') aria-current="true" @endif>FR</a>
    </div>

    {{-- Sign-in form --}}
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            {{-- Mobile brand --}}
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <img src="/img/ossz-logo.png" alt="{{ __('login.logoAlt') }}" class="h-11 w-auto rounded-lg ring-1 ring-black/10">
                <div>
                    <p class="font-display text-xl font-bold leading-none">OSSZ ATELIER</p>
                    <p class="text-[10px] tracking-[0.25em] text-muted-foreground mt-1">{{ __('login.brandTag') }}</p>
                </div>
            </div>

            <h2 class="font-display text-2xl font-bold">{{ __('login.title') }}</h2>
            <p class="text-sm text-muted-foreground mt-1 mb-6">{{ __('login.subtitle') }}</p>

            <form method="POST" action="{{ route('login.attempt') }}" x-data="{ loading: false, show: false }" @submit="loading = true" novalidate class="space-y-4">
                @csrf

                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium leading-none">{{ __('login.email') }}</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                               placeholder="{{ __('login.emailPlaceholder') }}"
                               class="flex h-9 w-full rounded-md border border-input bg-transparent pl-9 pr-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                    </div>
                    @error('email')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium leading-none">{{ __('login.password') }}</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input id="password" x-bind:type="show ? 'text' : 'password'" name="password" autocomplete="current-password" required
                               placeholder="{{ __('login.passwordPlaceholder') }}"
                               class="flex h-9 w-full rounded-md border border-input bg-transparent pl-9 pr-10 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <button type="button" @click="show = !show" x-bind:aria-label="show ? '{{ __('login.hidePassword') }}' : '{{ __('login.showPassword') }}'" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="!show" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" x-show="show" x-cloak aria-hidden="true"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Flash / validation error region (empty on the captured
                     successful page render) --}}
                @if (session('error') || $errors->any())
                    <div class="rounded-md border border-red-300 bg-red-50 px-3 py-2 space-y-1" role="alert">
                        @if (session('error'))
                            <p class="text-sm text-red-800">{{ session('error') }}</p>
                        @endif
                        @foreach ($errors->all() as $error)
                            <p class="text-sm text-red-800">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <button type="submit" x-bind:disabled="loading" class="w-full inline-flex items-center justify-center h-9 px-4 rounded-md bg-primary text-primary-foreground text-sm font-medium shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-60 disabled:pointer-events-none">
                    <span x-show="!loading">{{ __('login.submit') }}</span>
                    <span x-show="loading" x-cloak>{{ __('login.submitting') }}</span>
                </button>
            </form>

            {{-- System accounts (emails only — passwords are provided by Management) --}}
            <div class="mt-8 rounded-lg border bg-muted/40 p-4">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">{{ __('login.demoTitle') }}</p>
                <ul class="space-y-1.5 text-xs">
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-muted-foreground">{{ __('login.demoManagement') }}</span>
                        <span class="font-mono">management@osszcollection.com</span>
                    </li>
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-muted-foreground">{{ __('login.demoAdmin') }}</span>
                        <span class="font-mono">admin@osszcollection.com</span>
                    </li>
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-muted-foreground">{{ __('login.demoStaff') }}</span>
                        <span class="font-mono">info@osszcollection.com</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
</body>
</html>
