let subtotalAmount = 0;
let discountAmount = 0;
let couponMessageTimeout = null;
const cachedCities = {};

document.addEventListener('DOMContentLoaded', async () => {
    const cart = JSON.parse(localStorage.getItem('toyota_cart')) || [];
    const cartDataInput = document.getElementById('cart_data_input');
    if (cartDataInput) {
        cartDataInput.value = JSON.stringify(cart);
    }

    renderCheckoutItems(cart);
    updateUI();
    await initProvinces();
    registerEventListeners();
});

function registerEventListeners() {
    const form = document.getElementById('checkout-form');
    if (form) {
        // حذف خطای کادر قرمز به محض تغییر یا تایپ در فیلدها
        form.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', () => clearFieldHighlight(input));
            input.addEventListener('change', () => clearFieldHighlight(input));
        });

        // مدیریت اعتبارسنجی هنگام کلیک روی ثبت نهایی
        form.addEventListener('submit', (e) => {
            const validation = validateCheckoutFields();
            if (!validation.isValid) {
                e.preventDefault();
                e.stopPropagation();

                highlightAndScrollToField(validation.field, validation.message);
                return false;
            }

            const btn = document.getElementById('submit-order-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>در حال ثبت فاکتور و آپلود رسید...</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    const savedAddressSelect = document.getElementById('saved-addresses-select');
    if (savedAddressSelect) {
        savedAddressSelect.addEventListener('change', (e) => {
            handleAddressSelection(e.target);
        });
    }

    const provinceSelect = document.getElementById('province-select');
    if (provinceSelect) {
        provinceSelect.addEventListener('change', (e) => {
            loadCities(e.target.value);
        });
    }

    const receiptInput = document.getElementById('receipt-file-input');
    if (receiptInput) {
        receiptInput.addEventListener('change', (e) => {
            previewReceiptFile(e.target);
            clearFieldHighlight(e.target);
        });
    }

    const couponBtn = document.getElementById('btn-apply-coupon');
    if (couponBtn) {
        couponBtn.addEventListener('click', validateCouponAjax);
    }

    const couponInput = document.getElementById('discount_code');
    if (couponInput) {
        couponInput.addEventListener('input', () => {
            const msgEl = document.getElementById('discount-message');
            if (msgEl && !msgEl.classList.contains('hidden')) {
                if (!msgEl.classList.contains('text-emerald-500')) {
                    msgEl.classList.add('hidden');
                    if (couponMessageTimeout) clearTimeout(couponMessageTimeout);
                }
            }
        });
    }

    const copyShebaBtn = document.getElementById('btn-copy-sheba');
    if (copyShebaBtn) {
        copyShebaBtn.addEventListener('click', () => {
            copyText(copyShebaBtn.getAttribute('data-copy'), 'toast-sheba');
        });
    }

    const copyCardBtn = document.getElementById('btn-copy-card');
    if (copyCardBtn) {
        copyCardBtn.addEventListener('click', () => {
            copyText(copyCardBtn.getAttribute('data-copy'), 'toast-card');
        });
    }
}

// تابع بررسی و اعتبارسنجی تک‌تک فیلدهای فرم
function validateCheckoutFields() {
    const fields = [
        {
            id: 'rec_name',
            message: 'لطفاً نام و نام‌خانوادگی تحویل‌گیرنده را وارد کنید.',
            validate: el => el.value.trim().length >= 3
        },
        {
            id: 'rec_phone',
            message: 'لطفاً شماره موبایل معتبر وارد کنید (مثال: 09189998852).',
            validate: el => /^09[0-9]{9}$/.test(el.value.trim())
        },
        {
            id: 'province-select',
            message: 'لطفاً استان مقصد را انتخاب کنید.',
            validate: el => el.value.trim() !== ''
        },
        {
            id: 'city-select',
            message: 'لطفاً شهر مقصد را انتخاب کنید.',
            validate: el => el.value.trim() !== ''
        },
        {
            id: 'rec_address_detail',
            message: 'لطفاً نشانی پستی دقیق را وارد کنید.',
            validate: el => el.value.trim().length >= 6
        },
        {
            id: 'rec_postal',
            message: 'کد پستی ۱۰ رقمی الزامی است و باید دقیقاً یک عدد ۱۰ رقمی باشد.',
            validate: el => /^[0-9]{10}$/.test(el.value.trim())
        },
        {
            id: 'receipt-file-input',
            message: 'لطفاً تصویر یا فایل فیش واریز را بارگذاری نمایید.',
            validate: el => el.files && el.files.length > 0
        }
    ];

    for (const item of fields) {
        const el = document.getElementById(item.id);
        if (el && !item.validate(el)) {
            return {
                isValid: false,
                field: el,
                message: item.message
            };
        }
    }

    return { isValid: true };
}

// نمایش کادر قرمز و اسکرول نرم به سمت فیلد جا افتاده
function highlightAndScrollToField(element, message) {
    if (!element) return;

    const target = element.id === 'receipt-file-input'
        ? element.closest('.border-dashed')
        : element;

    if (target) {
        target.classList.add('!border-rose-500', '!ring-2', '!ring-rose-200');
    }

    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

    if (element.focus && element.type !== 'file') {
        element.focus({ preventScroll: true });
    }

    if (typeof showAlert === 'function') {
        showAlert(message, 'danger');
    } else {
        alert(message);
    }
}

function clearFieldHighlight(element) {
    const target = element.id === 'receipt-file-input'
        ? element.closest('.border-dashed')
        : element;

    if (target) {
        target.classList.remove('!border-rose-500', '!ring-2', '!ring-rose-200');
    }
}

async function initProvinces() {
    const provinceSelect = document.getElementById('province-select');
    if (!provinceSelect) return;

    if (provinceSelect.options.length > 1) {
        provinceSelect.disabled = false;
        return;
    }

    provinceSelect.innerHTML = '<option value="">در حال دریافت استان‌ها...</option>';
    provinceSelect.disabled = true;

    try {
        const res = await fetch('/api/locations/provinces');
        if (res.ok) {
            const provinces = await res.json();
            provinceSelect.innerHTML = '<option value="">انتخاب استان...</option>';
            provinces.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.name;
                opt.textContent = p.name;
                opt.dataset.id = p.id;
                provinceSelect.appendChild(opt);
            });
            provinceSelect.disabled = false;
        }
    } catch (e) {
        console.error('Error fetching provinces:', e);
        provinceSelect.innerHTML = '<option value="">خطا در بارگذاری استان‌ها</option>';
    }
}

