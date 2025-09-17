<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    // public function update(ProfileUpdateRequest $request): RedirectResponse
    // {
    //     $request->user()->fill($request->validated());

    //     if ($request->user()->isDirty('email')) {
    //         $request->user()->email_verified_at = null;
    //     }

    //     $request->user()->save();

    //     return Redirect::route('profile.edit')->with('status', 'profile-updated');
    // }
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // تحديث الاسم والإيميل
        $user->fill($request->validated());

        // إذا تم تعديل الإيميل، إلغاء التحقق
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // تحديث التفضيلات (theme + lang)
        $prefs = $user->preferences ?? [];

        if ($request->has('theme')) {
            $prefs['theme'] = in_array($request->theme, ['light', 'dark']) ? $request->theme : 'light';
        }

        if ($request->has('lang')) {
            $prefs['lang'] = in_array($request->lang, ['ar', 'en']) ? $request->lang : app()->getLocale();

            // تحديث اللغة في السيشن
            session(['language' => $prefs['lang']]);
        }

        $user->preferences = $prefs;

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
