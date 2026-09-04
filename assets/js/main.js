const carModels = window.dynamicCarModels || {};
const partCategories = window.dynamicPartCategories || {};
const partsDatabase = window.dynamicPartsDatabase || [];

const dynamic = window.dynamicSettings || {};

const defaultConfig = {
    background_color: '#F8F6F0',
    surface_color: '#FFFFFF',
    text_color: '#251E1B',
    primary_action_color: '#EB0A1E',
    secondary_action_color: '#8B533A',
    font_family: 'IRANSans',
    font_size: 16,
    site_title: dynamic.site_title || 'پرادو یدک',
    hero_subtitle: dynamic.site_subtitle || 'PRADO YADAK',
    phone_number: dynamic.phone_number || '09189998852',
    address_text: dynamic.address || 'آدرس فروشگاه'
};

function applyConfig(config) {
    if (!document.body) return;
    const bg = config.background_color || defaultConfig.background_color;
    const surface = config.surface_color || defaultConfig.surface_color;
    const text = config.text_color || defaultConfig.text_color;
    const primary = config.primary_action_color || defaultConfig.primary_action_color;
    const secondary = config.secondary_action_color || defaultConfig.secondary_action_color;
    const font = config.font_family || defaultConfig.font_family;
    const size = config.font_size || defaultConfig.font_size;

    document.body.style.backgroundColor = bg;
    document.body.style.color = text;
    document.body.style.fontFamily = `${font}, sans-serif`;
    document.body.style.fontSize = `${size}px`;

    const title = config.site_title || defaultConfig.site_title;
    ['nav-title', 'footer-title'].forEach(id => {
        const e = document.getElementById(id);
        if (e) e.textContent = title;
    });

    const heroSub = document.getElementById('hero-subtitle');
    if (heroSub) heroSub.textContent = config.hero_subtitle || defaultConfig.hero_subtitle;

    const phone = config.phone_number || defaultConfig.phone_number;
    ['topbar-phone', 'contact-phone'].forEach(id => {
        const e = document.getElementById(id);
        if (e) e.textContent = phone;
    });

    const addr = config.address_text || defaultConfig.address_text;
    ['topbar-address', 'contact-address'].forEach(id => {
        const e = document.getElementById(id);
        if (e) e.textContent = addr;
    });

    document.querySelectorAll('.bg-brand-red').forEach(el => el.style.backgroundColor = primary);
    document.querySelectorAll('.text-brand-red').forEach(el => el.style.color = primary);
    document.querySelectorAll('.bg-brand-grey').forEach(el => el.style.backgroundColor = surface);
    document.querySelectorAll('.text-gray-400').forEach(el => el.style.color = secondary);

    document.querySelectorAll('h2, .text-4xl, .text-6xl').forEach(el => el.style.fontSize = `${size * 2.5}px`);
    document.querySelectorAll('h3, .text-3xl, .text-5xl').forEach(el => el.style.fontSize = `${size * 2}px`);
    document.querySelectorAll('h4, .text-xl').forEach(el => el.style.fontSize = `${size * 1.25}px`);
    document.querySelectorAll('.text-sm').forEach(el => el.style.fontSize = `${size * 0.875}px`);
}

function initElementSdk() {
    if (window.elementSdk) {
        window.elementSdk.init({
            defaultConfig,
            onConfigChange: async (config) => applyConfig(config),
            mapToCapabilities: (config) => ({
                recolorables: [
                    { get: () => config.background_color || defaultConfig.background_color, set: (v) => { config.background_color = v; window.elementSdk.setConfig({ background_color: v }); } },
                    { get: () => config.surface_color || defaultConfig.surface_color, set: (v) => { config.surface_color = v; window.elementSdk.setConfig({ surface_color: v }); } },
                    { get: () => config.text_color || defaultConfig.text_color, set: (v) => { config.text_color = v; window.elementSdk.setConfig({ text_color: v }); } },
                    { get: () => config.primary_action_color || defaultConfig.primary_action_color, set: (v) => { config.primary_action_color = v; window.elementSdk.setConfig({ primary_action_color: v }); } },
                    { get: () => config.secondary_action_color || defaultConfig.secondary_action_color, set: (v) => { config.secondary_action_color = v; window.elementSdk.setConfig({ secondary_action_color: v }); } },
                ],
                borderables: [],
                fontEditable: { get: () => config.font_family || defaultConfig.font_family, set: (v) => { config.font_family = v; window.elementSdk.setConfig({ font_family: v }); } },
                fontSizeable: { get: () => config.font_size || defaultConfig.font_size, set: (v) => { config.font_size = v; window.elementSdk.setConfig({ font_size: v }); } }
            }),
            mapToEditPanelValues: (config) => new Map([
                ['site_title', config.site_title || defaultConfig.site_title],
                ['hero_subtitle', config.hero_subtitle || defaultConfig.hero_subtitle],
                ['phone_number', config.phone_number || defaultConfig.phone_number],
                ['address_text', config.address_text || defaultConfig.address_text],
            ])
        });
    }
}

function initScrollReveal() {
    const elements = document.querySelectorAll('.scroll-reveal');
    if (!elements.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

function toggleMobileMenu(open) {
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-menu-overlay');
    if (!menu || !overlay) return;

    if (open) {
        overlay.classList.remove('hidden');
        menu.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            menu.classList.remove('translate-x-full');
        }, 20);
    } else {
        overlay.classList.remove('opacity-100');
        menu.classList.add('translate-x-full');
        setTimeout(() => {
            overlay.classList.add('hidden');
            menu.classList.add('hidden');
        }, 300);
    }
}

function toggleCart(open) {
    const drawer = document.getElementById('cart-drawer');
    const overlay = document.getElementById('cart-drawer-overlay');
    if (!drawer || !overlay) return;

    if (open) {
        overlay.classList.remove('hidden');
        drawer.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            drawer.classList.remove('-translate-x-full');
        }, 20);
    } else {
        overlay.classList.remove('opacity-100');
        drawer.classList.add('-translate-x-full');
        setTimeout(() => {
            overlay.classList.add('hidden');
            drawer.classList.add('hidden');
        }, 300);
    }
}

let cart = JSON.parse(localStorage.getItem('toyota_cart')) || [];

function saveAndRefreshCart() {
    localStorage.setItem('toyota_cart', JSON.stringify(cart));
    updateCartUI();
}

function addToCart(id) {
    const part = partsDatabase.find(p => p.id === id);
    if (!part) return;

    const existingItem = cart.find(item => item.product.id === id);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ product: part, quantity: 1 });
    }
    saveAndRefreshCart();
    toggleCart(true);
}

function addToCartFromDetail() {
    const productId = getProductIdFromURL();
    addToCart(productId);
}

function changeQty(productId, amount) {
    const item = cart.find(i => i.product.id === productId);
    if (!item) return;

    item.quantity += amount;
    if (item.quantity <= 0) {
        cart = cart.filter(i => i.product.id !== productId);
    }
    saveAndRefreshCart();
}

function removeFromCart(productId) {
    cart = cart.filter(i => i.product.id !== productId);
    saveAndRefreshCart();
}

