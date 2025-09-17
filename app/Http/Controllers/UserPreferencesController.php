<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class UserPreferencesController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'theme' => 'required|in:light,dark',
            'lang'  => 'required|in:ar,en',
        ]);

        $user = $request->user();
        $user->update([
            'preferences' => array_merge($user->preferences ?? [], $data)
        ]);

        return back()->with('success', 'تم حفظ التفضيلات بنجاح ✅');
    }

    public function toggleTheme(Request $request)
    {
        $user = $request->user();
        $current = data_get($user->preferences, 'theme', 'light');
        $next = $current === 'dark' ? 'light' : 'dark';

        $prefs = $user->preferences ?? [];
        $prefs['theme'] = $next;

        // Ensure language key exists to avoid validation elsewhere
        if (!isset($prefs['lang'])) {
            $prefs['lang'] = app()->getLocale();
        }

        $user->update(['preferences' => $prefs]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'theme' => $next]);
        }

        return back()->with('success', $next === 'dark' ? 'تم تفعيل الوضع الليلي 🌙' : 'تم تفعيل الوضع النهاري ☀️');
    }

    public function toggleLanguage(Request $request)
    {
        $user = $request->user();
        $current = data_get($user->preferences, 'lang', app()->getLocale());
        $next = $current === 'ar' ? 'en' : 'ar';

        $prefs = $user->preferences ?? [];
        $prefs['lang'] = $next;
        if (!isset($prefs['theme'])) {
            $prefs['theme'] = 'light';
        }

        $user->update(['preferences' => $prefs]);
        session(['language' => $next]);

        // Redirect to the previous page but with the new locale, not to the POST endpoint itself
        $previousUrl = url()->previous();
        $appBase = url('/');
        if (!str_starts_with($previousUrl, $appBase)) {
            // Fallback to home if the referrer is external or missing
            $previousUrl = $appBase;
        }
        $redirectUrl = LaravelLocalization::getLocalizedURL($next, $previousUrl, [], true);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'lang' => $next, 'redirect' => $redirectUrl]);
        }

        return redirect($redirectUrl)->with('success', $next === 'ar' ? 'تم التحويل إلى العربية' : 'Switched to English');
    }
}
