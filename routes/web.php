<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\BookUploadController;
use Illuminate\Support\Facades\Artisan;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


Route::get('/', function () {
    return redirect(LaravelLocalization::getLocalizedURL(app()->getLocale(), '/'));
});

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [
        \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
    ]
], function () {

    // الصفحة الرئيسية
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    // Dashboard للمستخدمين المسجلين
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');

    // مجموعة routes تتطلب تسجيل دخول
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // تبديل الثيم (ليلي/نهاري)
        Route::post('/theme/toggle', [UserPreferencesController::class, 'toggleTheme'])->name('theme.toggle');
        // تبديل اللغة (ar/en)
        Route::post('/lang/toggle', [UserPreferencesController::class, 'toggleLanguage'])->name('lang.toggle');

        // Routes خاصة بالكتب (سنضيفها لاحقاً)
        Route::prefix('books')->name('books.')->group(function () {
            Route::get('/', [BookController::class, 'index'])->name('index'); // هنا '/'
                        Route::get('/favorites', function () {
                return view('books.favorites');
            })->name('favorites');

            Route::get('/read-later', function () {
                return view('books.read-later');
            })->name('read-later');

            // رفع كتاب (أدمن فقط) - عرض النموذج وحفظ البيانات
            Route::get('/upload', [BookUploadController::class, 'create'])->middleware(['verified'])->name('upload.create');
            Route::post('/upload', [BookUploadController::class, 'store'])->middleware(['verified'])->name('upload.store');
            Route::get('/{book}', [BookController::class, 'show'])->name('show');
        });
    });

    // تضمين routes التوثيق من Breeze
    require __DIR__ . '/auth.php';

    // API Routes للمستقبل
    Route::prefix('api')->group(function () {
        // Routes للبحث
        Route::get('/search', function () {
            // سنضيف منطق البحث هنا لاحقاً
            return response()->json(['message' => 'Search API endpoint']);
        })->name('api.search');
    });

    // Routes لتغيير اللغة
    Route::post('/language', function () {
        $language = request('language', 'ar');
        session(['language' => $language]);

        return response()->json(['success' => true, 'language' => $language]);
    })->name('language.switch');
});

Route::get('/fix-config', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return '✅ تم مسح الكاش بنجاح';
});

require __DIR__ . '/auth.php';