async function loadCities(provinceName, selectedCity = '') {
    const citySelect = document.getElementById('city-select');
    if (!citySelect) return;

    if (!provinceName) {
        citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
        citySelect.disabled = true;
        return;
    }

    if (cachedCities[provinceName]) {
        populateCitySelect(citySelect, cachedCities[provinceName], selectedCity);
        return;
    }

    citySelect.innerHTML = '<option value="">در حال بارگذاری شهرها...</option>';
    citySelect.disabled = true;

    const provOption = document.querySelector(`#province-select option[value="${provinceName}"]`);
    const stateId = provOption ? provOption.dataset.id : null;

    try {
        const query = stateId
            ? `province_id=${encodeURIComponent(stateId)}`
            : `province=${encodeURIComponent(provinceName)}`;

        const res = await fetch(`/api/locations/cities?${query}`);
        if (res.ok) {
            const cities = await res.json();
            if (Array.isArray(cities) && cities.length > 0) {
                cachedCities[provinceName] = cities;
                populateCitySelect(citySelect, cities, selectedCity);
                return;
            }
        }
        citySelect.innerHTML = '<option value="">شهری برای این استان یافت نشد</option>';
        citySelect.disabled = false;
    } catch (e) {
        console.error('Error fetching cities:', e);
        citySelect.innerHTML = '<option value="">خطا در دریافت اطلاعات</option>';
        citySelect.disabled = false;
    }
}

