<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
