<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('books.index')" :active="request()->routeIs('books.index')">
                        {{ __('Books') }}
                    </x-nav-link>
                    @if(Auth::check() && Auth::user()->is_admin)
                        <x-nav-link :href="route('books.upload.create')" :active="request()->routeIs('books.upload.*')">
                            رفع كتاب
                        </x-nav-link>
                    @endif
                </div>
            </div>


            <!-- Language + Theme Toggles -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <form id="lang-toggle-form" method="POST" action="{{ route('lang.toggle') }}" class="mr-3">
                    @csrf
                    @php($lang = (auth()->user()->preferences['lang'] ?? app()->getLocale()))
                    <button type="submit" title="{{ $lang === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white hover:from-emerald-700 hover:to-teal-700 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-400 dark:focus:ring-offset-gray-800 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m6 8h6m-3-3v6M4 10h8m-4 0v8" />
                        </svg>
                        <span class="uppercase">{{ $lang }}</span>
                    </button>
                </form>

                <form id="theme-toggle-form" method="POST" action="{{ route('theme.toggle') }}" class="mr-6">
                    @csrf
                    @php($isDark = (auth()->user()->preferences['theme'] ?? 'light') === 'dark')
                    <button type="submit" title="{{ $isDark ? 'الوضع النهاري' : 'الوضع الليلي' }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:from-indigo-700 hover:to-purple-700 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400 dark:focus:ring-offset-gray-800">
                        @if($isDark)
                            <!-- Sun icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 3a1 1 0 011 1v1a1 1 0 11-2 0V4a1 1 0 011-1zm0 11a4 4 0 100-8 4 4 0 000 8zm7-5a1 1 0 100 2h1a1 1 0 100-2h-1zM2 10a1 1 0 000 2H1a1 1 0 100-2h1zm12.95 5.536a1 1 0 10-1.414 1.414l.707.707a1 1 0 101.414-1.414l-.707-.707zM5.05 5.05a1 1 0 10-1.414-1.414l-.707.707A1 1 0 104.343 5.757l.707-.707zm10.607-2.121a1 1 0 011.415 1.414l-.708.708a1 1 0 11-1.414-1.415l.707-.707zM4.343 15.535a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 111.414-1.414l.707.707z" />
                            </svg>
                            <span class="hidden sm:inline text-sm">{{__('messages.theme_light')}}</span>
                        @else
                            <!-- Moon icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.293 13.293A8 8 0 116.707 2.707a8.003 8.003 0 1010.586 10.586z" />
                            </svg>
                            <span class="hidden sm:inline text-sm">{{__('messages.theme_dark')}}</span>
                        @endif
                    </button>
                </form>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Mobile Language + Theme Toggle -->
            <div class="-me-2 flex items-center sm:hidden">
                <form id="lang-toggle-form-mobile" method="POST" action="{{ route('lang.toggle') }}" class="mr-2">
                    @csrf
                    @php($lang = (auth()->user()->preferences['lang'] ?? app()->getLocale()))
                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white hover:from-emerald-700 hover:to-teal-700 shadow-md focus:outline-none" title="{{ $lang === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}">
                        <span class="text-xs uppercase">{{ $lang }}</span>
                    </button>
                </form>
                <form id="theme-toggle-form-mobile" method="POST" action="{{ route('theme.toggle') }}" class="mr-2">
                    @csrf
                    @php($isDark = (auth()->user()->preferences['theme'] ?? 'light') === 'dark')
                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:from-indigo-700 hover:to-purple-700 shadow-md focus:outline-none" title="{{ $isDark ? 'الوضع النهاري' : 'الوضع الليلي' }}">
                        @if($isDark)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 3a1 1 0 011 1v1a1 1 0 11-2 0V4a1 1 0 011-1zm0 11a4 4 0 100-8 4 4 0 000 8zm7-5a1 1 0 100 2h1a1 1 0 100-2h-1zM2 10a1 1 0 000 2H1a1 1 0 100-2h1zm12.95 5.536a1 1 0 10-1.414 1.414l.707.707a1 1 0 101.414-1.414l-.707-.707zM5.05 5.05a1 1 0 10-1.414-1.414l-.707.707A1 1 0 104.343 5.757l.707-.707zm10.607-2.121a1 1 0 011.415 1.414l-.708.708a1 1 0 11-1.414-1.415l.707-.707zM4.343 15.535a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 111.414-1.414l.707.707z" />
                            </svg>
                            <span class="text-xs">{{__('messages.theme_light')}}</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                            </svg>
                            <span class="text-xs">{{__('messages.theme_dark')}}</span>
                        @endif
                    </button>
                </form>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(Auth::check() && Auth::user()->is_admin)
                <x-responsive-nav-link :href="route('books.upload.create')" :active="request()->routeIs('books.upload.*')">
                    رفع كتاب
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
