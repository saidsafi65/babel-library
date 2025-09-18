<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function index()
    {
        // يعرض صفحة قائمة الكتب التي تم تحديثها لقراءة من JSON
        return view('books.index');
    }

    public function show($book)
    {
        // يمكن لاحقاً قراءة التفاصيل من books.json عبر id
        // حالياً نعيد 404 حتى يتم إنشاء صفحة عرض مفصلة
        abort(404);
    }

    public function viewPdf($book)
    {
        $path = "books/{$book}.pdf"; // مسار الكتاب عندك بالستوريج

        if (!Storage::exists($path)) {
            abort(404);
        }

        $file = Storage::get($path);

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
    public function read(Request $request, $book)
    {
        $user = $request->user();
        $lastPage = (int) data_get($user->preferences, 'reading_progress.' . $book, 1);

        return response()->view('books.read', [
            'bookId' => (int) $book,
            'lastPage' => $lastPage,
        ]);
    }

    public function saveProgress(Request $request, $book)
    {
        $data = $request->validate([
            'page' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $prefs['reading_progress'] = $prefs['reading_progress'] ?? [];
        $prefs['reading_progress'][(string) $book] = (int) $data['page'];
        $user->update(['preferences' => $prefs]);

        return response()->json([
            'success' => true,
            'book' => (int) $book,
            'page' => (int) $data['page'],
        ]);
    }
}