function updateCartUI() {
    const container = document.getElementById('cart-items-container');
    const totalText = document.getElementById('cart-total-price');
    const badge = document.getElementById('cart-badge') || document.getElementById('header-cart-badge');

    if (!container) return;
    container.innerHTML = '';
    let total = 0;
    let totalCount = 0;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 text-gray-500">
                <i data-lucide="shopping-cart" class="mx-auto mb-3 text-gray-600" style="width:36px;height:36px;"></i>
                <span class="text-sm">سبد خرید شما خالی است!</span>
            </div>
        `;
        if (totalText) totalText.textContent = "۰ تومان";
        if (badge) {
            badge.classList.add('hidden');
            badge.classList.remove('flex', 'inline-flex');
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    cart.forEach(item => {
        total += item.product.price * item.quantity;
        totalCount += item.quantity;

        const icon = (item.product.images && item.product.images[0]) || item.product.imageIcon || 'disc';

        // تغییرات در HTML داینامیک هر آیتم
        const itemHtml = `
            <div class="relative flex gap-3 bg-black/30 p-3 rounded-xl border border-white/5 items-center transition hover:border-brand-red/50 group">
                
                <!-- دکمه ضربدر برای حذف کامل -->
                <button onclick="removeFromCart(${item.product.id})" class="absolute top-2 left-2 z-10 p-1 text-gray-500 hover:text-brand-red hover:bg-brand-red/10 rounded-md transition" title="حذف از سبد">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>

                <!-- آیکون تصویر (لینک‌دار) -->
                <a href="/product?id=${item.product.id}" class="w-14 h-14 bg-brand-dark rounded-lg flex items-center justify-center border border-white/10 text-brand-red flex-shrink-0 p-2 hover:scale-105 transition-transform">
                    ${renderMediaHTML(icon, "w-6 h-6")}
                </a>
                
                <!-- کلاس pl-6 اضافه شد تا متن زیر دکمه ضربدر نرود -->
                <div class="flex-1 min-w-0 pl-6"> 
                    <!-- عنوان قطعه (لینک‌دار) -->
                    <a href="/product?id=${item.product.id}" class="block transition-colors hover:opacity-80">
                        <h5 class="text-xs font-bold text-white truncate">${item.product.name}</h5>
                    </a>
                    <p class="text-[10px] text-gray-400 mt-0.5">OEM: ${item.product.oem}</p>
                    
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs font-black text-brand-red">${(item.product.price * item.quantity).toLocaleString('fa-IR')} تومان</span>
                        <div class="flex items-center gap-2 bg-brand-grey border border-white/10 rounded-md px-2 py-0.5 text-xs">
                            <button class="text-gray-400 hover:text-white font-bold" onclick="changeQty(${item.product.id}, 1)">+</button>
                            <span class="text-white font-medium">${item.quantity}</span>
                            <button class="text-gray-400 hover:text-white font-bold" onclick="changeQty(${item.product.id}, -1)">-</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', itemHtml);
    });

    if (totalText) totalText.textContent = `${total.toLocaleString('fa-IR')} تومان`;
    if (badge) {
        badge.textContent = totalCount;
        badge.classList.remove('hidden');
        badge.classList.add('flex');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function initFilters() {
    const modelContainer = document.getElementById('model-filters');
    const mobileModelContainer = document.getElementById('mobile-model-filters');
    const catContainer = document.getElementById('category-filters');
    const mobileCatContainer = document.getElementById('mobile-category-filters');

    if (!modelContainer || !catContainer) return;

    modelContainer.innerHTML = '';
    catContainer.innerHTML = '';
    if (mobileModelContainer) mobileModelContainer.innerHTML = '<h5 class="font-bold text-sm text-gray-200 mb-2">مدل خودرو</h5>';
    if (mobileCatContainer) mobileCatContainer.innerHTML = '<h5 class="font-bold text-sm text-gray-200 mb-2">دسته‌بندی</h5>';

    Object.keys(carModels).forEach(key => {
        const html = `<label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer transition"><input type="checkbox" name="model" value="${key}" class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10" onchange="syncCheckboxes('model', '${key}', this.checked)">${carModels[key]}</label>`;
        modelContainer.insertAdjacentHTML('beforeend', html);
        if (mobileModelContainer) mobileModelContainer.insertAdjacentHTML('beforeend', html);
    });

    Object.keys(partCategories).forEach(key => {
        const html = `<label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer transition"><input type="checkbox" name="category" value="${key}" class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10" onchange="syncCheckboxes('category', '${key}', this.checked)">${partCategories[key]}</label>`;
        catContainer.insertAdjacentHTML('beforeend', html);
        if (mobileCatContainer) mobileCatContainer.insertAdjacentHTML('beforeend', html);
    });

    // === ویژگی جدید: خواندن اتوماتیک جستجو از URL صفحه اصلی ===
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('q')) {
        const s = document.getElementById('search-input');
        if (s) s.value = urlParams.get('q');
    }
    if (urlParams.get('model')) {
        document.querySelectorAll(`input[name="model"][value="${urlParams.get('model')}"]`).forEach(cb => cb.checked = true);
    }
    if (urlParams.get('category')) {
        document.querySelectorAll(`input[name="category"][value="${urlParams.get('category')}"]`).forEach(cb => cb.checked = true);
    }
}

// جایگزین کردن تابع applyFilters با نسخه سرور ساید (AJAX)
async function applyFilters(page = 1) {
    const grid = document.getElementById('parts-grid');
    if (!grid) return;

    const searchInput = document.getElementById('search-input');
    const searchQuery = searchInput ? searchInput.value.trim() : '';
    const priceSlider = document.getElementById('price-slider');
    const maxPrice = priceSlider ? priceSlider.value : 25000000;
    const inStockToggle = document.getElementById('in-stock-toggle');
    const inStockOnly = inStockToggle ? inStockToggle.checked : false;
    const sortSelect = document.getElementById('sort-select');
    const sortVal = sortSelect ? sortSelect.value : 'newest';

    const checkedBrands = Array.from(document.querySelectorAll('input[name="brand"]:checked')).map(el => el.value);
    const checkedModels = Array.from(document.querySelectorAll('input[name="model"]:checked')).map(el => el.value);
    const checkedCats = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(el => el.value);

    // نمایش لودینگ گرافیکی روی گرید تا زمان برگشت جواب از سرور
    grid.classList.remove('hidden');
    grid.innerHTML = '<div class="col-span-full text-center py-16"><i data-lucide="loader-2" class="w-10 h-10 animate-spin mx-auto text-brand-red mb-3"></i><p class="text-gray-400 text-xs">در حال جستجو در انبار قطعات...</p></div>';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
        // ساخت پارامترهای GET برای ارسال به API
        const params = new URLSearchParams();
        if (searchQuery) params.append('q', searchQuery);
        if (maxPrice) params.append('maxPrice', maxPrice);
        if (inStockOnly) params.append('inStock', 'true');
        if (sortVal) params.append('sort', sortVal);
        params.append('page', page);
        
        checkedBrands.forEach(b => params.append('brand[]', b));
        checkedModels.forEach(m => params.append('model', m));
        checkedCats.forEach(c => params.append('category', c));

        // واکشی زنده اطلاعات از دیتابیس
        const response = await fetch(`/api/parts?${params.toString()}`);
        const data = await response.json();
        
        // به‌روزرسانی متغیر گلوبال برای دکمه "افزودن به سبد خرید"
        partsDatabase = data.items; 
        
        // ارسال دیتای جدید به تابع رندر HTML
        renderGrid(data.items, data.total);
        
    } catch (error) {
        console.error('Error fetching parts:', error);
        grid.innerHTML = '<div class="col-span-full text-center py-10 text-rose-500 bg-rose-500/10 rounded-2xl">خطا در دریافت اطلاعات. لطفا اینترنت خود را بررسی کنید.</div>';
    }
}

