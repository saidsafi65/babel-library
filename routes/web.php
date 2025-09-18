<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\BookUploadController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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

            // قارئ الكتاب وحفظ التقدم
            Route::get('/{book}/read', [BookController::class, 'read'])->name('read');
            //
            Route::get('/{book}/pdf', [BookController::class, 'viewPdf'])->name('book.pdf');
            Route::post('/{book}/progress', [BookController::class, 'saveProgress'])->name('progress');

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

Route::get('/test-storage', function () {
    return [
        'storage_path' => storage_path(),
        'public_path' => public_path(),
        'books_json_exists' => file_exists(public_path('assets/book/books.json')),
        'storage_books_dir' => Storage::files('books'),
        'default_disk' => config('filesystems.default')
    ];
});

Route::get('/fix-config', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return '✅ تم مسح الكاش بنجاح';
});

// روت تشخيص مبسط - أضفه في web.php
Route::get('/debug-books/{book?}', function ($book = null) {
    $jsonPath = public_path('assets/book/books.json');

    $debug = [];

    // فحص المسارات
    $debug['paths'] = [
        'JSON Path' => $jsonPath,
        'JSON Exists' => file_exists($jsonPath) ? '✅ YES' : '❌ NO',
        'Public Path' => public_path(),
        'Storage Path' => storage_path('app'),
    ];

    // فحص storage
    $debug['storage'] = [
        'Default Disk' => config('filesystems.default'),
        'Books Folder' => Storage::exists('books') ? '✅ EXISTS' : '❌ NOT FOUND',
        'Files Count' => count(Storage::exists('books') ? Storage::files('books') : [])
    ];

    // فحص JSON
    if (file_exists($jsonPath)) {
        $content = file_get_contents($jsonPath);
        $books = json_decode($content, true);

        $debug['json'] = [
            'File Size' => strlen($content) . ' bytes',
            'Valid JSON' => is_array($books) ? '✅ YES' : '❌ NO',
            'Books Count' => is_array($books) ? count($books) : 0,
        ];

        if (is_array($books)) {
            $debug['books_list'] = [];
            foreach ($books as $b) {
                $debug['books_list'][] = [
                    'ID' => $b['id'] ?? 'NO ID',
                    'Title' => $b['title'] ?? 'NO TITLE',
                    'PDF' => $b['pdf'] ?? 'NO PDF',
                ];
            }
        }

        // فحص كتاب محدد
        if ($book) {
            $bookData = collect($books ?: [])->firstWhere('id', (int) $book);
            $debug['specific_book'] = [
                'Requested ID' => $book,
                'Found' => $bookData ? '✅ YES' : '❌ NO',
                'Data' => $bookData ?: 'NOT FOUND'
            ];

            if ($bookData) {
                $pdfPaths = [
                    "books/{$bookData['pdf']}" => Storage::exists("books/{$bookData['pdf']}"),
                    "books/{$book}.pdf" => Storage::exists("books/{$book}.pdf"),
                ];

                $debug['pdf_files'] = [];
                foreach ($pdfPaths as $path => $exists) {
                    $debug['pdf_files'][$path] = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
                }
            }
        }
    } else {
        $debug['json'] = ['error' => '❌ books.json file not found!'];
    }

    // إرجاع النتيجة كـ HTML مقروء
    $html = '<div style="font-family: monospace; background: #f5f5f5; padding: 20px;">';
    $html .= '<h2>🔍 Book System Debug Report</h2>';

    foreach ($debug as $section => $data) {
        $html .= "<h3>📋 " . ucwords(str_replace('_', ' ', $section)) . "</h3>";
        $html .= '<ul>';

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $html .= "<li><strong>$key:</strong><pre>" . print_r($value, true) . "</pre></li>";
                } else {
                    $html .= "<li><strong>$key:</strong> $value</li>";
                }
            }
        }

        $html .= '</ul><hr>';
    }

    $html .= '</div>';

    return $html;
});

require __DIR__ . '/auth.php';
