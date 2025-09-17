<x-app-layout>
    <div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="flex items-center justify-between mb-4">
            <h1 id="bookTitle" class="text-2xl font-bold text-gray-800 dark:text-gray-100">قارئ PDF</h1>
            <a href="{{ route('books.index') }}" class="text-purple-600 hover:text-purple-800 font-semibold">العودة لقائمة الكتب</a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
            <div id="errorBox" class="mb-3 hidden text-sm text-red-600"></div>
            <div class="flex items-center justify-between mb-4 gap-2">
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">السابق</button>
                    <button id="nextPage" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">التالي</button>
                    <div class="flex items-center gap-2 mx-2">
                        <span class="text-sm text-gray-600 dark:text-gray-300">الصفحة:</span>
                        <input id="pageNum" type="number" class="w-20 px-2 py-1 rounded-lg border dark:bg-gray-700" min="1" value="{{ (int) ($lastPage ?? 1) }}">
                        <span id="pageCount" class="text-sm text-gray-600 dark:text-gray-300">/ ?</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span id="saveStatus" class="text-sm text-gray-500">لم يتم الحفظ بعد</span>
                </div>
            </div>

            <div id="viewerWrapper" class="w-full overflow-hidden bg-gray-100 dark:bg-gray-900 rounded-lg" style="min-height:70vh;">
                <!-- نعرض من خلال عارض المتصفح فقط مع إخفاء أي أدوات -->
                <iframe id="pdfIframe" class="w-full h-full" style="min-height:70vh; border:0;" referrerpolicy="no-referrer"></iframe>
            </div>
        </div>
    </div>

    <!-- نحمّل PDF.js فقط لاستخراج عدد الصفحات -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // متغيرات من السيرفر
        const BOOK_ID = {{ (int) ($bookId ?? 0) }};
        const LAST_PAGE = {{ (int) ($lastPage ?? 1) }};
        const LOCALE = "{{ app()->getLocale() }}";
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const BOOKS_JSON_URL = '/assets/book/books.json';

        // عناصر DOM
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageNumInput = document.getElementById('pageNum');
        const pageCountSpan = document.getElementById('pageCount');
        const saveStatus = document.getElementById('saveStatus');
        const titleEl = document.getElementById('bookTitle');
        const iframe = document.getElementById('pdfIframe');
        const errorBox = document.getElementById('errorBox');

        // pdf.js إعداد لاستخراج عدد الصفحات فقط
        const pdfjsLib = window['pdfjsLib'] || window['pdfjs-dist/build/pdf'];
        if (pdfjsLib && pdfjsLib.GlobalWorkerOptions) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        let currentPage = Math.max(1, LAST_PAGE || 1);
        let totalPages = 0;
        let pdfBuffer = null;
        let blobUrl = null;

        function updateIframe() {
            if (!blobUrl && pdfBuffer) {
                const blob = new Blob([pdfBuffer], { type: 'application/pdf' });
                blobUrl = URL.createObjectURL(blob);
            }
            if (blobUrl) {
                // إخفاء الواجهات قدر الإمكان + ملاءمة العرض عرض الصفحة
                const params = `#page=${currentPage}&zoom=page-width&toolbar=0&navpanes=0&scrollbar=0&statusbar=0&messages=0&view=FitH`;
                iframe.src = blobUrl + params;
            }
        }

        // حفظ التقدم للمستخدم
        let saveTimer = null;
        function debounceSave(page) {
            if (saveTimer) clearTimeout(saveTimer);
            saveTimer = setTimeout(() => saveProgress(page), 600);
        }
        async function saveProgress(page) {
            try {
                saveStatus.textContent = 'جاري الحفظ...';
                const res = await fetch(`/${LOCALE}/books/${BOOK_ID}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ page })
                });
                if (!res.ok) throw new Error('خطأ في حفظ التقدم');
                saveStatus.textContent = `تم الحفظ (صفحة ${page})`;
            } catch (e) {
                console.error(e);
                saveStatus.textContent = 'تعذر الحفظ';
            }
        }

        function showPrevPage() {
            if (currentPage <= 1) return;
            currentPage--;
            pageNumInput.value = currentPage;
            updateIframe();
            debounceSave(currentPage);
        }
        function showNextPage() {
            if (currentPage >= totalPages) return;
            currentPage++;
            pageNumInput.value = currentPage;
            updateIframe();
            debounceSave(currentPage);
        }
        function jumpToPage(num) {
            const n = Math.min(totalPages, Math.max(1, parseInt(num || 1)));
            currentPage = n;
            pageNumInput.value = currentPage;
            updateIframe();
            debounceSave(currentPage);
        }

        async function init() {
            try {
                const res = await fetch(BOOKS_JSON_URL, { cache: 'no-cache' });
                if (!res.ok) throw new Error('تعذر تحميل قائمة الكتب');
                const books = await res.json();
                const book = (Array.isArray(books) ? books : []).find(b => b && Number(b.id) === Number(BOOK_ID));
                if (!book || !book.pdf) throw new Error('لم يتم العثور على رابط PDF لهذا الكتاب');

                // عنوان الصفحة
                if (book.title) {
                    titleEl.textContent = `قراءة: ${book.title}`;
                    document.title = `${book.title} - قارئ PDF`;
                }

                // جلب الملف كـ ArrayBuffer لمنع التقاط IDM واستخدامه كـ Blob
                const pdfRes = await fetch(book.pdf, { cache: 'no-store', credentials: 'same-origin' });
                if (!pdfRes.ok) throw new Error(`تعذر تحميل ملف PDF (HTTP ${pdfRes.status})`);
                pdfBuffer = await pdfRes.arrayBuffer();

                // استخدام PDF.js لاستخراج عدد الصفحات فقط (دعم العربية عبر CMap والخطوط القياسية)
                if (!pdfjsLib) throw new Error('تعذر تحميل مكتبة PDF.js');
                const loadingTask = pdfjsLib.getDocument({
                    data: new Uint8Array(pdfBuffer),
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                    standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
                    enableXfa: true,
                    fontExtraProperties: true,
                });
                const pdfDoc = await loadingTask.promise;
                totalPages = pdfDoc.numPages || 0;
                pageCountSpan.textContent = `/ ${totalPages}`;

                // ابدأ من آخر صفحة محفوظة ضمن الحدود
                currentPage = Math.min(totalPages || 1, Math.max(1, currentPage || 1));
                pageNumInput.value = currentPage;

                updateIframe();
            } catch (e) {
                console.error(e);
                pageCountSpan.textContent = '/ 0';
                if (errorBox) {
                    errorBox.textContent = e.message || 'تعذر تحميل أو عرض الكتاب';
                    errorBox.classList.remove('hidden');
                }
            }
        }

        // أحداث التحكم
        prevBtn.addEventListener('click', showPrevPage);
        nextBtn.addEventListener('click', showNextPage);
        pageNumInput.addEventListener('change', () => jumpToPage(pageNumInput.value));
        window.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') {
                showNextPage();
            } else if (e.key === 'ArrowLeft') {
                showPrevPage();
            }
        });
        window.addEventListener('beforeunload', () => {
            saveProgress(currentPage);
            if (blobUrl) URL.revokeObjectURL(blobUrl);
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    </script>
</x-app-layout>