function populateCitySelect(selectElement, cities, selectedCity) {
    selectElement.innerHTML = '<option value="">انتخاب شهر...</option>';
    cities.forEach(item => {
        const cityName = typeof item === 'object' ? item.name : item;
        const opt = document.createElement('option');
        opt.value = cityName;
        opt.textContent = cityName;
        if (selectedCity && cityName.trim() === selectedCity.trim()) {
            opt.selected = true;
        }
        selectElement.appendChild(opt);
    });
    selectElement.disabled = false;
}

function handleAddressSelection(select) {
    const provinceSelect = document.getElementById('province-select');
    const citySelect = document.getElementById('city-select');
    const addressInput = document.getElementById('rec_address_detail');
    const postalInput = document.getElementById('rec_postal');

    if (!select.value) {
        if (provinceSelect) provinceSelect.value = '';
        if (citySelect) {
            citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
            citySelect.disabled = true;
        }
        if (addressInput) addressInput.value = '';
        if (postalInput) postalInput.value = '';
        return;
    }

    try {
        const addr = JSON.parse(select.value);
        if (addressInput) addressInput.value = addr.address_detail || '';
        if (postalInput) postalInput.value = addr.postal_code || '';

        if (addr.province_city && provinceSelect) {
            const parts = addr.province_city.split(' - ');
            const prov = parts[0] ? parts[0].trim() : '';
            const city = parts[1] ? parts[1].trim() : '';

            provinceSelect.value = prov;
            loadCities(prov, city);
        }
    } catch (e) {
        console.error('Error parsing address JSON:', e);
    }
}

