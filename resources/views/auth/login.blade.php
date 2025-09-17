<x-guest-layout>
    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-600 relative overflow-hidden">
        <!-- تأثير ضبابي خفيف -->
        <div class="absolute inset-0 bg-black opacity-10 blur-3xl"></div>

        <!-- صندوق تسجيل الدخول -->
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl p-10 space-y-8 z-10">

            <!-- عنوان الصفحة -->
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-800 mb-2 animate-fade-in-down">مكتبة بابل</h1>
                <p class="text-gray-500 text-sm animate-fade-in-up">سجل دخولك لتتمكن من الوصول لكل محتويات المكتبة 📚</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <!-- الفورم -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- البريد الإلكتروني -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12H8m8 0a4 4 0 01-8 0m8 0V8m0 4v4m0-4h-8" />
                        </svg>
                    </span>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username"
                        class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition duration-300">
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600" />
                </div>

                <!-- كلمة المرور -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11c1.657 0 3 1.343 3 3v3H9v-3c0-1.657 1.343-3 3-3z" />
                        </svg>
                    </span>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition duration-300">
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600" />
                </div>

                <!-- تذكرني و نسيت كلمة المرور -->
                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" name="remember"
                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-gray-600">تذكرني</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-purple-600 hover:underline">نسيت كلمة
                            المرور؟</a>
                    @endif
                </div>

                <!-- زر تسجيل الدخول -->
                <button type="submit"
                    class="w-full py-3 rounded-2xl bg-purple-600 text-white font-semibold text-lg hover:bg-purple-700 hover:shadow-lg transition duration-300">
                    تسجيل الدخول
                </button>

                <!-- Divider -->
                <div class="flex items-center my-4">
                    <hr class="flex-grow border-gray-300">
                    <span class="px-2 text-gray-400 text-sm">أو</span>
                    <hr class="flex-grow border-gray-300">
                </div>

                <!-- تسجيل الدخول عبر Google -->
                <a href="{{ route('google.redirect') }}"
                    class="w-full inline-flex items-center justify-center px-4 py-3 border rounded-2xl bg-white text-gray-700 hover:bg-gray-50 shadow-md transition">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 533.5 544.3" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M533.5 278.4c0-17.4-1.6-34.1-4.6-50.4H272v95.3h147.6c-6.4 34.7-25.5 64-54.5 83.6l88.1 68c51.5-47.5 80.3-117.5 80.3-196.5z"
                            fill="#4285f4" />
                        <path
                            d="M272 544.3c72.9 0 134.1-24.1 178.8-65.5l-88.1-68c-24.4 16.4-55.4 26-90.7 26-69.7 0-128.8-47-150.1-110.1l-92.4 71.4c43.3 85.3 131.9 146.2 242.5 146.2z"
                            fill="#34a853" />
                        <path
                            d="M121.9 326.7c-10.2-30-10.2-62.5 0-92.5l-92.4-71.4c-38.9 76.7-38.9 159.7 0 236.4l92.4-72.5z"
                            fill="#fbbc04" />
                        <path
                            d="M272 107.7c39.7 0 75.3 13.7 103.5 40.8l77.5-77.5C407 24.1 344.9 0 272 0 161.4 0 72.8 60.9 29.5 146.2l92.4 71.4C143.2 154.7 202.3 107.7 272 107.7z"
                            fill="#ea4335" />
                    </svg>
                    سجل الدخول عبر Google
                </a>
            </form>
        </div>
    </div>
</x-guest-layout>

<style>
    @keyframes fade-in-down {
        0% {
            opacity: 0;
            transform: translateY(-20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fade-in-up {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-down {
        animation: fade-in-down 1s ease-out;
    }

    .animate-fade-in-up {
        animation: fade-in-up 1s ease-out;
    }
</style>
