<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookUploadController extends Controller
{
    // عرض نموذج الرفع (للأدمن فقط)
    public function create(Request $request)
    {
        // تحقّق بسيط للأدمن: يتوقع وجود حقل is_admin على المستخدم
        if (!$request->user() || !$request->user()->is_admin) {
            abort(403, 'غير مصرح لك بالدخول');
        }
        return view('books.upload');
    }

    // استلام وحفظ الكتاب والصورة وتحديث ملف JSON (للأدمن فقط)
    public function store(Request $request)
    {
        if (!$request->user() || !$request->user()->is_admin) {
            abort(403, 'غير مصرح لك بالدخول');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'year' => ['required', 'integer', 'min:0', 'max:' . date('Y')],
            'author' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'pdf' => ['required', 'file', 'mimetypes:application/pdf'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'category.regex' => 'يسمح بالحروف والأرقام والشرطة والشرطة السفلية فقط للتصنيف',
            'pdf.mimetypes' => 'يجب أن يكون الكتاب بصيغة PDF',
        ]);

        $category = strtolower($data['category']);
        $baseDir = public_path('assets/book/' . $category);
        $pdfDir = $baseDir . DIRECTORY_SEPARATOR . 'pdf';
        $imgDir = $baseDir . DIRECTORY_SEPARATOR . 'image';

        // إنشاء المجلدات إن لم تكن موجودة
        foreach ([$baseDir, $pdfDir, $imgDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // إنشاء اسم أساسي موحّد من العنوان لاستخدامه لكلا الملفين
        $baseName = Str::slug($data['title'], '_');
        if ($baseName === '') {
            $baseName = 'book_' . time();
        }

        // ضمان عدم التكرار: إذا كان موجوداً، ألحق عداداً
        $candidate = $baseName;
        $i = 1;
        while (file_exists($pdfDir . DIRECTORY_SEPARATOR . $candidate . '.pdf') ||
               file_exists($imgDir . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $baseName . '_' . $i++;
        }
        $baseName = $candidate;

        // حفظ PDF
        $pdfFile = $request->file('pdf');
        $pdfName = $baseName . '.pdf';
        $pdfFile->move($pdfDir, $pdfName);

        // حفظ الصورة بنفس الاسم الأساس مع لاحقة الامتداد الفعلي
        $imgFile = $request->file('image');
        $imgExt = strtolower($imgFile->getClientOriginalExtension());
        $imgName = $baseName . '.' . $imgExt; // نفس الاسم بالضبط مع اختلاف الامتداد
        $imgFile->move($imgDir, $imgName);

        // تحديث ملف JSON
        $jsonPath = public_path('assets/book/books.json');
        $books = [];
        if (file_exists($jsonPath)) {
            $raw = file_get_contents($jsonPath);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $books = $decoded;
            }
        }

        // حساب معرف جديد (max+1)
        $nextId = 1;
        foreach ($books as $b) {
            if (isset($b['id']) && is_numeric($b['id'])) {
                $nextId = max($nextId, (int)$b['id'] + 1);
            }
        }

        $newBook = [
            'id' => $nextId,
            'title' => $data['title'],
            'category' => $category,
            'image' => '/assets/book/' . $category . '/image/' . $imgName,
            'pdf' => '/assets/book/' . $category . '/pdf/' . $pdfName,
            'author' => $data['author'] ?? null,
            'description' => $data['description'] ?? null,
            'rating' => isset($data['rating']) ? (float)$data['rating'] : null,
            'year' => (int)$data['year'],
        ];

        $books[] = $newBook;
        file_put_contents($jsonPath, json_encode($books, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'تم رفع الكتاب وإضافته بنجاح ✅');
    }
}
