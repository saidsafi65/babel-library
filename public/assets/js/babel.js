
/**
 * Babel Library - Interactive JavaScript
 * Handles language switching, animations, and user interactions
 */

document.addEventListener('DOMContentLoaded', function () {

    // Language Management
    class LanguageManager {
        constructor() {
            this.currentLang = localStorage.getItem('preferred-language') || 'ar';
            this.init();
        }

        init() {
            this.setLanguage(this.currentLang);
            this.setupToggleButton();
        }

        setLanguage(lang) {
            const html = document.documentElement;
            const body = document.body;

            html.setAttribute('lang', lang);
            html.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');

            // Update toggle button text
            const toggleBtn = document.getElementById('lang-toggle');
            if (toggleBtn) {
                toggleBtn.textContent = lang === 'ar' ? 'English' : 'العربية';
            }

            // Save preference
            localStorage.setItem('preferred-language', lang);
            this.currentLang = lang;

            // Trigger custom event for other components
            document.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
        }

        setupToggleButton() {
            const toggleBtn = document.getElementById('lang-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const newLang = this.currentLang === 'ar' ? 'en' : 'ar';
                    this.setLanguage(newLang);
                });
            }
        }

        toggle() {
            const newLang = this.currentLang === 'ar' ? 'en' : 'ar';
            this.setLanguage(newLang);
        }
    }

    // Theme Management
    class ThemeManager {
        constructor() {
            this.currentTheme = localStorage.getItem('preferred-theme') || 'light';
            this.init();
        }

        init() {
            this.applyTheme(this.currentTheme);
            this.setupToggleButton();
        }

        applyTheme(theme) {
            const html = document.documentElement;

            if (theme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }

            localStorage.setItem('preferred-theme', theme);
            this.currentTheme = theme;
        }

        setupToggleButton() {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    this.toggle();
                });
            }
        }

        toggle() {
            const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.applyTheme(newTheme);
        }
    }

    // Animation Controller
    class AnimationController {
        constructor() {
            this.setupScrollAnimations();
            this.setupNavbarEffects();
            this.setupParallax();
        }

        setupScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        entry.target.style.animationDelay = `${Math.random() * 0.3}s`;
                    }
                });
            }, observerOptions);

            // Observe all elements that should animate on scroll
            document.querySelectorAll('.card-hover, .text-center').forEach(el => {
                observer.observe(el);
            });
        }

        setupNavbarEffects() {
            const navbar = document.querySelector('nav');
            let lastScrollY = window.scrollY;

            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;

                if (currentScrollY > 100) {
                    navbar.classList.add('navbar-blur', 'shadow-xl');
                } else {
                    navbar.classList.remove('navbar-blur', 'shadow-xl');
                }

                // Hide/show navbar on scroll
                if (currentScrollY > lastScrollY && currentScrollY > 200) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }

                lastScrollY = currentScrollY;
            }, { passive: true });
        }

        setupParallax() {
            const heroSection = document.querySelector('.hero-gradient');
            if (heroSection) {
                window.addEventListener('scroll', () => {
                    const scrolled = window.pageYOffset;
                    const rate = scrolled * -0.5;
                    heroSection.style.transform = `translate3d(0, ${rate}px, 0)`;
                }, { passive: true });
            }
        }
    }

    // Form Enhancements
    class FormEnhancer {
        constructor() {
            this.setupFormValidation();
            this.setupLoadingStates();
        }

        setupFormValidation() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, textarea, select');

                inputs.forEach(input => {
                    input.addEventListener('blur', () => this.validateField(input));
                    input.addEventListener('input', () => this.clearErrors(input));
                });

                form.addEventListener('submit', (e) => this.handleSubmit(e, form));
            });
        }

        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            let errorMessage = '';

            // Required field validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = field.dataset.errorRequired || 'هذا الحقل مطلوب';
            }

            // Email validation
            if (type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'يرجى إدخال بريد إلكتروني صحيح';
                }
            }

            // Password validation
            if (type === 'password' && value) {
                if (value.length < 8) {
                    isValid = false;
                    errorMessage = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
                }
            }

            this.showFieldError(field, isValid, errorMessage);
            return isValid;
        }

        showFieldError(field, isValid, message) {
            const errorElement = field.parentNode.querySelector('.field-error');

            if (!isValid) {
                field.classList.add('border-red-500', 'bg-red-50');
                field.classList.remove('border-green-500', 'bg-green-50');

                if (!errorElement) {
                    const error = document.createElement('div');
                    error.className = 'field-error text-red-500 text-sm mt-1 arabic';
                    error.textContent = message;
                    field.parentNode.appendChild(error);
                }
            } else {
                field.classList.remove('border-red-500', 'bg-red-50');
                field.classList.add('border-green-500', 'bg-green-50');

                if (errorElement) {
                    errorElement.remove();
                }
            }
        }

        clearErrors(field) {
            field.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50');
            const errorElement = field.parentNode.querySelector('.field-error');
            if (errorElement) {
                errorElement.remove();
            }
        }

        setupLoadingStates() {
            const buttons = document.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    if (button.form && button.form.checkValidity()) {
                        this.showLoading(button);
                    }
                });
            });
        }

        showLoading(button) {
            const originalText = button.innerHTML;
            button.innerHTML = `
                <span class="loading-animation"></span>
                <span class="mr-2 rtl:ml-2 rtl:mr-0">جاري التحميل...</span>
            `;
            button.disabled = true;

            // Reset after 3 seconds (in case of no response)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 10000);
        }

        handleSubmit(e, form) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isFormValid = true;

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isFormValid = false;
                }
            });

            if (!isFormValid) {
                e.preventDefault();
                this.showNotification('يرجى تصحيح الأخطاء في النموذج', 'error');
            }
        }

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `
                fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm
                ${type === 'error' ? 'bg-red-500 text-white' :
                    type === 'success' ? 'bg-green-500 text-white' :
                        'bg-blue-500 text-white'}
                transform transition-transform duration-300 translate-x-full
            `;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="arabic">${message}</span>
                    <button class="ml-2 rtl:mr-2 rtl:ml-0" onclick="this.parentElement.parentElement.remove()">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 5000);
        }
    }

    // Utility Functions
    class Utils {
        static smoothScrollTo(target, duration = 1000) {
            const targetElement = document.querySelector(target);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        static debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        static throttle(func, limit) {
            let inThrottle;
            return function () {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    }

    // Initialize all components
    const languageManager = new LanguageManager();
    const themeManager = new ThemeManager();
    const animationController = new AnimationController();
    const formEnhancer = new FormEnhancer();

    // Global functions for external access
    window.BabelLibrary = {
        language: languageManager,
        theme: themeManager,
        animations: animationController,
        forms: formEnhancer,
        utils: Utils
    };

    // Setup smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            Utils.smoothScrollTo(this.getAttribute('href'));
        });
    });

    // Performance optimization: Lazy load images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Add loading state to page
    window.addEventListener('load', () => {
        document.body.classList.add('loaded');

        // Hide any loading spinners
        document.querySelectorAll('.page-loader').forEach(loader => {
            loader.style.display = 'none';
        });
    });

    console.log('🚀 Babel Library initialized successfully!');
});