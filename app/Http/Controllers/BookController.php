<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function viewPdf($book)
    {
        // تسجيل للتشخيص
        Log::info("محاولة تحميل PDF للكتاب: {$book}");

        // تحقق من books.json أولاً
        $jsonPath = public_path('assets/book/books.json');
        Log::info("مسار JSON: {$jsonPath}");
        Log::info("هل JSON موجود؟ " . (file_exists($jsonPath) ? 'نعم' : 'لا'));

        if (!file_exists($jsonPath)) {
            Log::error("ملف books.json غير موجود في: {$jsonPath}");
            return response()->json([
                'error' => 'ملف قاعدة البيانات غير موجود',
                'debug' => [
                    'json_path' => $jsonPath,
                    'public_path' => public_path(),
                    'book_id' => $book
                ]
            ], 404);
        }

        // قراءة وتحليل JSON
        $content = file_get_contents($jsonPath);
        $books = json_decode($content, true);

        Log::info("عدد الكتب في JSON: " . (is_array($books) ? count($books) : 'غير صالح'));
        Log::info("بيانات JSON: " . substr($content, 0, 200) . '...');

        if (!is_array($books)) {
            Log::error("تنسيق JSON غير صحيح");
            return response()->json([
                'error' => 'خطأ في تنسيق قاعدة البيانات',
                'debug' => [
                    'json_content' => substr($content, 0, 100),
                    'json_error' => json_last_error_msg()
                ]
            ], 500);
        }

        // البحث عن الكتاب
        $bookData = collect($books)->firstWhere('id', (int) $book);
        Log::info("بيانات الكتاب الموجود: " . json_encode($bookData));

        if (!$bookData) {
            Log::error("الكتاب غير موجود في JSON. ID: {$book}");
            Log::info("الـ IDs المتاحة: " . json_encode(collect($books)->pluck('id')->toArray()));

            return response()->json([
                'error' => 'الكتاب غير موجود',
                'debug' => [
                    'requested_id' => (int) $book,
                    'available_ids' => collect($books)->pluck('id')->toArray(),
                    'books_count' => count($books)
                ]
            ], 404);
        }

        // تحقق من وجود مسار PDF في البيانات
        if (!isset($bookData['pdf']) || empty($bookData['pdf'])) {
            Log::error("مسار PDF غير محدد في بيانات الكتاب");
            return response()->json([
                'error' => 'مسار PDF غير محدد',
                'debug' => [
                    'book_data' => $bookData
                ]
            ], 400);
        }

        // طرق مختلفة للعثور على الملف
        $possiblePaths = [
            "books/{$bookData['pdf']}", // إذا كان PDF يحتوي على اسم الملف
            "books/{$book}.pdf", // الطريقة القديمة
            $bookData['pdf'], // إذا كان مسار كامل
            "public/assets/book/{$bookData['pdf']}" // إذا كان في مجلد assets
        ];

        $foundPath = null;
        foreach ($possiblePaths as $path) {
            Log::info("فحص المسار: {$path}");
            if (Storage::exists($path)) {
                $foundPath = $path;
                Log::info("تم العثور على الملف في: {$path}");
                break;
            }

            // تحقق من المسار المطلق أيضاً
            $absolutePath = storage_path("app/{$path}");
            if (file_exists($absolutePath)) {
                $foundPath = $path;
                Log::info("تم العثور على الملف في المسار المطلق: {$absolutePath}");
                break;
            }
        }

        if (!$foundPath) {
            Log::error("لم يتم العثور على ملف PDF في أي من المسارات المحتملة");
            Log::info("Storage disk: " . config('filesystems.default'));
            Log::info("Storage path: " . Storage::path(''));

            return response()->json([
                'error' => 'ملف PDF غير موجود',
                'debug' => [
                    'book_data' => $bookData,
                    'searched_paths' => $possiblePaths,
                    'storage_disk' => config('filesystems.default'),
                    'storage_path' => Storage::path(''),
                    'storage_url' => Storage::url('')
                ]
            ], 404);
        }

        // محاولة قراءة الملف
        try {
            $file = Storage::get($foundPath);
            Log::info("تم قراءة الملف بنجاح. حجم الملف: " . strlen($file) . " بايت");

            return response($file, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($foundPath) . '"',
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => strlen($file)
            ]);

        } catch (\Exception $e) {
            Log::error("خطأ في قراءة الملف: " . $e->getMessage());
            return response()->json([
                'error' => 'خطأ في قراءة الملف',
                'debug' => [
                    'file_path' => $foundPath,
                    'exception' => $e->getMessage()
                ]
            ], 500);
        }
    }

    // باقي الدوال...
    public function read(Request $request, $book)
    {
        // تحقق سريع من وجود الكتاب
        $jsonPath = public_path('assets/book/books.json');

        if (!file_exists($jsonPath)) {
            abort(404, 'قاعدة البيانات غير متوفرة');
        }

        $books = json_decode(file_get_contents($jsonPath), true);
        $bookData = collect($books ?: [])->firstWhere('id', (int) $book);

        if (!$bookData) {
            abort(404, 'الكتاب غير موجود');
        }

        $user = $request->user();
        $lastPage = 1;

        if ($user) {
            $lastPage = (int) data_get($user->preferences, 'reading_progress.' . $book, 1);
        }

        return response()->view('books.read', [
            'bookId' => (int) $book,
            'lastPage' => $lastPage,
            'bookData' => $bookData,
        ]);
    }
}
