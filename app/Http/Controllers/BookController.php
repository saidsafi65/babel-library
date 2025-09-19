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
        if (str_contains($userAgent, 'IDM') || 
            str_contains($userAgent, 'Internet Download Manager') ||
            str_contains($userAgent, 'Download Manager')) {
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
            // Debug: سجل بعض رؤوس الطلب والحالة الحالية للجلسة
            Log::info('viewPdf called', [
                'book' => $book,
                'ip' => $request->ip(),
                'ua' => $request->header('User-Agent'),
                'range' => $request->header('Range'),
                'x_requested_with' => $request->header('X-Requested-With'),
                'x_pdf_token' => $request->header('X-PDF-Token'),
                'cookies' => $request->cookies->all(),
            ]);
            $sessionKey = "pdf_access_{$book}";
            
            // إذا كان الطلب XHR، نتعامل مع التوكن
            if ($request->ajax() || $request->wantsJson()) {
                $newToken = hash('sha256', uniqid() . $book . time());
                session([$sessionKey => $newToken]);
                Log::info('viewPdf: issuing token', ['book' => $book, 'token' => substr($newToken,0,12)]);
                return response()->json(['token' => $newToken]);
            }

            // التعامل مع الطلبات المباشرة للملف
            $requestToken = $request->header('X-PDF-Token');
            $validToken = session($sessionKey);

            // التحقق من صحة التوكن
            if ($requestToken && $validToken && $requestToken === $validToken) {
                // التوكن صالح - نواصل لعرض الملف
                Log::info('viewPdf: token validated', ['book' => $book]);
            } else {
                // بدل redirect أو نص، أعد PDF فيه رسالة خطأ لمنع حظر Chrome
                Log::info('viewPdf: invalid token or missing - returning error PDF', ['book' => $book, 'requestToken' => $requestToken ? substr($requestToken,0,12) : null, 'sessionToken' => $validToken ? substr($validToken,0,12) : null]);
                $errorPdf = base64_decode('JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PC9UeXBlL1BhZ2UvTWVkaWFCb3hbMCAwIDMwMCA0MDBdL0NvbnRlbnRzIDIgMCBSL0dyb3VwPDwvUy9UcmFuc3BhcmVuY3kvQ1MvRGV2aWNlUkdCL0kgdHJ1ZT4+Pj4KZW5kb2JqCjIgMCBvYmoKPDwvTGVuZ3RoIDc1Pj4Kc3RyZWFtCkJUIAovRjEgMjQgVGYKMTAgMTAwIFRECi9KZXNzYSBnaXIgUERGIC8gSmFsc2EgZ2lyIHNhbGloCkVUCmVuZHN0cmVhbQplbmRvYmoKMyAwIG9iago8PC9UeXBlL0NhdGFsb2cvUGFnZXMgMSAwIFI+PgplbmRvYmoKeHJlZgowIDQKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMDExIDAwMDAwIG4gCjAwMDAwMDAwNzUgMDAwMDAgbiAKMDAwMDAwMDE1NSAwMDAwMCBuIAp0cmFpbGVyCjw8L1Jvb3QgMyAwIFIKL1NpemUgND4+CnN0YXJ0eHJlZgo0MTYKJSVFT0YK');
                return response($errorPdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="error.pdf"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'Pragma' => 'private',
                    'X-Content-Type-Options' => 'nosniff',
                    'Content-Security-Policy' => 'default-src \'self\''
                ]);
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

            // البحث عن الملف في storage أو public/storage
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

            // التأكد من وجود الملف وقابلية القراءة
            if (!is_readable($absolutePath)) {
                throw new \Exception('لا يمكن قراءة ملف PDF');
            }

            $filesize = filesize($absolutePath);
            $filename = basename($absolutePath);

            // دعم طلبات Range (مطلوب من Chrome و PDF viewers)
            $start = 0;
            $end = $filesize - 1;
            $status = 200;

            if ($request->headers->has('Range')) {
                $range = $request->header('Range'); // e.g. bytes=0-499
                if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                    if ($matches[1] !== '') $start = intval($matches[1]);
                    if ($matches[2] !== '') $end = intval($matches[2]);
                    if ($start > $end || $start < 0 || $end >= $filesize) {
                        return response('', 416)->header('Content-Range', "bytes */{$filesize}");
                    }
                    $status = 206; // Partial Content
                }
            }

            $length = $end - $start + 1;

            // إعداد الهيدرز المناسبة
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $length,
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'private',
            ];

            if ($status === 206) {
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$filesize}";
            }

            // تدفق الملف جزئياً - نستخدم readfile مع fseek
            Log::info('viewPdf: streaming file', ['book' => $book, 'absolutePath' => $absolutePath, 'filesize' => $filesize, 'start' => $start, 'end' => $end, 'status' => $status]);
            $response = response()->stream(function () use ($absolutePath, $start, $length) {
                $handle = fopen($absolutePath, 'rb');
                if ($handle === false) {
                    return;
                }
                try {
                    fseek($handle, $start);
                    $bufferSize = 1024 * 8; // 8KB
                    $remaining = $length;
                    while ($remaining > 0 && !feof($handle)) {
                        $read = ($remaining > $bufferSize) ? $bufferSize : $remaining;
                        echo fread($handle, $read);
                        flush();
                        $remaining -= $read;
                    }
                } finally {
                    fclose($handle);
                }
            }, $status, $headers);

            return $response;

        } catch (\Exception $e) {
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

            return view('books.read', [
                'bookId' => (int) $book,
                'lastPage' => $lastPage,
                'bookData' => $bookData,
            ]);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }
}