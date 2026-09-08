<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <!-- محتوای اصلی -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:py-12 space-y-12 sm:space-y-16">

        <!-- باکس اصلی محصول -->
        <div id="product-container" class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- تصویر محصول -->
                <div class="md:w-5/12 flex items-center justify-center bg-brand-dark rounded-2xl p-8 border border-white/5 relative">
                    <?php
                    $images = $product['images'] ?? [];
                    $mainImage = !empty($images) ? "/image?id=" . e($images[0]) : "assets/logo/logo.webp";
                    ?>
                    <img src="<?= $mainImage ?>" alt="<?= e($product['name']) ?>" class="max-w-full max-h-96 object-contain drop-shadow-2xl">
                    
                    <?php if ($product['isGenuine']): ?>
                        <span class="absolute top-4 right-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-4 h-4"></i> جنیون پارت اصلی
                        </span>
                    <?php endif; ?>
                </div>

                <!-- اطلاعات محصول -->
                <div class="md:w-7/12 space-y-6 flex flex-col justify-center">
                    <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight"><?= e($product['name']) ?></h1>
                    
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-brand-dark text-gray-300 text-xs px-3 py-1.5 rounded-lg border border-white/5 font-mono">کد قطعه: <?= e($product['oem'] ?? 'ندارد') ?></span>
                        <span class="bg-brand-dark text-gray-300 text-xs px-3 py-1.5 rounded-lg border border-white/5">برند: <?= e(strtoupper($product['brand'] ?? 'نامشخص')) ?></span>
                        <span class="bg-brand-dark text-gray-300 text-xs px-3 py-1.5 rounded-lg border border-white/5">مدل: <?= e(strtoupper($product['model'] ?? 'عمومی')) ?></span>
                    </div>
                    
                    <p class="text-sm text-gray-400 leading-loose text-justify"><?= e($product['desc']) ?></p>

                    <div class="border-t border-white/10 pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-3xl font-black text-brand-red"><?= number_format($product['price']) ?> <span class="text-sm text-gray-500 font-bold">تومان</span></div>
                        
                        <?php if ($product['inStock']): ?>
                            <button onclick="addToCart(<?= $product['id'] ?>)" class="bg-brand-red hover:bg-red-700 text-white font-bold px-8 py-3.5 rounded-xl transition flex items-center gap-2 w-full sm:w-auto justify-center shadow-[0_4px_15px_rgba(225,6,0,0.2)]">
                                <i data-lucide="shopping-cart" class="w-5 h-5"></i> افزودن به سبد خرید
                            </button>
                        <?php else: ?>
                            <button disabled class="bg-brand-dark border border-white/10 text-gray-500 font-bold px-8 py-3.5 rounded-xl flex items-center gap-2 w-full sm:w-auto justify-center cursor-not-allowed">
                                <i data-lucide="x-circle" class="w-5 h-5"></i> ناموجود در انبار
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش خدمات آنلاین (اصالت‌سنجی و پیگیری سفارش) -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- کارت اصالت سنجی -->
            <div class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
                <h3 class="text-base sm:text-lg font-extrabold flex items-center gap-2.5">
                    <i data-lucide="shield-check" class="text-brand-red" style="width:22px;height:22px;"></i>
                    سامانه اصالت‌سنجی هوشمند قطعات
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">کد هولوگرام یا بارکد روی جعبه قطعه تویوتا را وارد کنید تا اصالت آن از انبار مرکزی امین‌زاده استعلام شود.</p>
                <div class="flex gap-2">
                    <input type="text" id="authenticity-code" placeholder="مثال: TOY-109283" class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition text-center font-mono placeholder:text-gray-600">
                    <button onclick="checkAuthenticity()" class="bg-brand-red hover:bg-red-700 text-white px-4 sm:px-5 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">استعلام اصالت</button>
                </div>
                <div id="authenticity-result" class="hidden text-xs p-3 rounded-xl border transition-all duration-300 font-bold"></div>
            </div>

            <!-- کارت پیگیری سفارش -->
            <div class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
                <h3 class="text-base sm:text-lg font-extrabold flex items-center gap-2.5">
                    <i data-lucide="truck" class="text-brand-red" style="width:22px;height:22px;"></i>
                    پیگیری سریع وضعیت سفارش مرسوله
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">کد سفارش خود را وارد کنید تا از وضعیت فرآیند بسته‌بندی، زمان تحویل به تیپاکس/باربری مطلع شوید.</p>
                <div class="flex gap-2">
                    <input type="text" id="tracking-code" placeholder="مثال: 403192" class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition text-center font-mono placeholder:text-gray-600">
                    <button onclick="trackOrder()" class="bg-white text-brand-dark hover:bg-gray-200 px-4 sm:px-5 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">پیگیری قطعه</button>
                </div>
                <div id="tracking-result" class="hidden text-xs p-3 rounded-xl border transition-all duration-300 font-bold"></div>
            </div>
        </section>

        <!-- بخش نظرات و امتیازدهی قطعه -->
        <section class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-8 space-y-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                        نظرات و امتیاز کاربران
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">امتیازدهی و ثبت نظر برای این کالا</p>
                </div>
                <div class="flex items-center gap-3 bg-brand-dark/50 border border-white/5 px-4 py-2 rounded-xl self-stretch sm:self-auto justify-between">
                    <?php 
                    $avgRating = 5.0; 
                    $commentCount = count($comments);
                    if ($commentCount > 0) {
                        $sum = 0;
                        foreach($comments as $c) $sum += $c['rating'];
                        $avgRating = round($sum / $commentCount, 1);
                    }
                    ?>
                    <div class="flex text-amber-400">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i data-lucide="star" style="width:16px;height:16px; <?= $i<=$avgRating ? 'fill:currentColor;' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-black text-sm text-white"><?= $avgRating ?></span>
                        <span class="text-xs text-gray-500">(<?= $commentCount ?> نظر)</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- فرم ثبت نظر -->
                <div class="lg:col-span-5 bg-brand-dark/40 border border-white/5 p-4 sm:p-5 rounded-2xl h-fit space-y-4">
                    <h4 class="font-bold text-sm text-gray-200">ثبت نظر و امتیاز</h4>
                    <form id="comment-form" onsubmit="submitProductComment(event)" class="space-y-4">
                        <input type="hidden" id="comment-product-id" value="<?= $product['id'] ?>">
                        <div id="comment-msg" class="hidden text-xs font-bold p-2 rounded-lg text-center"></div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-2">نام و نام خانوادگی</label>
                            <input type="text" id="comment-name" required placeholder="مثال: علی محمدی" class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-2">امتیاز شما به قطعه</label>
                            <div class="flex gap-1.5 text-gray-600 direction-ltr justify-end" id="star-picker">
                                <?php for($i=5; $i>=1; $i--): ?>
                                    <button type="button" onclick="setStarRating(<?= $i ?>)" class="hover:text-amber-400 transition <?= $i==5 ? 'text-amber-400' : '' ?>" data-star="<?= $i ?>">
                                        <i data-lucide="star" style="width:20px;height:20px; <?= $i==5 ? 'fill:currentColor;' : '' ?>"></i>
                                    </button>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-2">متن نظر شما</label>
                            <textarea id="comment-text" required rows="4" placeholder="تجربه خود را از کیفیت و تطابق قطعه بنویسید..." class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition resize-none"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs transition shadow-[0_4px_15px_rgba(225,6,0,0.15)]">
                            ثبت و ارسال نظر
                        </button>
                    </form>
                </div>

                <!-- لیست نظرات -->
                <div class="lg:col-span-7 space-y-4 max-h-[500px] overflow-y-auto pr-1">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-10 text-gray-500 text-sm bg-brand-dark/20 rounded-2xl border border-white/5">
                            هنوز نظری برای این قطعه ثبت نشده است. اولین نفری باشید که نظر می‌دهد!
                        </div>
                    <?php else: ?>
                        <?php foreach($comments as $comment): ?>
                            <div class="bg-brand-dark/60 border border-white/5 p-4 rounded-xl space-y-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-sm text-white block"><?= e($comment['name']) ?></span>
                                        <span class="text-[10px] text-gray-500"><?= date('Y/m/d', strtotime($comment['created_at'])) ?></span>
                                    </div>
                                    <div class="flex text-amber-400">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i data-lucide="star" style="width:14px;height:14px; <?= $i<=$comment['rating'] ? 'fill:currentColor;' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-300 leading-relaxed"><?= e($comment['comment_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- تابعی کمکی برای رندر کارت‌های محصول در PHP -->
        <?php
        function renderProductCardHTML($p) {
            $img = !empty($p['images']) ? "/image?id=" . e($p['images'][0]) : "assets/logo/logo.webp";
            $price = number_format($p['price']);
            $genuineBadge = $p['isGenuine'] ? '<span class="absolute top-3 right-3 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold px-2 py-1 rounded border border-emerald-500/20 shadow-sm backdrop-blur-md">جنیون پارت</span>' : '';
            $stockBtn = $p['inStock'] 
                ? "<button onclick=\"addToCart({$p['id']}); event.preventDefault();\" class=\"w-10 h-10 bg-brand-dark border border-white/10 hover:border-brand-red text-gray-400 hover:text-white rounded-xl flex items-center justify-center transition shrink-0\"><i data-lucide=\"shopping-cart\" style=\"width:18px;height:18px;\"></i></button>"
                : "<span class=\"text-[10px] text-gray-500 font-bold bg-brand-dark px-2 py-2 rounded-lg border border-white/5\">ناموجود</span>";
            
            return "
            <a href=\"/product?id={$p['id']}\" class=\"bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between relative\">
                $genuineBadge
                <div class=\"h-48 bg-brand-dark flex items-center justify-center p-4 border-b border-white/5 relative overflow-hidden\">
                    <img src=\"$img\" alt=\"{$p['name']}\" class=\"max-w-full max-h-full object-contain group-hover:scale-110 transition duration-500 drop-shadow-lg\">
                </div>
                <div class=\"p-5 flex-1 flex flex-col justify-between space-y-4\">
                    <div class=\"space-y-2\">
                        <h3 class=\"font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-2 leading-relaxed\">{$p['name']}</h3>
                        <p class=\"text-[11px] text-gray-500 font-mono\">OEM: {$p['oem']}</p>
                    </div>
                    <div class=\"flex justify-between items-center pt-4 border-t border-white/5\">
                        <div class=\"space-y-0.5\">
                            <span class=\"block text-[10px] text-gray-500\">قیمت</span>
                            <span class=\"font-black text-sm text-white tracking-wide\">$price <span class=\"text-[10px] text-gray-500 font-normal\">تومان</span></span>
                        </div>
                        $stockBtn
                    </div>
                </div>
            </a>";
        }
        ?>

        <!-- بخش قطعات مشابه -->
        <?php if(!empty($similar_parts)): ?>
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    قطعات مشابه و پیشنهادی
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <?php foreach($similar_parts as $sp) echo renderProductCardHTML($sp); ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- بخش جدیدترین قطعات -->
        <?php if(!empty($newest_parts)): ?>
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    جدیدترین قطعات تویوتا
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <?php foreach($newest_parts as $np) echo renderProductCardHTML($np); ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- بخش وبلاگ و راهنمای فنی تخصصی تویوتا -->
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    راهنمای فنی و وبلاگ تخصصی تویوتا
                </h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- مقاله ۱ -->
                <div onclick="window.location.href='/blog'" class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="zap" style="width:44px;height:44px;" class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4 class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم؟</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">بررسی کامل تفاوت هولوگرام باکس جنیون و آلیاژ ایریدیوم دنسو ژاپن.</p>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۵ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1" onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- مقاله ۲ -->
                <div onclick="window.location.href='/blog'" class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="wrench" style="width:44px;height:44px;" class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4 class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">بهترین زمان تعویض تسمه تایم تویوتا کمری و پرادو</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">علائم خرابی زنجیر تایم، صداهای غیرعادی موتور در حالت سرد و کیلومتر استاندارد.</p>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۴ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1" onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- مقاله ۳ -->
                <div onclick="window.location.href='/blog'" class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="droplet" style="width:44px;height:44px;" class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4 class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">راهنمای جامع انتخاب و تعویض روغن گیربکس (ATF)</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">تفاوت روانکارهای نوع WS با Type IV و تاثیر حیاتی تعویض به موقع فیلتر گیربکس.</p>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۶ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1" onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        // اسکریپت‌های مربوط به ارتباطات AJAX صفحه محصول (تکی)
        
        function checkAuthenticity() {
            const code = document.getElementById('authenticity-code').value;
            const resDiv = document.getElementById('authenticity-result');
            if(!code) {
                resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 bg-rose-500/10 text-rose-400 border-rose-500/20';
                resDiv.innerHTML = 'لطفا کد را وارد کنید.';
                return;
            }

            resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 bg-brand-dark text-gray-400 border-white/10';
            resDiv.innerHTML = 'در حال بررسی...';

            fetch('/api/check-authenticity', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'code=' + encodeURIComponent(code)
            })
            .then(r => r.json())
            .then(data => {
                let colorClass = data.status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                (data.status === 'warning' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                                'bg-rose-500/10 text-rose-400 border-rose-500/20');
                resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 ' + colorClass;
                resDiv.innerHTML = data.message;
            }).catch(() => {
                resDiv.innerHTML = 'خطا در ارتباط با سرور.';
            });
        }

        function trackOrder() {
            const code = document.getElementById('tracking-code').value;
            const resDiv = document.getElementById('tracking-result');
            if(!code) {
                resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 bg-rose-500/10 text-rose-400 border-rose-500/20';
                resDiv.innerHTML = 'لطفا کد رهگیری را وارد کنید.';
                return;
            }

            resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 bg-brand-dark text-gray-400 border-white/10';
            resDiv.innerHTML = 'در حال رهگیری...';

            fetch('/api/track-order', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'code=' + encodeURIComponent(code)
            })
            .then(r => r.json())
            .then(data => {
                let colorClass = data.status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                (data.status === 'warning' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                                'bg-rose-500/10 text-rose-400 border-rose-500/20');
                resDiv.className = 'text-xs p-3 rounded-xl border transition-all duration-300 block mt-3 ' + colorClass;
                resDiv.innerHTML = data.message;
            }).catch(() => {
                resDiv.innerHTML = 'خطا در ارتباط با سرور.';
            });
        }

        // سیستم انتخاب ستاره
        let currentRating = 5;
        function setStarRating(rating) {
            currentRating = rating;
            const buttons = document.querySelectorAll('#star-picker button');
            buttons.forEach(btn => {
                let starVal = parseInt(btn.getAttribute('data-star'));
                let icon = btn.querySelector('i');
                if(starVal <= rating) {
                    btn.classList.add('text-amber-400');
                    icon.style.fill = 'currentColor';
                } else {
                    btn.classList.remove('text-amber-400');
                    icon.style.fill = 'none';
                }
            });
        }

        function submitProductComment(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const msgBox = document.getElementById('comment-msg');
            const name = document.getElementById('comment-name').value;
            const text = document.getElementById('comment-text').value;
            const pid = document.getElementById('comment-product-id').value;

            btn.disabled = true;
            btn.innerHTML = 'در حال ثبت...';

            fetch('/api/submit-comment', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `product_id=${pid}&name=${encodeURIComponent(name)}&text=${encodeURIComponent(text)}&rating=${currentRating}`
            })
            .then(r => r.json())
            .then(data => {
                msgBox.className = 'text-xs font-bold p-3 rounded-lg text-center mb-4 block ' + 
                    (data.status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20');
                msgBox.innerHTML = data.message;
                
                if(data.status === 'success') {
                    document.getElementById('comment-form').reset();
                    setStarRating(5);
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'ثبت و ارسال نظر';
            });
        }
    </script>
</body>
</html>