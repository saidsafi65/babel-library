<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-10 rtl" dir="rtl">
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-800 border border-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-800 border border-red-200">
                <div class="font-bold mb-2">حدثت أخطاء:</div>
                <ul class="list-disc pr-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">رفع كتاب جديد (أدمن)</h1>
            <form method="POST" action="{{ route('books.upload.store') }}" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <div>
                    <label class="block mb-2 text-gray-700 font-semibold">اسم الكتاب</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block mb-2 text-gray-700 font-semibold">التصنيف (بالإنجليزية)</label>
                    <select name="category" required
                        class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                        <option value="" disabled selected>اختر التصنيف</option>
                        <option value="religion">الدين</option>
                        <option value="history">التاريخ</option>
                        <option value="programming">البرمجة</option>
                        <option value="literature">الأدب</option>
                        <option value="science">العلوم</option>
                        <option value="philosophy">الفلسفة</option>
                        <option value="psychology">علم النفس</option>
                        <option value="economics">الاقتصاد</option>
                        <option value="art">الفنون</option>
                        <option value="language">اللغات</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">يُسمح بالأحرف الإنجليزية والأرقام والشرطة (-) والشرطة السفلية
                        (_).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-gray-700 font-semibold">السنة</label>
                        <input type="number" name="year" min="0" max="{{ date('Y') }}"
                            value="{{ old('year') }}" required
                            class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-gray-700 font-semibold">التقييم (0 - 5)</label>
                        <input type="number" step="0.1" name="rating" min="0" max="5"
                            value="{{ old('rating') }}"
                            class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-gray-700 font-semibold">المؤلف</label>
                    <input type="text" name="author" value="{{ old('author') }}"
                        class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block mb-2 text-gray-700 font-semibold">الوصف</label>
                    <textarea name="description" rows="4"
                        class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-gray-700 font-semibold">رفع ملف الكتاب (PDF)</label>
                        <input type="file" name="pdf" accept="application/pdf" required
                            class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-gray-700 font-semibold">رفع صورة الغلاف (jpg, jpeg, png,
                            webp)</label>
                        <input type="file" name="image" accept="image/*" required
                            class="w-full border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition">
                        رفع الكتاب
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