// آپدیت کردن تابع renderGrid برای دریافت تعداد کل از سرور
function renderGrid(parts, totalCount = 0) {
    const grid = document.getElementById('parts-grid');
    const emptyState = document.getElementById('empty-state');
    const countText = document.getElementById('results-count');

    if (!grid) return;

    grid.innerHTML = '';
    // نمایش تعداد کل یافت شده در دیتابیس
    if (countText) countText.textContent = `یافت شده: ${totalCount} قطعه`;

    if (parts.length === 0) {
        grid.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    grid.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');

    parts.forEach(part => {
        const stockBadge = part.inStock
            ? '<span class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold">موجود در انبار</span>'
            : '<span class="bg-rose-500/10 text-rose-400 text-[10px] px-2 py-1 rounded-md font-bold">ناموجود</span>';

        const brandBadge = part.isGenuine
            ? '<span class="bg-brand-red/10 text-brand-red text-[10px] px-2 py-1 rounded-md font-bold">اصلی Genuine</span>'
            : '<span class="bg-gray-400/10 text-gray-300 text-[10px] px-2 py-1 rounded-md font-bold">ژاپنی OEM</span>';

        const iconName = part.imageIcon || (part.images && part.images[0]) || 'disc';
        const cardHtml = `
            <div class="bg-brand-grey border border-white/5 hover:border-brand-red/30 p-5 rounded-2xl flex flex-col justify-between transition duration-300 hover:shadow-[0_10px_35px_rgba(225,6,0,0.12)]">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        ${stockBadge}
                        ${brandBadge}
                    </div>
                    <div onclick="window.location.href='/product?id=${part.id}'" class="w-full h-40 bg-brand-dark rounded-xl flex items-center justify-center mb-4 text-brand-red relative group overflow-hidden border border-white/5 cursor-pointer">
                        ${renderMediaHTML(iconName, "w-12 h-12 transition transform group-hover:scale-125 duration-300")}
                        <span class="absolute bottom-2 left-2 text-[10px] text-gray-500 bg-brand-dark/80 px-2 py-0.5 rounded" style="direction: ltr;">OEM: ${part.oem}</span>
                    </div>
                    <h4 class="font-bold text-sm text-white leading-relaxed line-clamp-2 hover:text-brand-red cursor-pointer transition" onclick="window.location.href='/product?id=${part.id}'">${part.name}</h4>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <i data-lucide="car" style="width:13px;height:13px;"></i>
                        سازگار با: ${(carModels[part.model] || part.model).split(' ')[0]}
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-white/5 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-gray-500 block mb-0.5">قیمت مصرف‌کننده:</span>
                        <span class="font-black text-sm text-brand-red">${part.price.toLocaleString('fa-IR')} تومان</span>
                    </div>
                    <button ${part.inStock ? `onclick="addToCart(${part.id})"` : 'disabled'} class="p-2.5 rounded-xl transition ${part.inStock ? 'bg-brand-red hover:bg-red-700 text-white shadow-[0_4px_15px_rgba(225,6,0,0.2)]' : 'bg-white/5 text-gray-500 cursor-not-allowed'}">
                        <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>
        `;
        grid.insertAdjacentHTML('beforeend', cardHtml);
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updatePriceLabel(value) {
    const formatted = (value / 1000000).toFixed(1);

    const priceVal = document.getElementById('price-val');
    const priceSlider = document.getElementById('price-slider');
    if (priceVal) priceVal.textContent = `تا ${formatted} میلیون`;
    if (priceSlider) priceSlider.value = value;

    const mobileVal = document.getElementById('mobile-price-val');
    const mobileSlider = document.getElementById('mobile-price-slider');
    if (mobileVal) mobileVal.textContent = `تا ${formatted} میلیون`;
    if (mobileSlider) mobileSlider.value = value;

    applyFilters();
}

function applyFilters() {
    const grid = document.getElementById('parts-grid');
    if (!grid) return;

    const searchInput = document.getElementById('search-input');
    const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const priceSlider = document.getElementById('price-slider');
    const maxPrice = priceSlider ? parseFloat(priceSlider.value) : 25000000;
    const inStockToggle = document.getElementById('in-stock-toggle');
    const inStockOnly = inStockToggle ? inStockToggle.checked : false;
    const sortSelect = document.getElementById('sort-select');
    const sortVal = sortSelect ? sortSelect.value : 'newest';

    const checkedBrands = Array.from(document.querySelectorAll('input[name="brand"]:checked')).map(el => el.value);
    const checkedModels = Array.from(document.querySelectorAll('input[name="model"]:checked')).map(el => el.value);
    const checkedCats = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(el => el.value);

    let filteredParts = partsDatabase.filter(part => {
        const matchesSearch = part.name.toLowerCase().includes(searchQuery) || part.oem.toLowerCase().includes(searchQuery);
        const matchesPrice = part.price <= maxPrice;
        const matchesStock = !inStockOnly || part.inStock;
        const matchesModel = checkedModels.length === 0 || checkedModels.includes(part.model);
        const matchesCategory = checkedCats.length === 0 || checkedCats.includes(part.category);

        let matchesBrand = true;
        if (checkedBrands.length > 0) {
            // ۱. بررسی اصالت کلی (genuine یا oem)
            const status = part.isGenuine ? 'genuine' : 'oem';
            const matchesStatus = checkedBrands.includes(status);

            // ۲. بررسی برند خاص (مثل kyb, aisin و ...)
            const matchesSpecificBrand = part.brand && checkedBrands.includes(part.brand.toLowerCase());

            // اگر کاربر دسته کلی را انتخاب کرده بود یا برند خاص را، محصول نمایش داده شود
            matchesBrand = matchesStatus || matchesSpecificBrand;
        }

        return matchesSearch && matchesPrice && matchesStock && matchesModel && matchesCategory && matchesBrand;
    });

    if (sortVal === 'price-asc') {
        filteredParts.sort((a, b) => a.price - b.price);
    } else if (sortVal === 'price-desc') {
        filteredParts.sort((a, b) => b.price - a.price);
    } else if (sortVal === 'popular') {
        filteredParts.sort((a, b) => (b.id % 2) - (a.id % 2));
    } else {
        filteredParts.sort((a, b) => b.id - a.id);
    }

    renderGrid(filteredParts);
}

function renderGrid(parts) {
    const grid = document.getElementById('parts-grid');
    const emptyState = document.getElementById('empty-state');
    const countText = document.getElementById('results-count');

    if (!grid) return;

    grid.innerHTML = '';
    if (countText) countText.textContent = `یافت شده: ${parts.length} قطعه`;

    if (parts.length === 0) {
        grid.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    grid.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');

    parts.forEach(part => {
        const stockBadge = part.inStock
            ? '<span class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold">موجود در انبار</span>'
            : '<span class="bg-rose-500/10 text-rose-400 text-[10px] px-2 py-1 rounded-md font-bold">ناموجود</span>';

        const brandBadge = part.isGenuine
            ? '<span class="bg-brand-red/10 text-brand-red text-[10px] px-2 py-1 rounded-md font-bold">اصلی Genuine</span>'
            : '<span class="bg-gray-400/10 text-gray-300 text-[10px] px-2 py-1 rounded-md font-bold">ژاپنی OEM</span>';

        const iconName = part.imageIcon || (part.images && part.images[0]) || 'disc';
        const cardHtml = `
            <div class="bg-brand-grey border border-white/5 hover:border-brand-red/30 p-5 rounded-2xl flex flex-col justify-between transition duration-300 hover:shadow-[0_10px_35px_rgba(225,6,0,0.12)]">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        ${stockBadge}
                        ${brandBadge}
                    </div>
                    <div onclick="window.location.href='/product?id=${part.id}'" class="w-full h-40 bg-brand-dark rounded-xl flex items-center justify-center mb-4 text-brand-red relative group overflow-hidden border border-white/5 cursor-pointer">
                        <i data-lucide="${iconName}" style="width:52px;height:52px;" class="transition transform group-hover:scale-125 duration-300"></i>
                        <span class="absolute bottom-2 left-2 text-[10px] text-gray-500 bg-brand-dark/80 px-2 py-0.5 rounded" style="direction: ltr;">OEM: ${part.oem}</span>
                    </div>
                    <h4 class="font-bold text-sm text-white leading-relaxed line-clamp-2 hover:text-brand-red cursor-pointer transition" onclick="window.location.href='/product?id=${part.id}'">${part.name}</h4>
                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                        <i data-lucide="car" style="width:13px;height:13px;"></i>
                        سازگار با: ${(carModels[part.model] || part.model).split(' ')[0]}
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-white/5 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-gray-500 block mb-0.5">قیمت مصرف‌کننده:</span>
                        <span class="font-black text-sm text-brand-red">${part.price.toLocaleString('fa-IR')} تومان</span>
                    </div>
                    <button ${part.inStock ? `onclick="addToCart(${part.id})"` : 'disabled'} class="p-2.5 rounded-xl transition ${part.inStock ? 'bg-brand-red hover:bg-red-700 text-white shadow-[0_4px_15px_rgba(225,6,0,0.2)]' : 'bg-white/5 text-gray-500 cursor-not-allowed'}">
                        <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>
        `;
        grid.insertAdjacentHTML('beforeend', cardHtml);
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function resetFilters() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.value = '';

    const priceSlider = document.getElementById('price-slider');
    const priceVal = document.getElementById('price-val');
    if (priceSlider) priceSlider.value = 25000000;
    if (priceVal) priceVal.textContent = "تا ۲۵ میلیون";

    const mobilePriceSlider = document.getElementById('mobile-price-slider');
    const mobilePriceVal = document.getElementById('mobile-price-val');
    if (mobilePriceSlider) mobilePriceSlider.value = 25000000;
    if (mobilePriceVal) mobilePriceVal.textContent = "تا ۲۵ میلیون";

    const inStockToggle = document.getElementById('in-stock-toggle');
    const mobileInStockToggle = document.getElementById('mobile-in-stock-toggle');
    if (inStockToggle) inStockToggle.checked = false;
    if (mobileInStockToggle) mobileInStockToggle.checked = false;

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.checked = false);

    applyFilters();
}

function showProductDetails(id) {
    window.location.href = `/product?id=${id}`;
}

function toggleMobileFilters(open) {
    const drawer = document.getElementById('mobile-filter-drawer');
    const overlay = document.getElementById('mobile-filter-overlay');
    if (!drawer || !overlay) return;
    if (open) {
        overlay.classList.remove('hidden');
        drawer.classList.remove('hidden');
        setTimeout(() => { overlay.classList.add('opacity-100'); drawer.classList.remove('translate-x-full'); }, 20);
    } else {
        overlay.classList.remove('opacity-100');
        drawer.classList.add('translate-x-full');
        setTimeout(() => { overlay.classList.add('hidden'); drawer.classList.add('hidden'); }, 300);
    }
}

function toggleDetailModal(open) {
    const overlay = document.getElementById('detail-modal-overlay');
    const modal = document.getElementById('detail-modal');
    if (!overlay || !modal) return;
    if (open) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        setTimeout(() => { overlay.classList.add('opacity-100'); modal.classList.remove('scale-95', 'opacity-0'); }, 20);
    } else {
        overlay.classList.remove('opacity-100');
        modal.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }, 300);
    }
}

function syncCheckboxes(name, value, isChecked) {
    document.querySelectorAll(`input[name="${name}"][value="${value}"]`).forEach(cb => {
        cb.checked = isChecked;
    });
    applyFilters();
}

function toggleInStock(isChecked) {
    const stockToggle = document.getElementById('in-stock-toggle');
    const mobileStock = document.getElementById('mobile-in-stock-toggle');
    if (stockToggle) stockToggle.checked = isChecked;
    if (mobileStock) mobileStock.checked = isChecked;
    applyFilters();
}

let currentImgSource = '';

function getProductIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return parseInt(params.get('id')) || 1;
}

function renderMediaHTML(source, iconStyleClass = "anim-float") {
    if (!source) return '';

    if (source !== 'disc' && !source.startsWith('http') && !source.includes('.') && source.length > 20) {
        return `<img src="/image?id=${source}" class="max-w-full max-h-full object-contain ${iconStyleClass}" alt="Product Image" loading="lazy" />`;
    }

    if (source.startsWith('http') || source.includes('/') || source.includes('.')) {
        return `<img src="${source}" class="max-w-full max-h-full object-contain ${iconStyleClass}" alt="Part Image" loading="lazy" />`;
    }
    return `<i data-lucide="${source}" class="${iconStyleClass}" style="width:100%; height:100%; max-width:110px; max-height:110px;"></i>`;
}

function changeMainImage(imgSrc, element) {
    currentImgSource = imgSrc;
    const mainImageContainer = document.getElementById('main-product-inner');
    if (mainImageContainer) {
        mainImageContainer.innerHTML = renderMediaHTML(imgSrc);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    document.querySelectorAll('.thumb-btn').forEach(btn => btn.classList.remove('border-brand-red', 'bg-brand-dark'));
    if (element) element.classList.add('border-brand-red', 'bg-brand-dark');
}

function toggleZoomModal(open) {
    const overlay = document.getElementById('image-zoom-modal');
    const content = document.getElementById('zoom-modal-content');
    if (!overlay || !content) return;

    if (open) {
        content.innerHTML = renderMediaHTML(currentImgSource, "scale-100 max-w-[90vw] max-h-[80vh]");
        overlay.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            content.classList.remove('scale-95');
        }, 20);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
        overlay.classList.remove('opacity-100');
        content.classList.add('scale-95');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }
}

function checkAuthenticity() {
    const input = document.getElementById('authenticity-code');
    const result = document.getElementById('authenticity-result');
    if (!input || !result) return;

    const code = input.value.trim();
    result.className = "text-xs p-3 rounded-xl border mt-3 transition-all duration-300";

    if (!code) {
        result.innerHTML = '⚠️ لطفا کد یا بارکد ۱۰ رقمی قطعه را وارد کنید.';
        result.classList.add('bg-amber-500/10', 'text-amber-400', 'border-amber-500/20', 'block');
        return;
    }

    if (code.toLowerCase().includes('toy')) {
        result.innerHTML = '✔ <b>تایید اصالت: قطعه اصلی (Toyota Genuine Parts) است.</b> این کالا تحت پلمپ کمپانی مادر و گارانتی اصالت کتبی فروشگاه پرادو یدک قرار دارد.';
        result.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20', 'block');
    } else {
        result.innerHTML = '❌ <b>کد نامعتبر!</b> بارکد وارد شده در شبکه توزیع قطعات اورجینال پرادو یدک یافت نشد. احتمال تقلبی بودن کالا وجود دارد.';
        result.classList.add('bg-rose-500/10', 'text-rose-400', 'border-rose-500/20', 'block');
    }
}

function trackOrder() {
    const input = document.getElementById('tracking-code');
    const result = document.getElementById('tracking-result');
    if (!input || !result) return;

    const code = input.value.trim();
    result.className = "text-xs p-3 rounded-xl border mt-3 transition-all duration-300";

    if (!code) {
        result.innerHTML = '⚠️ لطفا کد سفارش یا شماره موبایل خرید را وارد کنید.';
        result.classList.add('bg-amber-500/10', 'text-amber-400', 'border-amber-500/20', 'block');
        return;
    }

    result.innerHTML = `📦 <b>وضعیت مرسوله (#${code}):</b> تحویل به هاب تیپاکس<br><span class="text-[10px] text-gray-400 block mt-1">کد رهگیری تیپاکس: ۲۹۳۸۱۷۲۳۹۱۸۲<br>آخرین وضعیت: خروج از تهران به سمت مرکز توزیع مقصد</span>`;
    result.classList.add('bg-brand-dark/60', 'text-gray-200', 'border-white/10', 'block');
}

function loadProductDetails() {
    const container = document.getElementById('product-container');
    if (!container) return;

    const productId = getProductIdFromURL();
    const part = partsDatabase.find(p => p.id === productId);

    if (!part) {
        container.innerHTML = `<div class="text-center py-12"><h3 class="text-xl font-bold">قطعه یافت نشد!</h3></div>`;
        return;
    }

    const imgs = part.images || [part.imageIcon || 'disc'];
    currentImgSource = imgs[0];

    let thumbnailsHtml = '';
    imgs.forEach((img, index) => {
        const isActive = index === 0 ? 'border-brand-red bg-brand-dark' : 'border-white/5 bg-brand-dark/40';
        thumbnailsHtml += `
            <div onclick="changeMainImage('${img}', this)" class="thumb-btn h-16 sm:h-20 border rounded-xl flex items-center justify-center text-brand-red/80 cursor-pointer transition duration-200 hover:border-brand-red/50 p-2 ${isActive}">
                ${renderMediaHTML(img, "w-6 h-6 sm:w-8 sm:h-8")}
            </div>
        `;
    });

    container.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-5 space-y-4 w-full">
                <div id="main-product-image" onclick="toggleZoomModal(true)" 
                    class="w-full h-64 sm:h-80 bg-brand-dark border border-white/5 rounded-2xl flex items-center justify-center text-brand-red relative overflow-hidden cursor-zoom-in group">
                    <div id="main-product-inner" class="w-full h-full flex items-center justify-center p-6">
                        ${renderMediaHTML(imgs[0])}
                    </div>
                    <div class="absolute top-3 right-3 bg-brand-grey/80 border border-white/10 p-2 rounded-xl opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <i data-lucide="zoom-in" style="width:16px;height:16px;" class="text-white"></i>
                    </div>
                </div>
                <div class="grid grid-cols-5 gap-2 sm:gap-3">
                    ${thumbnailsHtml}
                </div>
            </div>

            <div class="lg:col-span-7 space-y-6 w-full">
                <div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="bg-brand-red/10 text-brand-red text-xs px-3 py-1 rounded-full font-bold">${part.isGenuine ? "اصلی جنیون پارت" : "وارداتی OEM معتبر"}</span>
                        <span class="${part.inStock ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'} text-xs px-3 py-1 rounded-full font-bold">${part.inStock ? 'موجود در انبار' : 'ناموجود'}</span>
                    </div>
                    <h2 class="text-lg sm:text-2xl font-black text-white leading-snug">${part.name}</h2>
                </div>

                <div class="red-line"></div>

                <div class="space-y-2">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-400 flex items-center gap-2"><i data-lucide="file-text" style="width:16px;height:16px;"></i> بررسی تخصصی قطعه</h4>
                    <p class="text-gray-300 text-xs sm:text-sm leading-relaxed text-justify">${part.desc}</p>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-400 flex items-center gap-2"><i data-lucide="info" style="width:16px;height:16px;"></i> مشخصات فنی</h4>
                    <div class="border border-white/10 rounded-xl overflow-hidden text-xs sm:text-sm">
                        <div class="grid grid-cols-2 bg-black/20 p-3 border-b border-white/5"><span class="text-gray-400">شماره فنی (OEM)</span><span class="font-mono text-white text-left" style="direction: ltr;">${part.oem}</span></div>
                        <div class="grid grid-cols-2 p-3 border-b border-white/5"><span class="text-gray-400">خودرو سازگار</span><span class="text-white font-bold">${carModels[part.model] || part.model}</span></div>
                        <div class="grid grid-cols-2 bg-black/20 p-3 border-b border-white/5"><span class="text-gray-400">دسته‌بندی</span><span class="text-white">${partCategories[part.category] || part.category}</span></div>
                    </div>
                </div>

                <div class="bg-brand-dark/50 border border-white/5 p-4 sm:p-5 rounded-xl sm:rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-center sm:text-right w-full sm:w-auto">
                        <span class="text-[11px] text-gray-500 block mb-1">قیمت نهایی قطعه:</span>
                        <span class="font-black text-xl sm:text-2xl text-brand-red">${part.price.toLocaleString('fa-IR')} <span class="text-xs font-normal text-white">تومان</span></span>
                    </div>
                    <button ${part.inStock ? '' : 'disabled'} onclick="addToCartFromDetail()" class="w-full sm:w-auto bg-brand-red hover:bg-red-700 disabled:bg-white/5 disabled:text-gray-500 text-white font-black px-6 sm:px-8 py-3.5 rounded-xl transition flex items-center justify-center gap-3 shadow-[0_5px_20px_rgba(225,6,0,0.3)] text-sm">
                        <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i> افزودن به سبد خرید
                    </button>
                </div>
            </div>
        </div>
    `;

    renderSimilarParts(part);
    renderNewestParts();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    loadComments();
}

function renderSimilarParts(currentPart) {
    const grid = document.getElementById('similar-parts-grid');
    if (!grid) return;
    const shuffled = partsDatabase.filter(p => p.id !== currentPart.id && (p.category === currentPart.category || p.model === currentPart.model));
    grid.innerHTML = shuffled.slice(0, 4).map(p => generateMiniCard(p)).join('');
}

function renderNewestParts() {
    const grid = document.getElementById('newest-parts-grid');
    if (!grid) return;
    const newest = [...partsDatabase].sort((a, b) => b.id - a.id).slice(0, 4);
    grid.innerHTML = newest.map(p => generateMiniCard(p)).join('');
}

function generateMiniCard(p) {
    const img = (p.images && p.images[0]) || p.imageIcon || 'disc';
    return `
        <div class="bg-brand-grey border border-white/5 hover:border-brand-red/30 p-4 rounded-xl flex flex-col justify-between transition duration-300">
            <div>
                <div onclick="window.location.href='/product?id=${p.id}'" class="w-full h-32 bg-brand-dark rounded-xl flex items-center justify-center text-brand-red mb-3 cursor-pointer group overflow-hidden border border-white/5 p-4">
                    <div class="w-10 h-10 flex items-center justify-center transition transform group-hover:scale-110 duration-200">${renderMediaHTML(img, "")}</div>
                </div>
                <h4 onclick="window.location.href='/product?id=${p.id}'" class="font-bold text-xs text-white line-clamp-2 hover:text-brand-red cursor-pointer transition h-8 mb-2">${p.name}</h4>
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/5">
                <span class="font-black text-xs text-brand-red">${p.price.toLocaleString('fa-IR')} تومان</span>
                <span class="text-[10px] text-gray-500">${(carModels[p.model] || p.model).split(' ')[0]}</span>
            </div>
        </div>
    `;
}

const defaultComments = [
    { id: 1, partId: 1, name: "مهران رضایی", rating: 5, text: "روی کمری ۲۰۱۴ بستم، کاملاً بی‌صدا هست و گیرایی فوق‌العاده‌ای داره. بسته‌بندی پلمپ جنیون پارت بود.", date: "۱۴۰۵/۰۲/۱۵" },
    { id: 2, partId: 1, name: "جواد کاظمی", rating: 4, text: "کیفیت لنت عالیه، فقط زمان ارسال به مشهد سه روز طول کشید که جا داره سریع‌تر بشه. در کل راضیم.", date: "۱۴۰۵/۰۳/۰۲" },
    { id: 3, partId: 2, name: "امیرحسین عباسی", rating: 5, text: "شمع سوزنی اصلی دنسو بود. مصرف سوخت ماشین کاملاً محسوس پایین اومد. ممنون از آقای امین‌زاده.", date: "۱۴۰۵/۰۱/۲۰" }
];

let selectedRating = 5;

function setStarRating(rating) {
    selectedRating = rating;
    const stars = document.querySelectorAll('#star-picker button');
    stars.forEach(star => {
        const starVal = parseInt(star.getAttribute('data-star'));
        if (starVal <= rating) {
            star.classList.add('text-amber-400');
        } else {
            star.classList.remove('text-amber-400');
        }
    });
}

function loadComments() {
    const container = document.getElementById('comments-list-container');
    if (!container) return;

    const partId = getProductIdFromURL();
    let localComments = JSON.parse(localStorage.getItem('part_comments')) || defaultComments;
    const filtered = localComments.filter(c => c.partId === partId);
    container.innerHTML = '';

    if (filtered.length === 0) {
        container.innerHTML = `<div class="text-center py-12 text-gray-500 text-xs">هنوز نظری برای این قطعه ثبت نشده است. اولین خریدار باشید که نظر می‌دهد!</div>`;
        updateRatingSummary(0, 0);
        return;
    }

    let totalRating = 0;
    filtered.forEach(c => {
        totalRating += c.rating;
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            starsHtml += `<i data-lucide="star" style="width:14px;height:14px;" class="${i <= c.rating ? 'text-amber-400 fill-amber-400' : 'text-gray-600'}"></i>`;
        }

        container.insertAdjacentHTML('beforeend', `
            <div class="bg-brand-dark/20 border border-white/5 p-4 sm:p-5 rounded-2xl space-y-3">
                <div class="flex justify-between items-start gap-2">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-sm text-white">${c.name}</span>
                            <span class="bg-emerald-500/10 text-emerald-400 text-[9px] sm:text-[10px] px-2 py-0.5 rounded border border-emerald-500/20 flex items-center gap-1">
                                <i data-lucide="check-circle" style="width:10px;height:10px;"></i> خریدار (تحویل شده)
                            </span>
                        </div>
                        <span class="text-[10px] text-gray-500 block">${c.date}</span>
                    </div>
                    <div class="flex gap-0.5 direction-ltr shrink-0">${starsHtml}</div>
                </div>
                <p class="text-xs text-gray-300 leading-relaxed">${c.text}</p>
            </div>
        `);
    });

    const avg = (totalRating / filtered.length).toFixed(1);
    updateRatingSummary(avg, filtered.length);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateRatingSummary(avg, count) {
    const avgNum = document.getElementById('avg-rating-num');
    const countEl = document.getElementById('total-comments-count');
    if (avgNum) avgNum.textContent = avg > 0 ? avg : '۰.۰';
    if (countEl) countEl.textContent = `(${count} نظر)`;

    const avgStarsContainer = document.getElementById('avg-stars');
    if (!avgStarsContainer) return;
    avgStarsContainer.innerHTML = '';
    const roundAvg = Math.round(avg);
    for (let i = 1; i <= 5; i++) {
        avgStarsContainer.insertAdjacentHTML('beforeend', `
            <i data-lucide="star" style="width:16px;height:16px;" class="${i <= roundAvg ? 'text-amber-400 fill-amber-400' : 'text-gray-600'}"></i>
        `);
    }
}

function submitComment(event) {
    if (event) event.preventDefault();
    const nameInput = document.getElementById('comment-name');
    const textInput = document.getElementById('comment-text');
    if (!nameInput || !textInput) return;

    const partId = getProductIdFromURL();
    let localComments = JSON.parse(localStorage.getItem('part_comments')) || defaultComments;

    localComments.unshift({
        id: Date.now(),
        partId: partId,
        name: nameInput.value.trim(),
        rating: selectedRating,
        text: textInput.value.trim(),
        date: new Date().toLocaleDateString('fa-IR')
    });

    localStorage.setItem('part_comments', JSON.stringify(localComments));
    nameInput.value = '';
    textInput.value = '';
    setStarRating(5);
    loadComments();
    alert('نظر شما به عنوان خریدار قطعه با موفقیت تایید و ثبت شد.');
}

function filterBlog(category) {
    const items = document.querySelectorAll('#blog-grid > div');
    if (!items.length) return;

    items.forEach(item => {
        const itemCategory = item.getAttribute('data-category');
        if (category === 'all' || itemCategory === category) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });

    const evt = window.event;
    if (evt && evt.target && evt.target.parentElement) {
        evt.target.parentElement.querySelectorAll('button').forEach(btn => {
            btn.className = "bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition";
        });
        evt.target.className = "bg-brand-red text-white text-xs font-bold px-4 py-2.5 rounded-xl transition";
    }
}

function searchBlog() {
    const searchInput = document.getElementById('blog-search');
    if (!searchInput) return;

    const query = searchInput.value.toLowerCase().trim();
    const articles = document.querySelectorAll('#blog-grid > div');

    articles.forEach(article => {
        const titleEl = article.querySelector('h3');
        const descEl = article.querySelector('p');
        const title = titleEl ? titleEl.innerText.toLowerCase() : '';
        const desc = descEl ? descEl.innerText.toLowerCase() : '';

        if (title.includes(query) || desc.includes(query)) {
            article.classList.remove('hidden');
        } else {
            article.classList.add('hidden');
        }
    });
}

let timerInterval;

function switchLoginTab(tab) {
    hideAlert();
    const formLogin = document.getElementById('form-login');
    const formOtp = document.getElementById('form-otp');
    const formSignup = document.getElementById('form-signup');
    const formForgot = document.getElementById('form-forgot');

    const tabLogin = document.getElementById('tab-login');
    const tabOtp = document.getElementById('tab-otp');
    const tabSignup = document.getElementById('tab-signup');

    [tabLogin, tabOtp, tabSignup].forEach(btn => {
        if (btn) {
            btn.className = "flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 text-gray-400 hover:text-white";
        }
    });

    [formLogin, formOtp, formSignup, formForgot].forEach(form => {
        if (form) form.classList.add('hidden');
    });

    if (tab === 'login' && formLogin) {
        formLogin.classList.remove('hidden');
        if (tabLogin) tabLogin.className = "flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 bg-brand-red text-white shadow-lg";
    } else if (tab === 'otp' && formOtp) {
        formOtp.classList.remove('hidden');
        if (tabOtp) tabOtp.className = "flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 bg-brand-red text-white shadow-lg";
    } else if (tab === 'signup' && formSignup) {
        formSignup.classList.remove('hidden');
        if (tabSignup) tabSignup.className = "flex-1 py-3 text-center font-bold text-xs sm:text-sm rounded-xl transition-all duration-300 bg-brand-red text-white shadow-lg";
    } else if (tab === 'forgot' && formForgot) {
        formForgot.classList.remove('hidden');
    }
}

function showAlert(message, type = 'danger') {
    const box = document.getElementById('alert-box');
    if (!box) return;
    box.classList.remove('hidden', 'bg-rose-500/10', 'text-rose-400', 'border-rose-500/20', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');

    if (type === 'danger') {
        box.classList.add('bg-rose-500/10', 'text-rose-400', 'border', 'border-rose-500/20');
    } else {
        box.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border', 'border-emerald-500/20');
    }

    box.innerHTML = message;
}

function hideAlert() {
    const box = document.getElementById('alert-box');
    if (box) box.classList.add('hidden');
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        if (icon) icon.setAttribute('data-lucide', 'eye');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// --- شروع کدهای سیستم احراز هویت یکپارچه ---
let authPhone = '';
let isNewUser = false;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// نمایش و مخفی کردن حالت لودینگ دکمه‌ها
function toggleButtonLoading(btn, isLoading, originalText) {
    if (!btn) return;
    if (isLoading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML; // ذخیره متن اصلی دکمه
        // قرار دادن آیکون در حال چرخش (spinner) و تغییر متن
        btn.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>لطفاً صبر کنید...</span></div>`;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText || originalText; // بازگرداندن متن قبلی
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

async function handleCheckPhone(event) {
    if (event) event.preventDefault();
    const phoneInput = document.getElementById('auth-phone');
    if (!phoneInput) return;

    authPhone = phoneInput.value.trim();
    if (!authPhone.match(/^09[0-9]{9}$/)) {
        showAlert('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789)');
        return;
    }

    const btn = document.querySelector('#step-phone button');
    toggleButtonLoading(btn, true); // ⏳ روشن کردن لودینگ و غیرفعال کردن دکمه

    try {
        const response = await fetch('/api/auth/check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify({ phone: authPhone })
        });
        const result = await response.json();

        toggleButtonLoading(btn, false, 'مرحله بعد'); // ⏳ خاموش کردن لودینگ

        if (response.ok) {
            hideAlert();
            document.getElementById('step-phone').classList.add('hidden');

            if (result.exists) {
                isNewUser = false;
                document.getElementById('step-password').classList.remove('hidden');
                document.getElementById('display-phone-pass').innerText = authPhone;
            } else {
                isNewUser = true;
                requestOtp();
            }
        } else {
            showAlert('❌ ' + result.error);
        }
    } catch (error) {
        toggleButtonLoading(btn, false, 'مرحله بعد'); // ⏳ خاموش کردن لودینگ
        showAlert('خطا در ارتباط با سرور. لطفاً اینترنت خود را بررسی کنید.');
    }
}

async function handleLoginPassword(event) {
    if (event) event.preventDefault();
    const password = document.getElementById('auth-password').value;

    if (!password) {
        showAlert('لطفاً رمز عبور را وارد کنید.');
        return;
    }

    const btn = document.querySelector('#step-password button.bg-brand-red');
    toggleButtonLoading(btn, true); // ⏳ روشن کردن لودینگ

    try {
        const response = await fetch('/api/auth/login-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify({ phone: authPhone, password })
        });
        const result = await response.json();

        toggleButtonLoading(btn, false, 'ورود به حساب'); // ⏳ خاموش کردن لودینگ

        if (response.ok) {
            showAlert('✔ ورود با موفقیت انجام شد.', 'success');
            setTimeout(() => { window.location.href = result.redirect || '/profile'; }, 1500);
        } else {
            showAlert('❌ ' + result.error);
        }
    } catch (error) {
        toggleButtonLoading(btn, false, 'ورود به حساب'); // ⏳ خاموش کردن لودینگ
        showAlert('خطا در ارتباط با سرور. لطفاً اینترنت خود را بررسی کنید.');
    }
}

