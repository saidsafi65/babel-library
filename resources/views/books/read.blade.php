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
                        <input id="pageNum" type="number" class="w-20 px-2 py-1 rounded-lg border dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" min="1" value="{{ (int) ($lastPage ?? 1) }}">
                        <span id="pageCount" class="text-sm text-gray-600 dark:text-gray-300">/ <span id="totalPages">0</span></span>
                    </div>
                </div>

                <!-- معلومات الحفظ والتحميل -->
                <div class="flex items-center gap-2">
                    <div id="loadingIndicator" class="hidden">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600"></div>
                    </div>
                    <span id="saveStatus" class="text-sm text-gray-500 dark:text-gray-400">جاري التحميل...</span>
                </div>
            </div>

            <!-- عارض PDF -->
            <div id="pdfViewer" class="w-full bg-gray-100 dark:bg-gray-900 rounded-lg border flex items-center justify-center" style="min-height:70vh;">
                <div class="text-gray-500 dark:text-gray-400">جاري تحميل الكتاب...</div>
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
            PDF_URL: "{{ route('books.book.pdf', $bookId) }}",
            SAVE_DEBOUNCE_TIME: 600,
            MAX_RETRIES: 3
        };

        // إعداد PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // عناصر DOM
        const elements = {
            prevBtn: document.getElementById('prevPage'),
            nextBtn: document.getElementById('nextPage'),
            pageNumInput: document.getElementById('pageNum'),
            totalPagesSpan: document.getElementById('totalPages'),
            saveStatus: document.getElementById('saveStatus'),
            titleEl: document.getElementById('bookTitle'),
            pdfViewer: document.getElementById('pdfViewer'),
            errorBox: document.getElementById('errorBox'),
            loadingIndicator: document.getElementById('loadingIndicator')
        };

        // متغيرات الحالة
        let state = {
            pdf: null,
            currentPage: Math.max(1, CONFIG.LAST_PAGE || 1),
            totalPages: 0,
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

        function updateUI() {
            elements.pageNumInput.value = state.currentPage;
            elements.totalPagesSpan.textContent = state.totalPages;
            updateButtonStates();
        }

        // عرض الصفحة
        async function renderPage(pageNumber) {
            try {
                showLoading(true);
                const page = await state.pdf.getPage(pageNumber);
                
                // حساب المقياس بناء على عرض الحاوية
                const viewport = page.getViewport({ scale: 1 });
                const containerWidth = elements.pdfViewer.clientWidth - 40;
                const scale = Math.min(containerWidth / viewport.width, 2);
                const scaledViewport = page.getViewport({ scale });

                // إنشاء canvas
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;
                canvas.style.maxWidth = '100%';
                canvas.style.height = 'auto';
                canvas.className = 'shadow-lg rounded-lg';

                // عرض الصفحة
                await page.render({
                    canvasContext: context,
                    viewport: scaledViewport
                }).promise;

                // تحديث المحتوى
                elements.pdfViewer.innerHTML = '';
                elements.pdfViewer.appendChild(canvas);
                
                updateUI();
                elements.saveStatus.textContent = `صفحة ${pageNumber} من ${state.totalPages}`;
                
            } catch (error) {
                console.error('خطأ في عرض الصفحة:', error);
                showError('خطأ في عرض الصفحة');
                elements.saveStatus.textContent = 'خطأ في العرض';
            } finally {
                showLoading(false);
            }
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
            }
        }

        // دوال التنقل
        async function showPrevPage() {
            if (state.currentPage <= 1) return;
            state.currentPage--;
            await renderPage(state.currentPage);
            debounceSave(state.currentPage);
        }

        async function showNextPage() {
            if (state.currentPage >= state.totalPages) return;
            state.currentPage++;
            await renderPage(state.currentPage);
            debounceSave(state.currentPage);
        }

        async function jumpToPage(num) {
            const pageNum = Math.min(state.totalPages, Math.max(1, parseInt(num || 1)));
            if (pageNum === state.currentPage) return;

            state.currentPage = pageNum;
            await renderPage(state.currentPage);
            debounceSave(state.currentPage);
        }

        // تهيئة التطبيق
        async function initializeApp() {
            try {
                hideError();
                showLoading(true);
                elements.saveStatus.textContent = 'جاري تحميل الكتاب...';

                // تحميل PDF مباشرة
                const loadingTask = pdfjsLib.getDocument({
                    url: CONFIG.PDF_URL,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true,
                    standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
                    withCredentials: true
                });

                state.pdf = await loadingTask.promise;
                state.totalPages = state.pdf.numPages;
                state.currentPage = Math.min(CONFIG.LAST_PAGE, state.totalPages);

                elements.pageNumInput.max = state.totalPages;
                elements.titleEl.textContent = `قراءة الكتاب - ${state.totalPages} صفحة`;

                // عرض الصفحة الحالية
                await renderPage(state.currentPage);
                
            } catch (error) {
                console.error('خطأ في تحميل PDF:', error);
                showError('تعذر تحميل الكتاب. تأكد من وجود الملف.');
                elements.saveStatus.textContent = 'فشل التحميل';
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

            // تحديث الحجم عند تغيير حجم النافذة
            window.addEventListener('resize', () => {
                if (state.pdf && state.currentPage) {
                    renderPage(state.currentPage);
                }
            });

            // حفظ عند إغلاق الصفحة
            window.addEventListener('beforeunload', () => {
                if (state.saveTimer) {
                    clearTimeout(state.saveTimer);
                    saveProgress(state.currentPage);
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