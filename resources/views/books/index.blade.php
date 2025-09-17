<x-app-layout>
    <div class="container mx-auto px-4 py-8" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

        {{-- شبكة الكتب (تعبئة ديناميكية من JSON) --}}
        <div id="booksGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8"></div>

        {{-- زر تحميل المزيد --}}
        <div class="text-center mt-8">
            <button id="loadMoreBtn"
                class="bg-white border-2 border-purple-600 text-purple-600 hover:bg-purple-600 hover:text-white px-8 py-3 rounded-xl font-semibold transition">
                <i class="fas fa-plus ml-2"></i>
                تحميل المزيد من الكتب
            </button>
        </div>

        {{-- الإحصائيات السريعة --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8 mt-12">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-xl text-center">
                <i class="fas fa-book text-2xl mb-2"></i>
                <div class="text-2xl font-bold">1,247</div>
                <div class="text-sm opacity-80">إجمالي الكتب</div>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl text-center">
                <i class="fas fa-heart text-2xl mb-2"></i>
                <div class="text-2xl font-bold">156</div>
                <div class="text-sm opacity-80">كتب مفضلة</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl text-center">
                <i class="fas fa-bookmark text-2xl mb-2"></i>
                <div class="text-2xl font-bold">89</div>
                <div class="text-sm opacity-80">اقرأ لاحقاً</div>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-red-500 text-white p-6 rounded-xl text-center">
                <i class="fas fa-chart-line text-2xl mb-2"></i>
                <div class="text-2xl font-bold">45</div>
                <div class="text-sm opacity-80">قيد القراءة</div>
            </div>
        </div>

        {{-- مودال تفاصيل الكتاب --}}
        <div id="bookModal"
            class="fixed inset-0 modal-backdrop hidden z-50 p-4 flex items-center justify-center bg-black bg-opacity-50">
            <div
                class="max-w-4xl mx-auto bg-white dark:bg-gray-700 rounded-2xl shadow-2xl max-h-screen overflow-auto relative">
                <div class="flex justify-between items-center p-6 border-b">
                    <h2 class="font-bold text-gray-800 dark:text-white text-2xl">تفاصيل الكتاب</h2>
                    <button onclick="closeBookModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 grid md:grid-cols-2 gap-8">
                    <div>
                        <img id="modalBookCover" src="" alt="غلاف الكتاب"
                            class="w-full max-w-sm mx-auto rounded-xl shadow-lg mb-6">
                        <div class="space-y-3">
                            <div class="flex space-x-3">
                                <!-- زر بدء القراءة -->
                                <a id="modalReadLink" href="#"
                                    class="w-full inline-block text-center bg-purple-600 hover:bg-purple-700 text-white py-3 px-6 rounded-xl font-semibold transition">
                                    <i class="fas fa-play ml-2"></i>
                                    بدء القراءة
                                </a>

                                <!-- زر تنزيل الكتاب -->
                                <a id="modalDownloadLink" href="#" target="_blank"
                                    class="w-full inline-block text-center bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-xl font-semibold transition">
                                    <i class="fas fa-download ml-2"></i>
                                    تنزيل الكتاب
                                </a>
                            </div>

                            <!-- زر إضافة للمفضلة -->
                            <button
                                class="w-full border-2 border-purple-600 text-purple-600 hover:bg-purple-600 hover:text-white py-3 px-6 rounded-xl font-semibold transition">
                                <i class="fas fa-bookmark ml-2"></i>
                                إضافة للمفضلة
                            </button>
                        </div>

                    </div>
                    <div>
                        <h3 id="modalBookTitle" class="font-bold text-3xl mb-4 text-gray-800 dark:text-white"></h3>
                        <p id="modalBookAuthor" class="text-purple-600 font-semibold mb-4 text-lg"></p>
                        <p id="modalBookDescription" class="font-bold text-base mb-6 text-gray-600 dark:text-white"></p>
                        <div class="flex items-center mb-6">
                            <!-- نص تقييم الكتاب -->
                            <div class="font-bold text-gray-600 dark:text-white text-sm mr-2">تقييم الكتاب:</div>

                            <!-- التقييم مع النجوم -->
                            <div id="modalBookRating" class="flex text-yellow-400 text-xl">
                                <!-- النجوم ستظهر هنا -->
                            </div>

                            <span id="modalBookRatingValue"
                                class="font-bold text-sm text-gray-600 mr-3 dark:text-white"></span>

                            <!-- نص سنة النشر -->
                            <div class="font-bold text-gray-600 dark:text-white text-sm mr-2">سنة النشر:</div>
                            <span id="modalBookYear" class="font-bold text-sm text-gray-500 dark:text-white"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- سكريبت تحميل الكتب من JSON وعرضها --}}
    <script>
        const BOOKS_JSON_URL = '/assets/book/books.json';
        const PAGE_SIZE = 8;
        let allBooks = [];
        let renderedCount = 0;
        const booksMap = {};

        function starIcons(rating) {
            const full = Math.floor(rating || 0);
            const half = (rating - full) >= 0.5;
            const empty = 5 - full - (half ? 1 : 0);
            let html = '';
            for (let i = 0; i < full; i++) html += '<i class="fas fa-star"></i>';
            if (half) html += '<i class="fas fa-star-half-alt"></i>';
            for (let i = 0; i < empty; i++) html += '<i class="far fa-star"></i>';
            return html;
        }

        function createBookCard(book) {
            const wrapper = document.createElement('div');
            wrapper.className =
                'book-card bg-white dark:bg-gray-700 rounded-2xl p-6 shadow-lg border hover:border-purple-200 cursor-pointer flex flex-col justify-between min-h-[300px] space-y-4';
            wrapper.setAttribute('onclick', `openBookModal(${book.id})`);
            wrapper.innerHTML = `
                <div class="relative mb-4">
                    <img src="${book.image}" alt="غلاف الكتاب" class="book-cover w-full h-80 object-cover rounded-xl shadow-lg">
                    <button class="absolute top-2 left-2 bg-white/80 hover:bg-white text-red-500 w-8 h-8 rounded-full flex items-center justify-center transition">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <h3 class="font-bold text-lg mb-2 text-gray-800 line-clamp-2">${book.title}</h3>
                <p class="text-purple-600 font-semibold mb-2">${book.author || ''}</p>
                <p class="font-bold text-gray-600 dark:text-white text-sm mb-4 line-clamp-3">${book.description || ''}</p>
                <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                        <!-- تقييم الكتاب -->
                        <div class="font-bold text-gray-600 dark:text-white text-sm mr-2">تقييم الكتاب:</div>
                        <div class="flex font-bold text-yellow-400 dark:text-white">
                            ${starIcons(book.rating || 0)}
                        </div>
                        <span class="font-bold text-gray-600 dark:text-white text-sm mr-2">(${(book.rating || 0).toFixed ? (book.rating || 0).toFixed(1) : Number(book.rating || 0).toFixed(1)})</span>
                    </div>

                    <!-- سنة النشر -->
                    <!--
                    <div class="font-bold text-gray-600 dark:text-white text-sm mr-2">سنة النشر:</div>
                    <span class="font-bold text-gray-500 dark:text-white text-sm mr-2">${book.publishedYear || ''}</span>
                    -->
                    <!-- سنة الكتاب -->
                    <div class="font-bold text-gray-600 dark:text-white text-sm mr-2">سنة النشر:</div>
                    <span class="font-bold text-gray-500 dark:text-white text-sm">${book.year || ''}</span>
                </div>

                <div class="flex space-x-reverse space-x-2">
                    <a href="${book.pdf}" target="_blank" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-xl font-semibold transition">
                        <i class="fas fa-book-open ml-2"></i>
                        قراءة المزيد
                    </a>
                    <button class="p-3 border-2 border-gray-200 hover:border-purple-300 rounded-xl text-gray-600 hover:text-purple-600 transition">
                        <i class="fas fa-bookmark"></i>
                    </button>
                </div>
            `;
            return wrapper;
        }

        function renderNextPage() {
            const grid = document.getElementById('booksGrid');
            const next = allBooks.slice(renderedCount, renderedCount + PAGE_SIZE);
            next.forEach(book => {
                grid.appendChild(createBookCard(book));
            });
            renderedCount += next.length;
            const btn = document.getElementById('loadMoreBtn');
            if (renderedCount >= allBooks.length) {
                btn.classList.add('hidden');
            } else {
                btn.classList.remove('hidden');
            }
        }

        async function loadBooks() {
            try {
                const res = await fetch(BOOKS_JSON_URL, {
                    cache: 'no-cache'
                });
                if (!res.ok) throw new Error('فشل تحميل ملف الكتب');
                const data = await res.json();
                if (!Array.isArray(data)) throw new Error('صيغة ملف JSON غير صحيحة');
                allBooks = data;
                // جهز خريطة للوصول السريع بالمعرف
                data.forEach(b => {
                    if (b && b.id != null) booksMap[b.id] = b;
                });
                renderedCount = 0;
                document.getElementById('booksGrid').innerHTML = '';
                renderNextPage();
            } catch (e) {
                console.error(e);
                document.getElementById('booksGrid').innerHTML = `
                    <div class="col-span-full bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                        حدث خطأ أثناء تحميل الكتب. تأكد من وجود الملف <code>/assets/book/books.json</code> وصحة الصيغة.
                    </div>
                `;
                document.getElementById('loadMoreBtn').classList.add('hidden');
            }
        }

        function openBookModal(bookId) {
            const book = booksMap[bookId];
            if (!book) return;

            document.getElementById('modalBookCover').src = book.image;
            document.getElementById('modalBookTitle').textContent = book.title || '';
            document.getElementById('modalBookAuthor').textContent = book.author || '';
            document.getElementById('modalBookDescription').textContent = book.description || '';
            document.getElementById('modalBookRatingValue').textContent = `(${Number(book.rating || 0).toFixed(1)})`;
            document.getElementById('modalBookYear').textContent = book.year || '';

            let ratingContainer = document.getElementById('modalBookRating');
            ratingContainer.innerHTML = starIcons(Number(book.rating || 0));

            const readLink = document.getElementById('modalReadLink');
            const downloadLink = document.getElementById('modalDownloadLink');
            // توجيه لصفحة القارئ الجديدة حتى نحفظ التقدم
            readLink.href = `/${'{{ app()->getLocale() }}'}/books/${book.id}/read`;
            downloadLink.href = book.pdf || '#';

            document.getElementById('bookModal').classList.remove('hidden');
        }

        function closeBookModal() {
            document.getElementById('bookModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadBooks();
            document.getElementById('loadMoreBtn').addEventListener('click', renderNextPage);
            document.getElementById('bookModal').addEventListener('click', e => {
                if (e.target.id === 'bookModal') closeBookModal();
            });
        });
        // دالة لإنشاء النجوم باستخدام Unicode (لا تحتاج لمكتبة خارجية)
        function starIcons(rating) {
            const fullStars = Math.floor(rating); // النجوم المكتملة
            const hasHalfStar = rating % 1 >= 0.5; // نجمة نصف إذا كان الكسر 0.5 أو أكثر
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0); // النجوم الفارغة

            let stars = '';

            // النجوم المكتملة (ذهبية)
            for (let i = 0; i < fullStars; i++) {
                stars += '<span class="text-yellow-400">★</span>';
            }

            // النجمة النصف إذا وجدت
            if (hasHalfStar) {
                stars += '<span class="text-yellow-400">☆</span>'; // أو يمكن استخدام ★ بشفافية
            }

            // النجوم الفارغة
            for (let i = 0; i < emptyStars; i++) {
                stars += '<span class="text-gray-300">☆</span>';
            }

            return stars;
        }

        // نسخة محسنة مع Font Awesome (إذا كانت متوفرة)
        function starIconsFA(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

            let stars = '';

            // النجوم المكتملة
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star text-yellow-400"></i>';
            }

            // النجمة النصف
            if (hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
            }

            // النجوم الفارغة
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star text-gray-300 dark:text-gray-600"></i>';
            }

            return stars;
        }

        // الكود المحدث لعرض التقييم
        const ratingHTML = `
            <div class="flex items-center">
                <div class="flex font-bold text-yellow-400 dark:text-white">
                    ${starIcons(book.rating || 0)}
                </div>
                <span class="font-bold text-gray-600 dark:text-white text-sm mr-2 ml-1">
                    (${(book.rating || 0).toFixed ? (book.rating || 0).toFixed(1) : Number(book.rating || 0).toFixed(1)})
                </span>
            </div>`;

        // إذا كنت تريد عرض النجوم فقط بدون الرقم:
        const starsOnlyHTML = `
            <div class="flex items-center">
                <div class="flex font-bold">
                    ${starIcons(book.rating || 0)}
                </div>
            </div>`;

        // مثال للاستخدام مع كتاب له تقييم 4.3
        const book = {
            rating: 4.3
        };
        console.log(starIcons(book.rating)); // سيعرض 4 نجوم مكتملة ونصف نجمة ونجمة فارغة
    </script>
</x-app-layout>