// ۳. تغییر حالت به ورود با کد یکبار مصرف (برای کاربر قدیمی)
function switchToOtpLogin() {
    document.getElementById('step-password').classList.add('hidden');
    requestOtp();
}

// ۴. درخواست ارسال پیامک (OTP)
async function requestOtp() {
    try {
        const response = await fetch('/api/auth/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify({ phone: authPhone })
        });
        const result = await response.json();

        if (response.ok) {
            document.getElementById('step-otp').classList.remove('hidden');
            document.getElementById('display-phone-otp').innerText = authPhone;

            // اگر کاربر جدید است، فیلدهای نام و رمز عبور را نشان بده
            if (isNewUser) {
                document.getElementById('new-user-fields').classList.remove('hidden');
            }

            showAlert('✔ کد تایید برای شما پیامک شد.', 'success');
            startOTPTimer(119);
        } else {
            showAlert('❌ ' + result.error);
        }
    } catch (error) {
        showAlert('خطا در ارتباط با سرور.');
    }
}

// ۵. تایمر شمارش معکوس پیامک
function startOTPTimer(seconds) {
    if (typeof timerInterval !== 'undefined' && timerInterval) clearInterval(timerInterval);
    const timerEl = document.getElementById('timer-count');
    const resendBtn = document.getElementById('resend-otp-btn');
    if (resendBtn) resendBtn.disabled = true;

    let remaining = seconds;
    window.timerInterval = setInterval(() => {
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        if (timerEl) timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

        if (remaining <= 0) {
            clearInterval(timerInterval);
            if (resendBtn) resendBtn.disabled = false;
            const otpTimer = document.getElementById('otp-timer');
            if (otpTimer) otpTimer.textContent = "کد دریافت نشد؟";
        }
        remaining--;
    }, 1000);
}

