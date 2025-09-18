<x-app-layout>
    <div class="container mx-auto px-4 py-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="flex items-center justify-between mb-4">
            <h1 id="bookTitle" class="text-2xl font-bold text-gray-800 dark:text-gray-100">قارئ PDF</h1>
            <a href="{{ route('books.index') }}" class="text-purple-600 hover:text-purple-800 font-semibold">العودة لقائمة الكتب</a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
            <div id="errorBox" class="mb-3 hidden text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800"></div>

            <!-- شريط التحكم -->
            <div class="flex items-center justify-between mb-4 gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">السابق</button>
                    <button id="nextPage" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">التالي</button>
                    <div class="flex items-center gap-2 mx-2">
                        <span class="text-sm text-gray-600 dark:text-gray-300">الصفحة:</span>
                        <input id="pageNum" type="number" class="w-20 px-2 py-1 rounded-lg border dark:bg-gray-700 dark:border-gray-600" min="1" value="{{ (int) ($lastPage ?? 1) }}">
                        <span id="pageCount" class="text-sm text-gray-600 dark:text-gray-300">/ <span id="totalPages">?</span></span>
                    </div>
                </div>

                <!-- معلومات الحفظ والتحميل -->
                <div class="flex items-center gap-2">
                    <div id="loadingIndicator" class="hidden">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600"></div>
                    </div>
                    <span id="saveStatus" class="text-sm text-gray-500">لم يتم الحفظ بعد</span>
                </div>
            </div>

            <!-- عارض PDF -->
            <div id="viewerWrapper" class="w-full overflow-hidden bg-gray-100 dark:bg-gray-900 rounded-lg border" style="min-height:70vh;">
                <iframe id="pdfIframe"
                        src="{{ route('books.book.pdf', $bookId) }}#page={{ $lastPage }}"
                        class="w-full h-full"
                        style="min-height:70vh; border:0;"
                        referrerpolicy="no-referrer"
                        loading="lazy">
                </iframe>
            </div>
        </div>
    </div>

    <!-- PDF.js للتحكم في الصفحات -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // متغيرات الإعداد
        const CONFIG = {
            BOOK_ID: {{ (int) ($bookId ?? 0) }},
            LAST_PAGE: {{ (int) ($lastPage ?? 1) }},
            LOCALE: "{{ app()->getLocale() }}",
            CSRF_TOKEN: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            BOOKS_JSON_URL: '/assets/book/books.json',
            SAVE_DEBOUNCE_TIME: 600,
            MAX_RETRIES: 3
        };

        // عناصر DOM
        const elements = {
            prevBtn: document.getElementById('prevPage'),
            nextBtn: document.getElementById('nextPage'),
            pageNumInput: document.getElementById('pageNum'),
            pageCountSpan: document.getElementById('pageCount'),
            totalPagesSpan: document.getElementById('totalPages'),
            saveStatus: document.getElementById('saveStatus'),
            titleEl: document.getElementById('bookTitle'),
            iframe: document.getElementById('pdfIframe'),
            errorBox: document.getElementById('errorBox'),
            loadingIndicator: document.getElementById('loadingIndicator')
        };

        // إعداد PDF.js
        const pdfjsLib = window['pdfjsLib'] || window['pdfjs-dist/build/pdf'];
        if (pdfjsLib?.GlobalWorkerOptions) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        // متغيرات الحالة
        let state = {
            currentPage: Math.max(1, CONFIG.LAST_PAGE || 1),
            totalPages: 0,
            pdfBuffer: null,
            blobUrl: null,
            saveTimer: null,
            retryCount: 0
        };

        // دوال المساعدة
        function showError(message) {
            if (elements.errorBox) {
                elements.errorBox.textContent = message;
                elements.errorBox.classList.remove('hidden');
            }
            console.error('خطأ في قارئ PDF:', message);
        }

        function hideError() {
            if (elements.errorBox) {
                elements.errorBox.classList.add('hidden');
            }
        }

        function showLoading(show = true) {
            if (elements.loadingIndicator) {
                elements.loadingIndicator.classList.toggle('hidden', !show);
            }
        }

        function updateButtonStates() {
            if (elements.prevBtn) {
                elements.prevBtn.disabled = state.currentPage <= 1;
            }
            if (elements.nextBtn) {
                elements.nextBtn.disabled = state.currentPage >= state.totalPages;
            }
        }

        function updateIframe() {
            if (!state.blobUrl && state.pdfBuffer) {
                const blob = new Blob([state.pdfBuffer], { type: 'application/pdf' });
                state.blobUrl = URL.createObjectURL(blob);
            }

            if (state.blobUrl) {
                const params = `#page=${state.currentPage}&zoom=page-width&toolbar=0&navpanes=0&scrollbar=0&statusbar=0&messages=0&view=FitH`;
                elements.iframe.src = state.blobUrl + params;
            }

            updateButtonStates();
        }

        // حفظ التقدم مع معالجة الأخطاء
        function debounceSave(page) {
            if (state.saveTimer) clearTimeout(state.saveTimer);
            state.saveTimer = setTimeout(() => saveProgress(page), CONFIG.SAVE_DEBOUNCE_TIME);
        }

        async function saveProgress(page, retryCount = 0) {
            if (!CONFIG.CSRF_TOKEN) {
                elements.saveStatus.textContent = 'لم يتم تسجيل الدخول';
                return;
            }

            try {
                showLoading(true);
                elements.saveStatus.textContent = 'جاري الحفظ...';

                const response = await fetch(`/${CONFIG.LOCALE}/books/${CONFIG.BOOK_ID}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CONFIG.CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ page })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }

                const data = await response.json();
                elements.saveStatus.textContent = `تم الحفظ (صفحة ${page})`;
                state.retryCount = 0;

            } catch (error) {
                console.error('خطأ في الحفظ:', error);

                if (retryCount < CONFIG.MAX_RETRIES) {
                    setTimeout(() => saveProgress(page, retryCount + 1), 1000 * (retryCount + 1));
                    elements.saveStatus.textContent = `جاري المحاولة مرة أخرى... (${retryCount + 1}/${CONFIG.MAX_RETRIES})`;
                } else {
                    elements.saveStatus.textContent = 'تعذر الحفظ';
                }
            } finally {
                showLoading(false);
            }
        }

        // دوال التنقل
        function showPrevPage() {
            if (state.currentPage <= 1) return;
            state.currentPage--;
            elements.pageNumInput.value = state.currentPage;
            updateIframe();
            debounceSave(state.currentPage);
        }

        function showNextPage() {
            if (state.currentPage >= state.totalPages) return;
            state.currentPage++;
            elements.pageNumInput.value = state.currentPage;
            updateIframe();
            debounceSave(state.currentPage);
        }

        function jumpToPage(num) {
            const pageNum = Math.min(state.totalPages, Math.max(1, parseInt(num || 1)));
            if (pageNum === state.currentPage) return;

            state.currentPage = pageNum;
            elements.pageNumInput.value = state.currentPage;
            updateIframe();
            debounceSave(state.currentPage);
        }

        // تهيئة التطبيق
        async function initializeApp() {
            try {
                hideError();
                showLoading(true);

                // جلب بيانات الكتب
                const booksResponse = await fetch(CONFIG.BOOKS_JSON_URL, {
                    cache: 'no-cache',
                    headers: { 'Accept': 'application/json' }
                });

                if (!booksResponse.ok) {
                    throw new Error('تعذر تحميل قائمة الكتب');
                }

                const books = await booksResponse.json();
                const book = (Array.isArray(books) ? books : []).find(b =>
                    b && Number(b.id) === Number(CONFIG.BOOK_ID)
                );

                if (!book?.pdf) {
                    throw new Error('لم يتم العثور على رابط PDF لهذا الكتاب');
                }

                // تحديث العنوان
                if (book.title) {
                    elements.titleEl.textContent = `قراءة: ${book.title}`;
                    document.title = `${book.title} - قارئ PDF`;
                }

                // جلب ملف PDF
                const pdfResponse = await fetch(book.pdf, {
                    cache: 'no-store',
                    credentials: 'same-origin'
                });

                if (!pdfResponse.ok) {
                    throw new Error(`تعذر تحميل ملف PDF (HTTP ${pdfResponse.status})`);
                }

                state.pdfBuffer = await pdfResponse.arrayBuffer();

                // استخراج عدد الصفحات
                if (!pdfjsLib) {
                    throw new Error('تعذر تحميل مكتبة PDF.js');
                }

                const loadingTask = pdfjsLib.getDocument({
                    data: new Uint8Array(state.pdfBuffer),
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                    standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
                    enableXfa: true,
                    fontExtraProperties: true,
                });

                const pdfDoc = await loadingTask.promise;
                state.totalPages = pdfDoc.numPages || 0;
                elements.totalPagesSpan.textContent = state.totalPages;

                // تحديد الصفحة الحالية
                state.currentPage = Math.min(state.totalPages || 1, Math.max(1, state.currentPage || 1));
                elements.pageNumInput.value = state.currentPage;
                elements.pageNumInput.max = state.totalPages;

                updateIframe();

            } catch (error) {
                showError(error.message || 'تعذر تحميل أو عرض الكتاب');
                elements.totalPagesSpan.textContent = '0';
            } finally {
                showLoading(false);
            }
        }

        // ربط الأحداث
        function bindEvents() {
            elements.prevBtn?.addEventListener('click', showPrevPage);
            elements.nextBtn?.addEventListener('click', showNextPage);
            elements.pageNumInput?.addEventListener('change', (e) => jumpToPage(e.target.value));
            elements.pageNumInput?.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    jumpToPage(e.target.value);
                }
            });

            // اختصارات لوحة المفاتيح
            window.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT') return;

                switch (e.key) {
                    case 'ArrowRight':
                    case 'PageDown':
                        e.preventDefault();
                        showNextPage();
                        break;
                    case 'ArrowLeft':
                    case 'PageUp':
                        e.preventDefault();
                        showPrevPage();
                        break;
                }
            });

            // حفظ عند إغلاق الصفحة
            window.addEventListener('beforeunload', () => {
                if (state.saveTimer) {
                    clearTimeout(state.saveTimer);
                    saveProgress(state.currentPage);
                }
                if (state.blobUrl) {
                    URL.revokeObjectURL(state.blobUrl);
                }
            });
        }

        // بدء التطبيق
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                bindEvents();
                initializeApp();
            });
        } else {
            bindEvents();
            initializeApp();
        }
    </script>
</x-app-layout>
