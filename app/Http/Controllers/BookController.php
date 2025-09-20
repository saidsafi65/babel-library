<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function index()
    {
        try {
            $books = [];

            // محاولة قراءة من Storage
            if (Storage::exists('book/books.json')) {
                $books = json_decode(Storage::get('book/books.json'), true) ?? [];
            }
            // الرجوع للمسار القديم كاحتياطي
            else {
                $jsonPath = public_path('assets/book/books.json');
                if (file_exists($jsonPath)) {
                    $books = json_decode(file_get_contents($jsonPath), true) ?? [];
                }
            }

            // تحديث المسارات
            $books = collect($books)->map(function ($book) {
                if (isset($book['image'])) {
                    $book['image'] = str_replace('/assets/', '/storage/', $book['image']);
                }
                if (isset($book['pdf'])) {
                    $book['pdf'] = str_replace('/assets/', '/storage/', $book['pdf']);
                }
                return $book;
            })->all();

            return view('books.index', [
                'books' => collect($books)->sortByDesc('created_at')->values()->all()
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في قراءة books.json: ' . $e->getMessage());
            return view('books.index', ['books' => []]);
        }
    }

    /**
     * التحقق من صحة طلب PDF
     */
    private function validatePdfRequest(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        // التحقق من وجود User-Agent
        if (empty($userAgent)) {
            return false;
        }

        // رفض طلبات IDM بشكل مباشر
        if (
            str_contains($userAgent, 'IDM') ||
            str_contains($userAgent, 'Internet Download Manager') ||
            str_contains($userAgent, 'Download Manager')
        ) {
            return false;
        }

        // التحقق من أن الطلب قادم من متصفح معروف
        $validBrowsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $isValidBrowser = false;
        foreach ($validBrowsers as $browser) {
            if (str_contains($userAgent, $browser)) {
                $isValidBrowser = true;
                break;
            }
        }

        return $isValidBrowser;
    }

    public function viewPdf(Request $request, $book)
    {
        try {
            Log::info('viewPdf called', [
                'book' => $book,
                'ip' => $request->ip(),
                'ua' => substr($request->header('User-Agent'), 0, 100),
                'referer' => $request->header('Referer')
            ]);

            // رفض برامج التحميل فقط
            $userAgent = $request->header('User-Agent');
            if ($userAgent && (
                str_contains($userAgent, 'IDM') ||
                str_contains($userAgent, 'Internet Download Manager') ||
                str_contains($userAgent, 'Download Manager') ||
                str_contains($userAgent, 'wget') ||
                str_contains($userAgent, 'curl') ||
                str_contains($userAgent, 'aria2')
            )) {
                Log::warning('Download manager blocked', ['ua' => $userAgent]);
                abort(403, 'Access Denied');
            }

            // قراءة بيانات الكتاب
            $jsonPath = public_path('assets/book/books.json');
            if (!file_exists($jsonPath)) {
                throw new \Exception('ملف قاعدة البيانات غير موجود');
            }

            $books = json_decode(file_get_contents($jsonPath), true);
            if (!is_array($books)) {
                throw new \Exception('خطأ في تنسيق قاعدة البيانات');
            }

            $bookData = collect($books)->firstWhere('id', (int) $book);
            if (!$bookData || empty($bookData['pdf'])) {
                throw new \Exception('الكتاب غير موجود');
            }

            // تنظيف المسار
            $pdfPath = ltrim($bookData['pdf'], '/');
            $pdfPath = preg_replace('/^assets\//', '', $pdfPath);

            // البحث عن الملف
            $absolutePath = null;
            if (Storage::exists($pdfPath)) {
                $absolutePath = Storage::path($pdfPath);
            } elseif (file_exists(public_path("storage/{$pdfPath}"))) {
                $absolutePath = public_path("storage/{$pdfPath}");
            } elseif (file_exists(public_path($pdfPath))) {
                $absolutePath = public_path($pdfPath);
            } else {
                throw new \Exception('ملف PDF غير موجود');
            }

            if (!is_readable($absolutePath)) {
                throw new \Exception('لا يمكن قراءة ملف PDF');
            }

            $filesize = filesize($absolutePath);
            $filename = basename($absolutePath);

            // دعم Range requests للمتصفحات
            $start = 0;
            $end = $filesize - 1;
            $status = 200;

            if ($request->headers->has('Range')) {
                $range = $request->header('Range');
                if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                    if ($matches[1] !== '') $start = intval($matches[1]);
                    if ($matches[2] !== '') $end = intval($matches[2]);
                    if ($start > $end || $start < 0 || $end >= $filesize) {
                        return response('', 416)->header('Content-Range', "bytes */{$filesize}");
                    }
                    $status = 206;
                }
            }

            $length = $end - $start + 1;

            // Headers مبسطة
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $length,
                'Cache-Control' => 'public, max-age=3600', // تخزين مؤقت لساعة واحدة
                'X-Content-Type-Options' => 'nosniff'
            ];

            if ($status === 206) {
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$filesize}";
            }

            Log::info('Serving PDF file', [
                'book' => $book,
                'filesize' => $filesize,
                'range' => "{$start}-{$end}",
                'status' => $status
            ]);

            // إرسال الملف
            return response()->stream(function () use ($absolutePath, $start, $length) {
                $handle = fopen($absolutePath, 'rb');
                if ($handle === false) {
                    return;
                }

                try {
                    fseek($handle, $start);
                    $bufferSize = 8192; // 8KB buffer
                    $remaining = $length;

                    while ($remaining > 0 && !feof($handle)) {
                        $read = min($bufferSize, $remaining);
                        $chunk = fread($handle, $read);
                        if ($chunk === false) break;

                        echo $chunk;
                        flush();
                        $remaining -= strlen($chunk);
                    }
                } finally {
                    fclose($handle);
                }
            }, $status, $headers);
        } catch (\Exception $e) {
            Log::error('PDF serving error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function read(Request $request, $book)
    {
        try {
            // تحقق من وجود الكتاب
            $jsonPath = public_path('assets/book/books.json');
            if (!file_exists($jsonPath)) {
                throw new \Exception('قاعدة البيانات غير متوفرة');
            }

            $books = json_decode(file_get_contents($jsonPath), true);
            $bookData = collect($books ?: [])->firstWhere('id', (int) $book);

            if (!$bookData) {
                throw new \Exception('الكتاب غير موجود');
            }

            // الحصول على آخر صفحة تمت قراءتها
            $user = $request->user();
            $lastPage = 1;

            if ($user) {
                $lastPage = (int) data_get($user->preferences, 'reading_progress.' . $book, 1);
            }

            // إنشاء توكن PDF مسبقاً
            $sessionKey = "pdf_access_{$book}";
            $pdfToken = hash('sha256', uniqid() . $book . time());
            session([$sessionKey => $pdfToken]);

            return view('books.read', [
                'bookId' => (int) $book,
                'lastPage' => $lastPage,
                'bookData' => $bookData,
                'pdfToken' => $pdfToken
            ]);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * حفظ تقدم القراءة
     */
    public function saveProgress(Request $request, $book)
    {
        try {
            $request->validate([
                'page' => 'required|integer|min:1'
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'غير مسموح'], 401);
            }

            $preferences = $user->preferences ?? [];
            $preferences['reading_progress'][$book] = $request->page;

            $user->update(['preferences' => $preferences]);

            return response()->json(['success' => true, 'page' => $request->page]);
        } catch (\Exception $e) {
            Log::error('خطأ في حفظ التقدم: ' . $e->getMessage());
            return response()->json(['error' => 'تعذر الحفظ'], 500);
        }
    }
}
