<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- الاسم --}}
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)"
                required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- الإيميل --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        {{-- اللغة --}}
        <div>
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-200 mb-1">اللغة:</label>
            <select name="lang"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="ar" {{ auth()->user()->preferences['lang'] == 'ar' ? 'selected' : '' }}>العربية
                </option>
                <option value="en" {{ auth()->user()->preferences['lang'] == 'en' ? 'selected' : '' }}>English
                </option>
            </select>
        </div>

        {{-- الثيم --}}
        <div>
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-200 mb-1">الوضع:</label>
            <select name="theme"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="light" {{ auth()->user()->preferences['theme'] == 'light' ? 'selected' : '' }}>فاتح
                </option>
                <option value="dark" {{ auth()->user()->preferences['theme'] == 'dark' ? 'selected' : '' }}>داكن
                </option>
            </select>
        </div>

        {{-- زر الحفظ --}}
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('messages.edit_profile') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('messages.saved_profile') }}</p>
            @endif
        </div>
    </form>

</section>
