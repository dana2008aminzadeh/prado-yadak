<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">

    <?php include 'assets/php/header.php'; ?>

    <!-- بدنه اصلی صفحه اختصاصی ورود و ثبت‌نام -->
    <main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:py-16 flex items-center justify-center relative z-10 w-full">

        <!-- پس‌زمینه گرافیکی اتمسفریک -->
        <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-25">
            <div class="w-[500px] h-[500px] bg-brand-red/20 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-xl">
            <!-- کارت اصلی احراز هویت -->
            <div
                class="bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-10 shadow-[0_20px_60px_rgba(0,0,0,0.7)] relative overflow-hidden backdrop-blur-md">

                <!-- هدر کارت / لوگو -->
                <div class="text-center mb-8 space-y-2">
                    <div
                        class="w-16 h-16 bg-brand-red/10 border border-brand-red/30 rounded-2xl flex items-center justify-center mx-auto text-brand-red mb-3">
                        <i data-lucide="shield-check" style="width:36px;height:36px;"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white">ورود به سامانه کاربری پرادو یدک</h2>
                    <p class="text-xs text-gray-400">مدیریت سفارش‌ها، استعلام سریع شماره شاسی و تخفیف‌های ویژه مشتریان
                    </p>
                </div>

                <!-- سوییچ تب‌ها (ورود / ثبت‌نام / ورود با پیامک) -->
                <div
                    class="flex border-b border-white/10 mb-8 bg-brand-dark/50 p-1.5 rounded-2xl border border-white/5">
                    <button id="tab-login"
                        class="flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 bg-brand-red text-white shadow-lg"
                        onclick="switchTab('login')">
                        ورود با رمز عبور
                    </button>
                    <button id="tab-otp"
                        class="flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 text-gray-400 hover:text-white"
                        onclick="switchTab('otp')">
                        ورود سریع با پیامک
                    </button>
                    <button id="tab-signup"
                        class="flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 text-gray-400 hover:text-white"
                        onclick="switchTab('signup')">
                        ثبت‌نام جدید
                    </button>
                </div>

                <!-- پیام وضعیت / اعلان خطا یا موفقیت -->
                <div id="alert-box"
                    class="hidden p-4 rounded-xl text-xs font-bold mb-6 transition-all duration-300 flex items-center gap-2">
                </div>

                <!-- ================= ۱. فرم ورود با رمز عبور ================= -->
                <form id="form-login" class="space-y-5" onsubmit="handleLogin(event)">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-2 flex items-center gap-1.5">
                            <i data-lucide="phone" style="width:14px;height:14px;" class="text-brand-red"></i>
                            شماره موبایل یا ایمیل
                        </label>
                        <div class="relative">
                            <input type="text" id="login-identifier" required placeholder="مثال: 09189998852"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600"
                                style="direction: ltr;">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs">
                                <i data-lucide="user" style="width:16px;height:16px;"></i>
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-medium text-gray-300 flex items-center gap-1.5">
                                <i data-lucide="lock" style="width:14px;height:14px;" class="text-brand-red"></i>
                                رمز عبور
                            </label>
                            <a href="javascript:void(0)" onclick="switchTab('forgot')"
                                class="text-[11px] text-gray-400 hover:text-brand-red transition">
                                رمز عبور را فراموش کرده‌اید؟
                            </a>
                        </div>
                        <div class="relative">
                            <input type="password" id="login-password" required placeholder="••••••••"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600 pl-10"
                                style="direction: ltr;">
                            <button type="button" onclick="togglePasswordVisibility('login-password', this)"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition">
                                <i data-lucide="eye" style="width:18px;height:18px;"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer hover:text-white transition">
                            <input type="checkbox"
                                class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10">
                            مرا به خاطر بسپار
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs sm:text-sm transition shadow-[0_4px_20px_rgba(225,6,0,0.3)] flex items-center justify-center gap-2">
                        <i data-lucide="log-in" style="width:18px;height:18px;"></i> ورود به حساب کاربری
                    </button>
                </form>

                <!-- ================= ۲. فرم ورود سریع با OTP (کد یکبار مصرف) ================= -->
                <form id="form-otp" class="space-y-5 hidden" onsubmit="handleSendOTP(event)">
                    <div id="otp-step-1">
                        <label class="block text-xs font-medium text-gray-300 mb-2 flex items-center gap-1.5">
                            <i data-lucide="smartphone" style="width:14px;height:14px;" class="text-brand-red"></i>
                            شماره موبایل جهت دریافت کد تایید
                        </label>
                        <div class="relative">
                            <input type="tel" id="otp-phone" required placeholder="09189998852"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600"
                                style="direction: ltr;">
                        </div>
                        <p class="text-[11px] text-gray-500 mt-2">کد تایید ۵ رقمی از طریق پیامک برای این شماره ارسال
                            خواهد شد.</p>
                        <button type="submit"
                            class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs sm:text-sm transition mt-4 shadow-[0_4px_20px_rgba(225,6,0,0.3)] flex items-center justify-center gap-2">
                            <i data-lucide="send" style="width:16px;height:16px;"></i> ارسال کد تایید پیامکی
                        </button>
                    </div>

                    <div id="otp-step-2" class="hidden space-y-4">
                        <div class="text-center space-y-1">
                            <span class="text-xs text-gray-400">کد تایید به شماره <strong id="otp-sent-phone"
                                    class="text-white font-mono" style="direction: ltr;"></strong> ارسال شد.</span>
                            <button type="button" onclick="resetOTPStep()"
                                class="text-[11px] text-brand-red hover:underline block mx-auto">اصلاح شماره
                                موبایل</button>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-2 text-center">کد تایید ۵ رقمی را
                                وارد کنید</label>
                            <input type="text" id="otp-code" maxlength="5" placeholder="• • • • •"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-lg font-mono text-center tracking-[10px] text-white focus:outline-none focus:border-brand-red transition"
                                style="direction: ltr;">
                        </div>

                        <div class="flex justify-between items-center text-xs text-gray-400">
                            <span id="otp-timer">ارسال مجدد تا <strong class="text-brand-red font-mono"
                                    id="timer-count">01:59</strong></span>
                            <button type="button" id="resend-otp-btn" disabled onclick="handleSendOTP(event)"
                                class="text-gray-500 hover:text-white transition disabled:opacity-50 disabled:cursor-not-allowed">
                                ارسال مجدد کد
                            </button>
                        </div>

                        <button type="button" onclick="handleVerifyOTP()"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl text-xs sm:text-sm transition shadow-[0_4px_20px_rgba(16,185,129,0.3)] flex items-center justify-center gap-2">
                            <i data-lucide="check-circle" style="width:18px;height:18px;"></i> تایید و ورود به حساب
                        </button>
                    </div>
                </form>

                <!-- ================= ۳. فرم ثبت‌نام جدید ================= -->
                <form id="form-signup" class="space-y-4 hidden" onsubmit="handleSignup(event)">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">نام و نام خانوادگی</label>
                        <input type="text" id="signup-name" required placeholder="مثال: علی محمدی"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">شماره موبایل (جهت پیگیری
                            سفارشات)</label>
                        <input type="tel" id="signup-phone" required placeholder="09189998852"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600"
                            style="direction: ltr;">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">رمز عبور (حداقل ۶ کاراکتر)</label>
                        <div class="relative">
                            <input type="password" id="signup-password" minlength="6" required placeholder="••••••••"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600 pl-10"
                                style="direction: ltr;">
                            <button type="button" onclick="togglePasswordVisibility('signup-password', this)"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition">
                                <i data-lucide="eye" style="width:18px;height:18px;"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label
                            class="flex items-start gap-2 text-xs text-gray-400 cursor-pointer hover:text-white transition">
                            <input type="checkbox" required
                                class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10 mt-0.5">
                            <span>کلیه <a href="/terms" target="_blank"
                                    class="text-brand-red hover:underline font-bold">قوانین و شرایط ضمانت اصالت کالا</a>
                                تویوتای امین‌زاده را می‌پذیرم.</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs sm:text-sm transition shadow-[0_4px_20px_rgba(225,6,0,0.3)] flex items-center justify-center gap-2 mt-2">
                        <i data-lucide="user-plus" style="width:18px;height:18px;"></i> تکمیل ثبت‌نام و ساخت حساب
                    </button>
                </form>

                <!-- ================= ۴. بازیابی رمز عبور ================= -->
                <form id="form-forgot" class="space-y-4 hidden" onsubmit="handleForgot(event)">
                    <div class="text-center space-y-1 mb-4">
                        <h3 class="text-sm font-bold text-white">بازیابی رمز عبور</h3>
                        <p class="text-xs text-gray-400">شماره موبایل ثبت‌شده در حساب خود را وارد کنید.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">شماره موبایل</label>
                        <input type="tel" id="forgot-phone" required placeholder="09189998852"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red transition text-left placeholder:text-gray-600"
                            style="direction: ltr;">
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs sm:text-sm transition shadow-[0_4px_20px_rgba(225,6,0,0.3)] flex items-center justify-center gap-2">
                        ارسال لینک بازیابی رمز
                    </button>

                    <button type="button" onclick="switchTab('login')"
                        class="w-full text-xs text-gray-400 hover:text-white transition py-2">
                        بازگشت به فرم ورود
                    </button>
                </form>

            </div>
        </div>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>