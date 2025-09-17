<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return redirect()->route('login'); // إعادة توجيه إلى صفحة تسجيل الدخول
        }

        return $next($request); // متابعة تنفيذ الطلب
    }
}
