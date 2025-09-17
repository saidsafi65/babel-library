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
        // الحصول على اللغة الحالية من التفضيلات أو من اللغة الافتراضية
        $current = data_get($user->preferences, 'lang', app()->getLocale());

        // تحديد اللغة التالية بناءً على اللغة الحالية
        $next = $current === 'ar' ? 'en' : 'ar';

        // تحديث التفضيلات الخاصة بالمستخدم
        $prefs = $user->preferences ?? [];
        $prefs['lang'] = $next;

        // التأكد من أن خاصية الثيم موجودة
        if (!isset($prefs['theme'])) {
            $prefs['theme'] = 'light';
        }

        // تحديث تفضيلات المستخدم في قاعدة البيانات
        $user->update(['preferences' => $prefs]);

        // تخزين اللغة في الجلسة
        session(['language' => $next]);

        // تحديد الرابط الذي سيتم إعادة التوجيه إليه بناءً على اللغة الجديدة
        $previousUrl = url()->previous();
        $appBase = url('/');

        // إذا كانت الصفحة السابقة خارج التطبيق، العودة إلى الصفحة الرئيسية
        if (!str_starts_with($previousUrl, $appBase)) {
            $previousUrl = $appBase;
        }

        // تحويل الرابط إلى الرابط المحلي للغة الجديدة
        $redirectUrl = LaravelLocalization::getLocalizedURL($next, $previousUrl, [], true);

        // إذا كان الطلب هو طلب JSON، إرسال استجابة JSON
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'lang' => $next, 'redirect' => $redirectUrl]);
        }

        // إعادة التوجيه إلى الرابط مع اللغة الجديدة
        return redirect($redirectUrl)->with('success', $next === 'ar' ? 'تم التحويل إلى العربية' : 'Switched to English');
    }
}
