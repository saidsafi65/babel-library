<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Babel Library') }} - مكتبة بابل</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .arabic {
            font-family: 'Cairo', sans-serif;
        }

        .english {
            font-family: 'Figtree', sans-serif;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .rtl {
            direction: rtl;
        }

        .ltr {
            direction: ltr;
        }
    </style>
</head>

<body class="antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
<!-- Logo -->
<div class="flex items-center space-x-4 rtl:space-x-reverse">
    <img class="h-20 w-20" src="{{ asset('assets/logo/babel-logo.png') }}" alt="Babel Library Logo">
    <div>
        <h1 class="text-2xl font-bold text-indigo-600 arabic">{{ __('messages.library_name') }}</h1>
        <p class="text-sm text-gray-500 english">{{ __('messages.library_name_en') }}</p>
    </div>
</div>


                <!-- Language Toggle -->
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <a href="{{ LaravelLocalization::getLocalizedURL(app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        class="text-gray-500 hover:text-indigo-600 transition-colors font-medium">
                        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                    </a>


                    <!-- Auth Links -->
                    @if (Route::has('login'))
                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                    class="text-gray-700 hover:text-indigo-600 transition-colors font-medium">
                                    <span class="arabic">{{ __('messages.dashboard') }}</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="text-gray-700 hover:text-indigo-600 transition-colors font-medium">
                                    <span class="arabic">{{ __('messages.login') }}</span>
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                                        <span class="arabic">{{ __('messages.register') }}</span>
                                    </a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center fade-in">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 arabic">
                    {{ __('messages.hero_title') }}
                </h1>
                <h2 class="text-2xl md:text-3xl text-white mb-8 english opacity-90">
                    {{ __('messages.hero_subtitle') }}
                </h2>
                <p class="text-xl text-white mb-12 max-w-3xl mx-auto opacity-80 arabic">
                    {{ __('messages.hero_description') }}
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    @guest
                        <a href="{{ route('register') }}"
                            class="bg-white text-indigo-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                            <span class="arabic">{{ __('messages.start_now') }}</span>
                        </a>
                        <a href="{{ route('login') }}"
                            class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-indigo-600 transition-all">
                            <span class="arabic">{{ __('messages.already_have_account') }}</span>
                        </a>
                    @else
                        <a href="{{ url('/dashboard') }}"
                            class="bg-white text-indigo-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                            <span class="arabic">{{ __('messages.go_to_library') }}</span>
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Floating Elements -->
        <div class="absolute top-10 left-10 w-20 h-20 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-16 h-16 bg-white bg-opacity-10 rounded-full animate-pulse"
            style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 right-1/4 w-12 h-12 bg-white bg-opacity-10 rounded-full animate-pulse"
            style="animation-delay: 2s;"></div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 arabic">{{ __('messages.features_title') }}</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto arabic">
                    {{ __('messages.features_subtitle') }}
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="text-center p-8 rounded-xl card-hover bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-100">
                    <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4 arabic">{{ __('messages.feature_1_title') }}
                    </h3>
                    <p class="text-gray-600 arabic">{{ __('messages.feature_1_desc') }}</p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="text-center p-8 rounded-xl card-hover bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4 arabic">{{ __('messages.feature_2_title') }}
                    </h3>
                    <p class="text-gray-600 arabic">{{ __('messages.feature_2_desc') }}</p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="text-center p-8 rounded-xl card-hover bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-100">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4 arabic">{{ __('messages.feature_3_title') }}
                    </h3>
                    <p class="text-gray-600 arabic">{{ __('messages.feature_3_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-20 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-indigo-600 mb-2">1000+</div>
                    <div class="text-gray-600 arabic">{{ __('messages.stats_books') }}</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-green-600 mb-2">500+</div>
                    <div class="text-gray-600 arabic">{{ __('messages.stats_recipes') }}</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-purple-600 mb-2">50+</div>
                    <div class="text-gray-600 arabic">{{ __('messages.stats_categories') }}</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">24/7</div>
                    <div class="text-gray-600 arabic">{{ __('messages.stats_available') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4 arabic">{{ __('messages.library_name') }}</h3>
                    <p class="text-gray-400 arabic">{{ __('messages.footer_about') }}</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4 arabic">{{ __('messages.quick_links') }}</h4>
                    <ul class="space-y-2">
                        <li><a href="#"
                                class="text-gray-400 hover:text-white transition-colors arabic">{{ __('messages.about_us') }}</a>
                        </li>
                        <li><a href="#"
                                class="text-gray-400 hover:text-white transition-colors arabic">{{ __('messages.contact_us') }}</a>
                        </li>
                        <li><a href="#"
                                class="text-gray-400 hover:text-white transition-colors arabic">{{ __('messages.privacy_policy') }}</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4 arabic">{{ __('messages.follow_us') }}</h4>
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400 arabic">{{ __('messages.rights_reserved') }}</p>
            </div>
        </div>
    </footer>

    <script>
        // Language toggle functionality
        function toggleLanguage() {
            const html = document.documentElement;
            const currentLang = html.getAttribute('lang');
            const currentDir = html.getAttribute('dir');

            if (currentLang === 'ar') {
                html.setAttribute('lang', 'en');
                html.setAttribute('dir', 'ltr');
                document.getElementById('lang-toggle').textContent = 'العربية';
            } else {
                html.setAttribute('lang', 'ar');
                html.setAttribute('dir', 'rtl');
                document.getElementById('lang-toggle').textContent = 'English';
            }
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('shadow-xl');
            } else {
                nav.classList.remove('shadow-xl');
            }
        });
    </script>
</body>

</html>