async function handleVerifyOtp(event) {
    if (event) event.preventDefault();
    const code = document.getElementById('auth-otp-code').value.trim();

    let payload = { phone: authPhone, code: code };

    // اگر ثبت‌نام است، نام و رمز هم ارسال شود
    if (isNewUser) {
        const fullName = document.getElementById('auth-fullname').value.trim();
        const newPassword = document.getElementById('auth-new-password').value;
        if (!fullName || !newPassword) {
            showAlert('لطفاً نام و رمز عبور خود را وارد کنید.');
            return;
        }
        payload.full_name = fullName;
        payload.password = newPassword;
    }

    if (code.length < 4) {
        showAlert('کد تایید نامعتبر است.');
        return;
    }

    const btn = document.querySelector('#step-otp button.bg-brand-red');
    toggleButtonLoading(btn, true); // ⏳ روشن کردن لودینگ

    try {
        const response = await fetch('/api/auth/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        toggleButtonLoading(btn, false, 'تایید و ادامه'); // ⏳ خاموش کردن لودینگ

        if (response.ok) {
            showAlert('✔ ' + result.message, 'success');
            setTimeout(() => { 
                window.location.href = result.redirect || (isNewUser ? '/parts' : '/profile'); 
            }, 1500);
        } else {
            showAlert('❌ ' + result.error);
        }
    } catch (error) {
        toggleButtonLoading(btn, false, 'تایید و ادامه'); // ⏳ خاموش کردن لودینگ
        showAlert('خطا در ارتباط با سرور. لطفاً اینترنت خود را بررسی کنید.');
    }
}

function switchProfileTab(tabId) {
    document.querySelectorAll('.tab-page').forEach(page => {
        page.classList.add('hidden');
        page.classList.remove('block');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        // کلاس‌های حالت غیرفعال با حاشیه شفاف (برای جلوگیری از پرش)
        btn.className = "tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent";
    });

    const targetContent = document.getElementById('tab-content-' + tabId);
    const targetNav = document.getElementById('nav-' + tabId);

    if (targetContent) {
        targetContent.classList.remove('hidden');
        targetContent.classList.add('block');
    }

    if (targetNav) {
        // کلاس‌های حالت فعال (پس‌زمینه ملایم و حاشیه رنگی)
        targetNav.className = "tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-brand-red bg-brand-red/10 border border-brand-red/30 font-bold text-sm";
    }
}

function switchTab(tabId) {
    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        switchLoginTab(tabId);
        return;
    }
    const targetContent = document.getElementById('tab-content-' + tabId);
    if (targetContent || document.querySelector('.tab-page')) {
        switchProfileTab(tabId);
        return;
    }
}

