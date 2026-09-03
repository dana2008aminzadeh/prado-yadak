<?php
// تنظیم تایتل صفحه برای head.php
$current_page = '/login';
require_once BASE_PATH . '/assets/php/head.php';
?>
<body class="bg-brand-dark text-white font-sans antialiased min-h-screen flex flex-col">
    
    <!-- هدر سایت -->
    <?php require_once BASE_PATH . '/assets/php/header.php'; ?>
    
    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            
            <!-- باکس نمایش خطاها و پیام‌های موفقیت -->
            <div id="alert-box" class="hidden text-xs p-3 rounded-xl mb-6 text-center transition-all duration-300"></div>

            <div class="text-center mb-8">
                <h2 class="text-2xl font-black text-white mb-2">ورود / ثبت‌نام</h2>
                <p class="text-xs text-gray-400">برای ادامه، شماره موبایل خود را وارد کنید.</p>
            </div>

            <!-- ============================================== -->
            <!-- مرحله ۱: دریافت شماره موبایل -->
            <!-- ============================================== -->
            <div id="step-phone" class="block space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 mb-2">شماره موبایل</label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" style="width:18px;height:18px;"></i>
                        <input type="tel" id="auth-phone" class="w-full bg-black/20 border border-white/10 rounded-xl py-3 pr-10 pl-4 text-white text-sm focus:border-brand-red focus:ring-1 focus:ring-brand-red outline-none transition text-left" placeholder="09123456789" dir="ltr">
                    </div>
                </div>
                <button onclick="handleCheckPhone(event)" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition text-sm shadow-[0_4px_15px_rgba(225,6,0,0.2)]">
                    مرحله بعد
                </button>
            </div>

            <!-- ============================================== -->
            <!-- مرحله ۲: ورود با رمز عبور (مخصوص کاربران قدیمی) -->
            <!-- ============================================== -->
            <div id="step-password" class="hidden space-y-4">
                <div class="flex items-center justify-between bg-black/20 p-3 rounded-xl border border-white/5 mb-4">
                    <span id="display-phone-pass" class="font-mono text-brand-accent text-sm"></span>
                    <button onclick="location.reload()" class="text-xs text-gray-400 hover:text-white transition">تغییر شماره</button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 mb-2">رمز عبور</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" style="width:18px;height:18px;"></i>
                        <input type="password" id="auth-password" class="w-full bg-black/20 border border-white/10 rounded-xl py-3 pr-10 pl-10 text-white text-sm focus:border-brand-red focus:ring-1 focus:ring-brand-red outline-none transition text-left" placeholder="رمز عبور خود را وارد کنید" dir="ltr">
                        <button type="button" onclick="togglePasswordVisibility('auth-password', this)" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition">
                            <i data-lucide="eye" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                </div>
                <button onclick="handleLoginPassword(event)" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition text-sm shadow-[0_4px_15px_rgba(225,6,0,0.2)]">
                    ورود به حساب
                </button>
                <div class="text-center pt-2">
                    <button onclick="switchToOtpLogin()" class="text-xs text-gray-400 hover:text-white transition">فراموشی رمز / ورود با کد یکبار مصرف</button>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- مرحله ۳: ورود با پیامک / ثبت‌نام کاربر جدید -->
            <!-- ============================================== -->
            <div id="step-otp" class="hidden space-y-4">
                <div class="flex items-center justify-between bg-black/20 p-3 rounded-xl border border-white/5 mb-4">
                    <span id="display-phone-otp" class="font-mono text-brand-accent text-sm"></span>
                    <button onclick="location.reload()" class="text-xs text-gray-400 hover:text-white transition">تغییر شماره</button>
                </div>
                
                <!-- این بخش فقط برای کاربران جدید (ثبت‌نام) نمایش داده می‌شود -->
                <div id="new-user-fields" class="hidden space-y-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2">نام و نام خانوادگی</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" style="width:18px;height:18px;"></i>
                            <input type="text" id="auth-fullname" class="w-full bg-black/20 border border-white/10 rounded-xl py-3 pr-10 pl-4 text-white text-sm focus:border-brand-red outline-none transition" placeholder="مثال: علی محمدی">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2">تعیین رمز عبور</label>
                        <div class="relative">
                            <i data-lucide="key" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" style="width:18px;height:18px;"></i>
                            <input type="password" id="auth-new-password" class="w-full bg-black/20 border border-white/10 rounded-xl py-3 pr-10 pl-10 text-white text-sm focus:border-brand-red outline-none transition text-left" dir="ltr" placeholder="یک رمز عبور برای خریدهای بعدی وارد کنید">
                            <button type="button" onclick="togglePasswordVisibility('auth-new-password', this)" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition">
                                <i data-lucide="eye" style="width:18px;height:18px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- فیلد مشترک کد تایید -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 mb-2">کد ۵ رقمی پیامک شده</label>
                    <input type="text" id="auth-otp-code" class="w-full bg-black/20 border border-white/10 rounded-xl py-3 px-4 text-center text-white text-lg tracking-[0.5em] font-mono focus:border-brand-red focus:ring-1 focus:ring-brand-red outline-none transition" placeholder="- - - - -" maxlength="5" dir="ltr">
                </div>
                
                <button onclick="handleVerifyOtp(event)" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition text-sm shadow-[0_4px_15px_rgba(225,6,0,0.2)]">
                    تایید و ادامه
                </button>
                
                <div class="flex items-center justify-between text-xs pt-2">
                    <span id="timer-count" class="font-mono text-brand-red font-bold">01:59</span>
                    <button id="resend-otp-btn" onclick="requestOtp()" class="text-gray-500 hover:text-white transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>ارسال مجدد کد</button>
                </div>
            </div>

        </div>
    </main>

    <!-- فوتر سایت -->
    <?php require_once BASE_PATH . '/assets/php/footer.php'; ?>
    
    <!-- بارگذاری اسکریپت اصلی شامل کدهای داینامیک لاگین -->
    <script src="/assets/js/main.js"></script>
    <script>
        // اطمینان از ساخت آیکون‌های Lucide پس از رندر
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>