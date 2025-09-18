<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

        // مسارات التخزين على قرص public (storage/app/public)
        $pdfDir = "assets/book/{$category}/pdf";
        $imgDir = "assets/book/{$category}/image";

        // إنشاء المجلدات إن لم تكن موجودة
        Storage::disk('public')->makeDirectory($pdfDir);
        Storage::disk('public')->makeDirectory($imgDir);

        // إنشاء اسم أساسي موحّد من العنوان لاستخدامه لكلا الملفين
        $baseName = Str::slug($data['title'], '_');
        if ($baseName === '') {
            $baseName = 'book_' . time();
        }

        // ضمان عدم التكرار: إذا كان موجوداً، ألحق عداداً
        $candidate = $baseName;
        $i = 1;
        while (
            Storage::disk('public')->exists($pdfDir . '/' . $candidate . '.pdf') ||
            Storage::disk('public')->exists($imgDir . '/' . $candidate . '.jpg') ||
            Storage::disk('public')->exists($imgDir . '/' . $candidate . '.jpeg') ||
            Storage::disk('public')->exists($imgDir . '/' . $candidate . '.png') ||
            Storage::disk('public')->exists($imgDir . '/' . $candidate . '.webp')
        ) {
            $candidate = $baseName . '_' . $i++;
        }
        $baseName = $candidate;

        // حفظ PDF
        $pdfFile = $request->file('pdf');
        $pdfName = $baseName . '.pdf';
        $pdfFile->storeAs($pdfDir, $pdfName, 'public');

        // حفظ الصورة بنفس الاسم الأساس مع لاحقة الامتداد الفعلي
        $imgFile = $request->file('image');
        $imgExt = strtolower($imgFile->getClientOriginalExtension());
        $imgName = $baseName . '.' . $imgExt; // نفس الاسم بالضبط مع اختلاف الامتداد
        $imgFile->storeAs($imgDir, $imgName, 'public');

        // قراءة/تحديث ملف JSON من قرص public
        $jsonRelativePath = 'assets/book/books.json';
        $books = [];
        if (Storage::disk('public')->exists($jsonRelativePath)) {
            $raw = Storage::disk('public')->get($jsonRelativePath);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $books = $decoded;
            }
        }

        // حساب معرف جديد (max+1)
        $nextId = 1;
        foreach ($books as $b) {
            if (isset($b['id']) && is_numeric($b['id'])) {
                $nextId = max($nextId, (int) $b['id'] + 1);
            }
        }

        $newBook = [
            'id' => $nextId,
            'title' => $data['title'],
            'category' => $category,
            'image' => '/storage/' . $imgDir . '/' . $imgName,
            'pdf' => '/storage/' . $pdfDir . '/' . $pdfName,
            'author' => $data['author'] ?? null,
            'description' => $data['description'] ?? null,
            'rating' => isset($data['rating']) ? (float) $data['rating'] : null,
            'year' => (int) $data['year'],
        ];

        $books[] = $newBook;
        Storage::disk('public')->put($jsonRelativePath, json_encode($books, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'تم رفع الكتاب وإضافته بنجاح ✅');
    }
}
