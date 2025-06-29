document.addEventListener('DOMContentLoaded', function () {
    const resources = {
        en: {
            translation: {
                title: {
                    login: "Login",
                    register: "Register"
                },
                login: {
                    email: "Email",
                    password: "Password",
                    submit: "Sign in",
                    no_account: "Don't have an account?",
                    register: "Register"
                },
                register: {
                    name: "Full name",
                    email: "Email",
                    password: "Password",
                    submit: "Signup",
                    have_account: "Already registered?",
                    login: "Login"
                }
            }
        },
        bn: {
            translation: {
                title: {
                    login: "লগইন",
                    register: "নিবন্ধন"
                },
                login: {
                    email: "ইমেইল",
                    password: "পাসওয়ার্ড",
                    submit: "প্রবেশ করুন",
                    no_account: "অ্যাকাউন্ট নেই?",
                    register: "নিবন্ধন করুন"
                },
                register: {
                    name: "সম্পূর্ণ নাম",
                    email: "ইমেইল",
                    password: "পাসওয়ার্ড",
                    submit: "সাইন আপ",
                    have_account: "ইতিমধ্যে নিবন্ধিত?",
                    login: "লগইন করুন"
                }
            }
        }
    };

    // Load saved language from localStorage or fallback to 'en'
    const savedLang = localStorage.getItem('lang') || 'en';

    i18next.init({
        lng: savedLang,
        debug: false,
        resources: resources
    }, function(err, t) {
        updateContent();
        updateToggleButton();
    });

    function updateContent() {
        document.querySelectorAll('[data-i18n]').forEach(function(element) {
            const key = element.getAttribute('data-i18n');
            element.innerHTML = i18next.t(key);
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(function(element) {
            const key = element.getAttribute('data-i18n-placeholder');
            element.setAttribute('placeholder', i18next.t(key));
        });
    }

    function updateToggleButton() {
        const langToggleBtn = document.getElementById('lang-toggle');
        if (langToggleBtn) {
            langToggleBtn.innerText = i18next.language.toUpperCase();
        }
    }

    // Language toggle button event
    const langToggleBtn = document.getElementById('lang-toggle');
    if (langToggleBtn) {
        langToggleBtn.addEventListener('click', function() {
            const currentLang = i18next.language;
            const newLang = currentLang === 'en' ? 'bn' : 'en';
            i18next.changeLanguage(newLang, () => {
                updateContent();
                updateToggleButton();
                localStorage.setItem('lang', newLang);  // Save new language
            });
        });
    }
});
