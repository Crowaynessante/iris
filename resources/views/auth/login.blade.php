<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IRIS</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
<script>window.tailwind = window.tailwind || {}; window.tailwind.config = { darkMode: 'class', theme: { extend: { colors: { clsu: { 50:'#effcf3',100:'#dff8e6',200:'#bff0cc',300:'#8fe0a6',400:'#57c979',500:'#2aa957',600:'#1c8a45',700:'#176e3a',800:'#155832',900:'#10472b' } } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="auth-page auth-pattern">
    <main class="mx-auto flex min-h-screen items-center justify-center px-4 py-6 sm:px-6 sm:py-10">
        <section class="auth-card flex flex-col overflow-hidden rounded-2xl shadow-xl md:flex-row">
            <aside class="auth-side flex w-full shrink-0 flex-col justify-center p-8 sm:p-10 md:w-1/2">
                <div class="auth-logo-wrap mb-8 flex items-center justify-center rounded-2xl bg-white p-6 shadow-sm">
                    <img class="auth-logo mx-auto h-auto w-40 max-w-full object-contain sm:w-48" src="{{ asset('assets/iris-logo.png') }}" alt="IRIS International Rapport Insight System">
                </div>
                <div class="auth-side-copy"><h2>A clearer view of institutional performance.</h2><p class="mt-4 text-sm">CLSU Performance Observatory for the Office of International Affairs.</p></div>
                <p class="auth-side-footer mt-6 text-sm">International Affairs Office · Central Luzon State University</p>
            </aside>
            <div class="auth-form-panel flex w-full min-w-0 flex-col justify-center p-8 sm:p-10 md:w-1/2">
                <div class="mb-8 flex items-start justify-between"><div><p class="auth-title mb-2 text-sm font-semibold uppercase tracking-[.16em]">IRIS Login</p><h1 class="text-2xl font-semibold tracking-tight">Welcome back</h1><p class="auth-subtitle mt-2 text-sm">Sign in to continue to your observatory.</p></div><button type="button" data-theme-toggle class="icon-button" aria-label="Toggle dark mode" aria-pressed="false"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m8-9h1M3 12H2m16.95-6.95-.7.7M5.75 18.25l-.7.7m12.2-.7.7.7M5.75 5.75l-.7-.7M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" /></svg></button></div>
                @if(session('error'))<div class="mb-5 flex gap-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800" role="alert"><svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.29 3.86 2.82 17.25A1.5 1.5 0 0 0 4.12 19.5h15.76a1.5 1.5 0 0 0 1.3-2.25L13.71 3.86a1.96 1.96 0 0 0-3.42 0Z" /></svg><span>{{ session('error') }}</span></div>@endif
                @if($errors->any())<div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-login-form novalidate>
                    @csrf
                    <div>
                        <label for="username" class="mb-2 block text-sm font-medium text-gray-900 dark:text-gray-100">Username</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="username"
                            class="block w-full rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-900 shadow-sm transition-ui placeholder:text-gray-400 focus:border-clsu-500 focus:outline-none focus:ring-1 focus:ring-clsu-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                            aria-describedby="username-help">
                        <p id="username-help" class="mt-1.5 hidden text-xs text-rose-700 dark:text-rose-400" data-field-error>Username is required.</p>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-gray-900 dark:text-gray-100">Password</label>
                            <span class="auth-helper text-xs text-gray-500 dark:text-gray-400">Use your IRIS account password</span>
                        </div>
                        <input id="password" type="password" name="password" autocomplete="current-password"
                            class="block w-full rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-900 shadow-sm transition-ui placeholder:text-gray-400 focus:border-clsu-500 focus:outline-none focus:ring-1 focus:ring-clsu-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                            aria-describedby="password-help">
                        <p id="password-help" class="mt-1.5 hidden text-xs text-rose-700 dark:text-rose-400" data-field-error>Password is required.</p>
                    </div>
                    <button type="submit" class="auth-submit flex w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold shadow-sm transition-ui focus:ring-4 focus:ring-clsu-200" data-submit-button><svg data-spinner class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 0-4 4H4Z"/></svg><span data-submit-label>Log In</span></button>
                </form>
                <p class="auth-footer mt-7 text-center text-sm">No account? <a href="{{ route('register') }}" class="auth-link font-semibold hover:underline">Register here</a></p>
            </div>
        </section>
    </main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.2/flowbite.min.js"></script>
    <script>
        const root=document.documentElement; const themeToggle=document.querySelector('[data-theme-toggle]'); const savedTheme=localStorage.getItem('iris-theme'); const prefersDark=window.matchMedia('(prefers-color-scheme: dark)').matches; const initialDark=savedTheme?savedTheme==='dark':prefersDark; root.classList.toggle('dark',initialDark); document.body.classList.toggle('dark',initialDark); themeToggle.setAttribute('aria-pressed',initialDark?'true':'false'); themeToggle.addEventListener('click',()=>{const dark=root.classList.toggle('dark');document.body.classList.toggle('dark',dark);themeToggle.setAttribute('aria-pressed',dark?'true':'false');localStorage.setItem('iris-theme',dark?'dark':'light');});
        document.querySelector('[data-login-form]').addEventListener('submit',(event)=>{let valid=true;event.currentTarget.querySelectorAll('input').forEach((input)=>{const error=document.getElementById(`${input.id}-help`);const invalid=!input.value.trim();valid=invalid?false:valid;input.setAttribute('aria-invalid',invalid?'true':'false');input.classList.toggle('border-rose-500',invalid);error.classList.toggle('hidden',!invalid);});if(!valid){event.preventDefault();event.currentTarget.querySelector('input[aria-invalid="true"]').focus();return;}document.querySelector('[data-spinner]').classList.remove('hidden');document.querySelector('[data-submit-label]').textContent='Signing in…';document.querySelector('[data-submit-button]').disabled=true;});
    </script>
</body>
</html>