function filterOrders(status, event = null) {
    // ۱. تغییر رنگ دکمه‌ها (بدون دست زدن به سایز و پدینگ)
    if (event) {
        const buttons = document.querySelectorAll('.order-filter-btn');

        buttons.forEach(btn => {
            // حذف رنگ حالت فعال (قرمز) و اضافه کردن رنگ غیرفعال (خاکستری)
            btn.classList.remove('bg-brand-red', 'text-white');
            btn.classList.add('text-gray-400', 'hover:text-white');
        });

        // پیدا کردن دکمه‌ای که کلیک شده و دادن رنگ فعال به آن
        const clickedBtn = event.currentTarget || event.target;
        clickedBtn.classList.remove('text-gray-400', 'hover:text-white');
        clickedBtn.classList.add('bg-brand-red', 'text-white');
    }

    // ۲. نمایش یا مخفی کردن کارت‌های سفارش
    const cards = document.querySelectorAll('.order-card');
    cards.forEach(card => {
        if (status === 'all' || card.getAttribute('data-status') === status) {
            // نمایش کارت (اگر از flex استفاده میکنید اینجا به جای block کلمه flex را بنویسید)
            card.style.display = 'block';
        } else {
            // مخفی کردن کارت
            card.style.display = 'none';
        }
    });
}

