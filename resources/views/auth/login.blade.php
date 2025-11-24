<x-auth-split-layout>
    <div class="space-y-2 mb-8">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Welcome back
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Please enter your details to sign in.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email address
            </label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required autofocus
                       value="{{ old('email') }}"
                       class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="mt-1 relative">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" name="remember" type="checkbox"
                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800">
            <label for="remember_me" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                Remember me for 30 days
            </label>
        </div>

        <div>
            <button type="submit"
                    class="flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-2.5 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                Sign in
            </button>
        </div>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white dark:bg-gray-900 px-2 text-gray-500 dark:text-gray-400">
                    Protected by KingWine Security
                </span>
            </div>
        </div>
    </div>
</x-auth-split-layout>