function renderCheckoutItems(cart) {
    const container = document.getElementById('checkout-items-list');
    const badge = document.getElementById('items-count-badge');
    if (!container) return;

    container.innerHTML = '';
    subtotalAmount = 0;
    let totalCount = 0;

    if (cart.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">سبد خرید شما خالی است.</div>';
        if (badge) badge.innerText = '۰ قطعه';
        return;
    }

    cart.forEach(item => {
        const prod = item.product;
        const qty = item.quantity || 1;
        const price = parseFloat(prod.price) || 0;
        const lineTotal = price * qty;

        subtotalAmount += lineTotal;
        totalCount += qty;

        const imgSrc = (prod.images && prod.images[0]) ? '/image?id=' + encodeURIComponent(prod.images[0]) : '/assets/logo/logo.webp';

        const html = `
            <div class="flex items-center gap-3 bg-[#F8F6F0] border border-[#E8E2D9] p-3 rounded-2xl">
                <img src="${imgSrc}" alt="${escapeHtml(prod.name)}" class="w-14 h-14 object-contain rounded-xl bg-white p-1 border border-[#E8E2D9] shrink-0">
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-xs text-[#251E1B] truncate mb-0.5">${escapeHtml(prod.name)}</h4>
                    <div class="flex items-center justify-between text-[11px] text-[#5F605C]">
                        <span>کد فنی: <span class="font-mono text-[#251E1B]" dir="ltr">${escapeHtml(prod.oem || '---')}</span></span>
                        <span class="bg-white px-2 py-0.5 rounded text-[#251E1B] font-bold border border-[#E8E2D9]">${qty} عدد</span>
                    </div>
                    <div class="text-left mt-1">
                        <span class="text-xs font-bold text-emerald-600">${lineTotal.toLocaleString('fa-IR')} تومان</span>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    });

    if (badge) badge.innerText = `${totalCount} قطعه`;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateUI() {
    const finalPrice = Math.max(0, subtotalAmount - discountAmount);
    const cartSubtotalEl = document.getElementById('cart-subtotal');
    const finalPriceEl = document.getElementById('final-price');

    if (cartSubtotalEl) cartSubtotalEl.innerText = subtotalAmount.toLocaleString('fa-IR') + ' تومان';
    if (finalPriceEl) finalPriceEl.innerText = finalPrice.toLocaleString('fa-IR') + ' تومان';

    const payableBadge = document.getElementById('instruction-payable-amount');
    if (payableBadge) {
        payableBadge.innerText = finalPrice.toLocaleString('fa-IR') + ' تومان';
    }

    const discountRow = document.getElementById('discount-row');
    const discountAmountEl = document.getElementById('discount-amount');
    if (discountRow && discountAmountEl) {
        if (discountAmount > 0) {
            discountRow.classList.remove('hidden');
            discountAmountEl.innerText = discountAmount.toLocaleString('fa-IR') + ' تومان';
        } else {
            discountRow.classList.add('hidden');
        }
    }
}

function showCouponMessage(message, type = 'error', autoHideDuration = 5000) {
    const msgEl = document.getElementById('discount-message');
    if (!msgEl) return;

    if (couponMessageTimeout) {
        clearTimeout(couponMessageTimeout);
        couponMessageTimeout = null;
    }

    let colorClasses = '';
    if (type === 'success') {
        colorClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
    } else if (type === 'warning') {
        colorClasses = 'bg-amber-50 text-amber-700 border-amber-200';
    } else {
        colorClasses = 'bg-rose-50 text-rose-700 border-rose-200';
    }

    msgEl.className = `mt-2 text-xs font-bold px-3 py-2 rounded-xl border block transition-opacity duration-300 ${colorClasses}`;
    msgEl.innerText = message;
    msgEl.classList.remove('hidden');

    if (autoHideDuration > 0 && type !== 'success') {
        couponMessageTimeout = setTimeout(() => {
            msgEl.classList.add('hidden');
        }, autoHideDuration);
    }
}

async function validateCouponAjax() {
    const couponInput = document.getElementById('discount_code');
    const code = couponInput ? couponInput.value.trim() : '';
    const appliedInput = document.getElementById('applied_discount_input');
    const btn = document.getElementById('btn-apply-coupon');

    if (!code) {
        showCouponMessage('لطفاً کد تخفیف را وارد کنید.', 'warning', 5000);
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerText = '...';
    }

    try {
        const res = await fetch('/api/checkout/validate-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': typeof getCsrfToken === 'function' ? getCsrfToken() : ''
            },
            body: JSON.stringify({ code: code, subtotal: subtotalAmount })
        });

        const data = await res.json();

        if (btn) {
            btn.disabled = false;
            btn.innerText = 'اعمال کد';
        }

        if (res.ok && data.valid) {
            discountAmount = parseFloat(data.discount) || 0;
            if (appliedInput) appliedInput.value = data.code;
            showCouponMessage(data.message, 'success', 0);
        } else {
            discountAmount = 0;
            if (appliedInput) appliedInput.value = '';
            showCouponMessage(data.message || 'کد تخفیف وارد شده معتبر نیست.', 'error', 5000);
        }
        updateUI();
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'اعمال کد';
        }
        showCouponMessage('خطا در برقراری ارتباط با سرور.', 'error', 5000);
    }
}

function previewReceiptFile(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const placeholder = document.getElementById('upload-placeholder');
        const previewContainer = document.getElementById('file-preview-container');
        const previewImg = document.getElementById('receipt-preview-img');
        const nameDisplay = document.getElementById('file-name-display');

        if (nameDisplay) {
            nameDisplay.innerText = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        }

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(file);
        } else {
            if (previewImg) previewImg.classList.add('hidden');
        }

        if (placeholder) placeholder.classList.add('hidden');
        if (previewContainer) previewContainer.classList.remove('hidden');
    }
}

function copyText(text, toastId) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('opacity-0');
            toast.classList.add('opacity-100');
            setTimeout(() => {
                toast.classList.remove('opacity-100');
                toast.classList.add('opacity-0');
            }, 2000);
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}