function openOrderDetailModal(orderId) {
    const overlay = document.getElementById('order-detail-modal-overlay');
    const content = document.getElementById('order-detail-modal-content');
    if (!overlay || !content) return;

    content.innerHTML = `
        <button class="absolute top-4 left-4 text-gray-400 hover:text-white" onclick="closeOrderDetailModal()"><i data-lucide="x" style="width:20px;height:20px;"></i></button>
        <div class="space-y-6">
            <div class="flex justify-between items-center border-b border-white/10 pb-4">
                <div>
                    <h3 class="font-extrabold text-lg text-white">جزئیات فاکتور #${orderId}</h3>
                    <span class="text-xs text-gray-400">تاریخ ثبت: ۱۴۰۳/۰۵/۱۱</span>
                </div>
                <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-3 py-1 rounded-full border border-emerald-500/20">پرداخت شده</span>
            </div>

            <div class="space-y-3 text-xs text-gray-300">
                <div class="flex justify-between bg-black/20 p-3 rounded-xl"><span>تحویل گیرنده:</span> <span class="font-bold text-white">علی محمدی (09189998852)</span></div>
                <div class="flex justify-between bg-black/20 p-3 rounded-xl"><span>آدرس ارسال:</span> <span class="text-white">سقز، خیابان ساحلی، روبروی قطعات تویوتا</span></div>
                <div class="flex justify-between bg-black/20 p-3 rounded-xl"><span>روش ارسال:</span> <span class="text-white">تیپاکس اکسپرس (بیمه شده)</span></div>
            </div>

            <div class="space-y-2 border-t border-white/10 pt-4">
                <h4 class="font-bold text-xs text-white">اقلام فاکتور:</h4>
                <div class="bg-black/30 p-3 rounded-xl flex justify-between items-center text-xs">
                    <span class="text-white">لنت ترمز جلو اصلی تویوتا کمری (04465-33470)</span>
                    <span class="font-black text-brand-red">۳,۴۵۰,۰۰۰ تومان</span>
                </div>
                <div class="bg-black/30 p-3 rounded-xl flex justify-between items-center text-xs">
                    <span class="text-white">فیلتر روغن کاغذی اصلی (04152-38010)</span>
                    <span class="font-black text-brand-red">۴۵۰,۰۰۰ تومان</span>
                </div>
            </div>

            <div class="flex justify-between items-center border-t border-white/10 pt-4">
                <span class="text-xs text-gray-400">مبلغ کل فاکتور:</span>
                <span class="font-black text-xl text-emerald-400">۳,۹۰۰,۰۰۰ تومان</span>
            </div>

            <button onclick="window.print()" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs transition flex items-center justify-center gap-2">
                <i data-lucide="printer" style="width:16px;height:16px;"></i> چاپ نسخه رسمی فاکتور
            </button>
        </div>
    `;

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        content.classList.remove('scale-95', 'opacity-0');
    }, 20);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeOrderDetailModal() {
    const overlay = document.getElementById('order-detail-modal-overlay');
    const content = document.getElementById('order-detail-modal-content');
    if (!overlay || !content) return;
    overlay.classList.remove('opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    }, 300);
}

function openAddVehicleModal() {
    const overlay = document.getElementById('add-vehicle-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('hidden'); overlay.classList.add('flex');
    setTimeout(() => { overlay.classList.add('opacity-100'); if (modal) modal.classList.remove('scale-95', 'opacity-0'); }, 20);
}

function closeAddVehicleModal() {
    const overlay = document.getElementById('add-vehicle-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('opacity-100'); if (modal) modal.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }, 300);
}

function handleAddVehicle(event) {
    if (event) event.preventDefault();
    alert('✔ خودرو با موفقیت ثبت شد.');
    closeAddVehicleModal();
}

function openAddAddressModal() {
    const overlay = document.getElementById('add-address-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('hidden'); overlay.classList.add('flex');
    setTimeout(() => { overlay.classList.add('opacity-100'); if (modal) modal.classList.remove('scale-95', 'opacity-0'); }, 20);
}

function closeAddAddressModal() {
    const overlay = document.getElementById('add-address-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('opacity-100'); if (modal) modal.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }, 300);
}

function handleAddAddress(event) {
    if (event) event.preventDefault();
    alert('✔ آدرس با موفقیت ثبت شد.');
    closeAddAddressModal();
}

function openLogoutModal() {
    const overlay = document.getElementById('logout-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('hidden'); overlay.classList.add('flex');
    setTimeout(() => { overlay.classList.add('opacity-100'); if (modal) modal.classList.remove('scale-95', 'opacity-0'); }, 20);
}

function closeLogoutModal() {
    const overlay = document.getElementById('logout-modal-overlay');
    if (!overlay) return;
    const modal = overlay.children[0];
    overlay.classList.remove('opacity-100'); if (modal) modal.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }, 300);
}

function setChargeAmount(amount) {
    const input = document.getElementById('custom-wallet-amount');
    if (input) input.value = amount;
}

function submitWalletCharge() {
    const input = document.getElementById('custom-wallet-amount');
    if (!input || !input.value) {
        alert('لطفاً مبلغ مورد نظر برای شارژ را وارد کنید.');
        return;
    }
    alert(`در حال انتقال به درگاه پرداخت جهت شارژ ${parseInt(input.value).toLocaleString('fa-IR')} تومان...`);
}

function openNewTicketModal() {
    alert('جهت ثبت تیکت جدید یا پشتیبانی تلفنی با شماره 09189998852 تماس بگیرید یا در واتساپ پیام دهید.');
}

function handleSaveSettings(event) {
    if (event) event.preventDefault();
    alert('✔ مشخصات حساب کاربری با موفقیت به‌روزرسانی شد.');
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    initScrollReveal();

    initElementSdk();

    updateCartUI();

    if (document.getElementById('parts-grid')) {
        initFilters();
        applyFilters();
    }

    if (document.getElementById('product-container')) {
        loadProductDetails();
    }
});