/**
 * پنل کاربری پرادو یدک — تعاملات کلاینت
 * داده اولیه از SSR می‌آید؛ عملیات CRUD از طریق API
 */
(function () {
    'use strict';

    const csrf = () =>
        (typeof getCsrfToken === 'function' ? getCsrfToken() : '') ||
        (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

    async function api(url, options = {}) {
        const opts = {
            method: options.method || 'GET',
            headers: {
                Accept: 'application/json',
                'X-CSRF-Token': csrf(),
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
            credentials: 'same-origin',
            ...options,
        };

        if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
            opts.body = JSON.stringify(opts.body);
        }

        const res = await fetch(url, opts);
        let data = {};
        try {
            data = await res.json();
        } catch (_) {
            data = { success: false, message: 'پاسخ نامعتبر از سرور' };
        }

        if (res.status === 401) {
            window.location.href = '/login';
            throw new Error('unauthorized');
        }

        return { ok: res.ok, status: res.status, data };
    }

    function toast(message, type = 'success') {
        if (typeof showAlert === 'function') {
            showAlert(message, type === 'success' ? 'success' : 'danger');
            return;
        }
        alert(message);
    }

    function faNum(n) {
        return Number(n || 0).toLocaleString('fa-IR');
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function setBtnLoading(btn, loading, fallbackText) {
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML =
                '<span class="inline-flex items-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> لطفاً صبر کنید...</span>';
            refreshIcons();
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml || fallbackText || 'ثبت';
            refreshIcons();
        }
    }

    /* ---------- Tabs ---------- */
    window.switchProfileTab = function (tab) {
        document.querySelectorAll('.tab-page').forEach((el) => {
            el.classList.add('hidden');
            el.classList.remove('block');
        });

        document.querySelectorAll('.tab-btn').forEach((el) => {
            el.className =
                'tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent';
        });

        const page = document.getElementById('tab-content-' + tab);
        const nav = document.getElementById('nav-' + tab);
        if (page) {
            page.classList.remove('hidden');
            page.classList.add('block');
        }
        if (nav) {
            nav.className =
                'tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-brand-red bg-brand-red/10 border border-brand-red/30 font-bold text-sm';
        }

        // به‌روزرسانی URL بدون رفرش
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        } catch (_) { }

        // بستن منوی موبایل در صورت باز بودن
        const mobileNav = document.getElementById('profile-mobile-nav');
        if (mobileNav) mobileNav.classList.add('hidden');
    };

    // سازگاری با main.js
    window.switchTab = function (tab) {
        if (document.getElementById('tab-content-' + tab)) {
            window.switchProfileTab(tab);
        } else if (typeof switchLoginTab === 'function') {
            switchLoginTab(tab);
        }
    };

    /* ---------- Orders filter ---------- */
    window.filterOrders = function (status, evt) {
        if (evt) {
            document.querySelectorAll('.order-filter-btn').forEach((btn) => {
                btn.classList.remove('bg-brand-red', 'text-white');
                btn.classList.add('text-gray-400', 'hover:text-white');
            });
            const target = evt.currentTarget || evt.target;
            if (target) {
                target.classList.remove('text-gray-400', 'hover:text-white');
                target.classList.add('bg-brand-red', 'text-white');
            }
        }

        document.querySelectorAll('.order-card').forEach((card) => {
            const s = card.getAttribute('data-status');
            card.style.display = status === 'all' || s === status ? '' : 'none';
        });

        const empty = document.getElementById('orders-empty-filter');
        if (empty) {
            const visible = [...document.querySelectorAll('.order-card')].some(
                (c) => c.style.display !== 'none'
            );
            empty.classList.toggle('hidden', visible);
        }
    };

    /* ---------- Official invoice helpers ---------- */
    function getShopInfo() {
        return window.__PROFILE_SHOP__ || {};
    }
    function getProfileUser() {
        return window.__PROFILE_USER__ || {};
    }

    function buildInvoiceHtml(o) {
        const shop = getShopInfo();
        const buyer = getProfileUser();
        const dateShamsi = o.created_at_shamsi || o.created_at || '—';
        const invoiceNo = o.tracking_code || ('INV-' + o.id);

        let rows = '';
        let rowNum = 0;
        (o.items || []).forEach((it) => {
            rowNum += 1;
            rows += `
        <tr>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:center;">${faNum(rowNum)}</td>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:right;">${escapeHtml(it.name || '—')}</td>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:center;font-family:monospace;direction:ltr;">${escapeHtml(it.oem || '—')}</td>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:center;">${faNum(it.quantity || 0)}</td>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:left;direction:ltr;">${faNum(it.price || 0)}</td>
          <td style="border:1px solid #ccc;padding:6px 8px;text-align:left;direction:ltr;font-weight:700;">${faNum(it.line_total || 0)}</td>
        </tr>`;
        });

        if (!rows) {
            rows = `<tr><td colspan="6" style="border:1px solid #ccc;padding:12px;text-align:center;color:#666;">آیتمی ثبت نشده است</td></tr>`;
        }

        const economic = shop.economic_code
            ? `<div>کد اقتصادی فروشنده: <strong dir="ltr">${escapeHtml(shop.economic_code)}</strong></div>`
            : '';
        const shopNid = shop.national_id
            ? `<div>شناسه/کد ملی فروشنده: <strong dir="ltr">${escapeHtml(shop.national_id)}</strong></div>`
            : '';
        const regNo = shop.registration_number
            ? `<div>شماره ثبت: <strong dir="ltr">${escapeHtml(shop.registration_number)}</strong></div>`
            : '';
        const buyerNid = buyer.national_id
            ? `<div>کد ملی خریدار: <strong dir="ltr">${escapeHtml(buyer.national_id)}</strong></div>`
            : `<div style="color:#666;">کد ملی خریدار: ثبت‌نشده (اختیاری)</div>`;

        return `
      <div id="invoice-print-root" class="invoice-sheet" style="background:#fff;color:#111;border-radius:16px;overflow:hidden;">
        <!-- هدر فروشگاه -->
        <div style="border-bottom:3px solid #EB0A1E;padding:18px 20px 14px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start;">
          <div style="text-align:right;flex:1;min-width:200px;">
            <div style="font-size:20px;font-weight:900;color:#1a120f;">${escapeHtml(shop.title || 'پرادو یدک')}</div>
            <div style="font-size:11px;color:#666;letter-spacing:1px;margin-top:2px;">${escapeHtml(shop.subtitle || 'PRADO YADAK')}</div>
            <div style="font-size:11px;color:#333;margin-top:10px;line-height:1.8;">
              ${shop.address ? `<div>نشانی: ${escapeHtml(shop.address)}</div>` : ''}
              ${shop.phone ? `<div>تلفن: <span dir="ltr">${escapeHtml(shop.phone)}</span></div>` : ''}
              ${shop.postal_code ? `<div>کد پستی: <span dir="ltr">${escapeHtml(shop.postal_code)}</span></div>` : ''}
              ${shop.website ? `<div>وب‌سایت: <span dir="ltr">${escapeHtml(shop.website)}</span></div>` : ''}
              ${economic}${shopNid}${regNo}
            </div>
          </div>
          <div style="text-align:left;min-width:180px;">
            <div style="display:inline-block;background:#EB0A1E;color:#fff;font-weight:800;font-size:13px;padding:6px 14px;border-radius:8px;margin-bottom:10px;">فاکتور فروش / صورتحساب</div>
            <div style="font-size:12px;line-height:1.9;color:#222;">
              <div>شماره فاکتور: <strong style="font-family:monospace;direction:ltr;color:#EB0A1E;">${escapeHtml(invoiceNo)}</strong></div>
              <div>تاریخ صدور: <strong>${escapeHtml(dateShamsi)}</strong></div>
              <div>وضعیت: <strong>${escapeHtml(o.status_label || '—')}</strong></div>
            </div>
          </div>
        </div>

        <!-- خریدار / گیرنده -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:14px 20px;font-size:12px;border-bottom:1px solid #e5e5e5;">
          <div style="background:#f7f4f0;border:1px solid #e8e2d9;border-radius:10px;padding:12px;">
            <div style="font-weight:800;font-size:11px;color:#8B533A;margin-bottom:8px;">مشخصات خریدار / سفارش‌دهنده</div>
            <div style="line-height:1.85;color:#222;">
              <div>نام: <strong>${escapeHtml(buyer.full_name || o.recipient_name || '—')}</strong></div>
              <div>موبایل: <strong dir="ltr">${escapeHtml(buyer.phone || '—')}</strong></div>
              ${buyerNid}
              ${buyer.city ? `<div>شهر: <strong>${escapeHtml(buyer.city)}</strong></div>` : ''}
            </div>
          </div>
          <div style="background:#f7f4f0;border:1px solid #e8e2d9;border-radius:10px;padding:12px;">
            <div style="font-weight:800;font-size:11px;color:#8B533A;margin-bottom:8px;">مشخصات تحویل‌گیرنده و ارسال</div>
            <div style="line-height:1.85;color:#222;">
              <div>گیرنده: <strong>${escapeHtml(o.recipient_name || '—')}</strong></div>
              <div>تلفن: <strong dir="ltr">${escapeHtml(o.recipient_phone || '—')}</strong></div>
              <div>آدرس: ${escapeHtml(o.shipping_address || '—')}</div>
              ${o.postal_code ? `<div>کد پستی: <strong dir="ltr">${escapeHtml(o.postal_code)}</strong></div>` : ''}
            </div>
          </div>
        </div>

        <!-- جدول اقلام -->
        <div style="padding:14px 20px 8px;">
          <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
              <tr style="background:#251E1B;color:#fff;">
                <th style="border:1px solid #251E1B;padding:8px;width:36px;">ردیف</th>
                <th style="border:1px solid #251E1B;padding:8px;text-align:right;">شرح کالا / قطعه</th>
                <th style="border:1px solid #251E1B;padding:8px;width:110px;">کد فنی (OEM)</th>
                <th style="border:1px solid #251E1B;padding:8px;width:50px;">تعداد</th>
                <th style="border:1px solid #251E1B;padding:8px;width:90px;">فی (تومان)</th>
                <th style="border:1px solid #251E1B;padding:8px;width:100px;">مبلغ (تومان)</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>

        <!-- جمع‌ها -->
        <div style="padding:8px 20px 14px;display:flex;justify-content:flex-end;">
          <div style="min-width:260px;font-size:12px;line-height:2;">
            <div style="display:flex;justify-content:space-between;border-bottom:1px dashed #ddd;padding:4px 0;">
              <span>جمع کل اقلام</span><strong dir="ltr">${faNum(o.subtotal || 0)} تومان</strong>
            </div>
            ${Number(o.discount_amount) > 0
                ? `<div style="display:flex;justify-content:space-between;border-bottom:1px dashed #ddd;padding:4px 0;color:#059669;">
                    <span>تخفیف ${o.applied_coupon ? '(' + escapeHtml(o.applied_coupon) + ')' : ''}</span>
                    <strong dir="ltr">− ${faNum(o.discount_amount)} تومان</strong>
                  </div>`
                : ''
            }
            <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:14px;font-weight:900;color:#111;border-top:2px solid #EB0A1E;margin-top:6px;">
              <span>مبلغ قابل پرداخت</span><span dir="ltr" style="color:#EB0A1E;">${faNum(o.total_amount || 0)} تومان</span>
            </div>
          </div>
        </div>

        ${o.user_notes
                ? `<div style="padding:0 20px 12px;font-size:11px;color:#444;"><strong>یادداشت سفارش:</strong> ${escapeHtml(o.user_notes)}</div>`
                : ''
            }

        <!-- شرایط و اعتبار -->
        <div style="margin:0 20px 16px;padding:12px 14px;background:#faf7f2;border:1px solid #e8e2d9;border-radius:10px;font-size:10px;line-height:1.9;color:#333;">
          <div style="font-weight:800;margin-bottom:4px;color:#1a120f;">شرایط و اعتبار این صورتحساب</div>
          <ul style="margin:0;padding-right:16px;">
            <li>این برگه به‌عنوان صورتحساب/فاکتور فروش فروشگاه «${escapeHtml(shop.title || 'پرادو یدک')}» صادر شده و مشخصات فروشنده، خریدار، اقلام، تعداد و مبالغ در آن درج گردیده است.</li>
            <li>اصالت قطعات جنیون/OEM مطابق سیاست فروشگاه تضمین می‌شود؛ شرایط مرجوعی طبق صفحه قوانین سایت است.</li>
            <li>هزینه ارسال (پس‌کرایه) ممکن است جداگانه هنگام تحویل محاسبه شود و در این فاکتور لحاظ نشده باشد.</li>
            <li>برای استناد اداری/حقوقی، شماره فاکتور و تاریخ صدور را نزد خود نگه دارید.</li>
          </ul>
          ${shop.bank_name || shop.bank_sheba || shop.bank_owner
                ? `<div style="margin-top:8px;padding-top:8px;border-top:1px dashed #d5cac0;">
                  <strong>اطلاعات حساب واریز:</strong>
                  ${shop.bank_name ? ` بانک ${escapeHtml(shop.bank_name)}` : ''}
                  ${shop.bank_owner ? ` — به نام ${escapeHtml(shop.bank_owner)}` : ''}
                  ${shop.bank_sheba ? ` — شبا: <span dir="ltr" style="font-family:monospace;">${escapeHtml(shop.bank_sheba)}</span>` : ''}
                </div>`
                : ''
            }
        </div>

        <!-- امضا -->
        <div style="display:flex;justify-content:space-between;gap:24px;padding:8px 28px 20px;font-size:11px;color:#444;">
          <div style="text-align:center;flex:1;">
            <div style="height:48px;border-bottom:1px solid #ccc;margin-bottom:6px;"></div>
            مهر و امضای فروشنده
          </div>
          <div style="text-align:center;flex:1;">
            <div style="height:48px;border-bottom:1px solid #ccc;margin-bottom:6px;"></div>
            امضای خریدار
          </div>
        </div>

        <div style="text-align:center;font-size:9px;color:#888;padding:0 16px 14px;border-top:1px solid #eee;padding-top:10px;">
          سند الکترونیکی صادرشده از سامانه ${escapeHtml(shop.title || 'پرادو یدک')} — ${escapeHtml(shop.website || '')}
        </div>
      </div>`;
    }

    /* ---------- Order detail modal ---------- */
    window.openOrderDetailModal = async function (orderId) {
        const overlay = document.getElementById('order-detail-modal-overlay');
        const content = document.getElementById('order-detail-modal-content');
        if (!overlay || !content) return;

        content.innerHTML = `
      <div class="flex flex-col items-center justify-center py-16 gap-3 no-print">
        <i data-lucide="loader-2" class="w-10 h-10 animate-spin text-brand-red"></i>
        <span class="text-sm text-gray-400">در حال دریافت جزئیات فاکتور...</span>
      </div>`;
        refreshIcons();

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        requestAnimationFrame(() => {
            overlay.classList.add('opacity-100');
            content.classList.remove('scale-95', 'opacity-0');
        });

        try {
            const { ok, data } = await api('/api/profile/orders/detail?id=' + encodeURIComponent(orderId));
            if (!ok || !data.success) {
                content.innerHTML = `
          <button type="button" class="absolute top-4 left-4 text-gray-400 hover:text-white no-print" aria-label="بستن جزئیات سفارش" onclick="closeOrderDetailModal()">
            <i data-lucide="x" style="width:20px;height:20px;"></i>
          </button>
          <div class="text-center py-12 text-rose-400 text-sm no-print">${escapeHtml(data.message || 'خطا در دریافت سفارش')}</div>`;
                refreshIcons();
                return;
            }

            const o = data.order;
            const colorMap = {
                amber: 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                blue: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                emerald: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                rose: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
                gray: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
            };
            const badge = colorMap[o.status_color] || colorMap.gray;

            content.innerHTML = `
        <button type="button" class="absolute top-3 left-3 z-20 text-gray-500 hover:text-black p-1.5 rounded-lg hover:bg-black/5 transition no-print" onclick="closeOrderDetailModal()" aria-label="بستن جزئیات سفارش">
          <i data-lucide="x" style="width:20px;height:20px;"></i>
        </button>

        <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-[#E8E2D9] pr-1 pl-10">
          <div>
            <h3 class="font-extrabold text-base text-[#251E1B]">پیش‌نمایش فاکتور رسمی</h3>
            <p class="text-[11px] text-[#5F605C] mt-0.5">این نسخه برای چاپ و استناد اداری/حقوقی تنظیم شده است.</p>
          </div>
          <span class="text-[11px] font-bold px-3 py-1 rounded-full border ${badge}">${escapeHtml(o.status_label || '')}</span>
        </div>

        ${buildInvoiceHtml(o)}

        <div class="no-print mt-5 flex flex-col sm:flex-row gap-2">
          <button type="button" onclick="printOfficialInvoice()"
                  class="flex-1 bg-brand-red hover:bg-red-700 !text-white font-bold py-3 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-md">
            <i data-lucide="printer" style="width:16px;height:16px;"></i>
            چاپ فاکتور رسمی
          </button>
          <button type="button" onclick="closeOrderDetailModal()"
                  class="sm:w-32 bg-[#2A201C] border border-white/20 !text-white font-bold py-3 rounded-xl text-xs transition hover:bg-black/80">
            بستن
          </button>
        </div>`;
            refreshIcons();
        } catch (e) {
            console.error(e);
            content.innerHTML = `
        <button type="button" class="absolute top-4 left-4 text-gray-400 hover:text-white no-print" aria-label="بستن جزئیات سفارش" onclick="closeOrderDetailModal()">
          <i data-lucide="x" style="width:20px;height:20px;"></i>
        </button>
        <div class="text-center py-12 text-rose-400 text-sm no-print">خطا در ارتباط با سرور</div>`;
            refreshIcons();
        }
    };

    /** چاپ فقط فاکتور — بدون تکرار هدر/فوتر/مودال */
    window.printOfficialInvoice = function () {
        const sheet = document.getElementById('invoice-print-root');
        if (!sheet) {
            toast('فاکتور برای چاپ آماده نیست.', 'danger');
            return;
        }

        // انتقال موقت فاکتور به body تا فقط همان چاپ شود
        const placeholder = document.createElement('div');
        placeholder.id = 'invoice-print-placeholder';
        sheet.parentNode.insertBefore(placeholder, sheet);
        document.body.appendChild(sheet);
        document.body.classList.add('printing-invoice');

        const cleanup = () => {
            document.body.classList.remove('printing-invoice');
            if (placeholder.parentNode) {
                placeholder.parentNode.insertBefore(sheet, placeholder);
                placeholder.remove();
            }
            window.removeEventListener('afterprint', cleanup);
        };

        window.addEventListener('afterprint', cleanup);
        // fallback اگر afterprint پشتیبانی نشد
        setTimeout(() => {
            if (document.body.classList.contains('printing-invoice')) cleanup();
        }, 1500);

        window.print();
    };

    window.closeOrderDetailModal = function () {
        const overlay = document.getElementById('order-detail-modal-overlay');
        const content = document.getElementById('order-detail-modal-content');
        if (!overlay || !content) return;
        overlay.classList.remove('opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 300);
    };

    /* ---------- Modal helpers ---------- */
    function openModal(overlayId) {
        const overlay = document.getElementById(overlayId);
        if (!overlay) return;
        const box = overlay.children[0];
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        requestAnimationFrame(() => {
            overlay.classList.add('opacity-100');
            if (box) box.classList.remove('scale-95', 'opacity-0');
        });
        refreshIcons();
    }

    function closeModal(overlayId) {
        const overlay = document.getElementById(overlayId);
        if (!overlay) return;
        const box = overlay.children[0];
        overlay.classList.remove('opacity-100');
        if (box) box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }, 300);
    }

    function selectVehicleModelOption(modelName) {
        const sel = document.getElementById('veh-model');
        if (!sel) return;
        const target = String(modelName || '').trim();
        let matched = false;
        [...sel.options].forEach((opt) => {
            const name = (opt.getAttribute('data-name') || opt.textContent || '').trim();
            if (target && (name === target || opt.value === target)) {
                opt.selected = true;
                matched = true;
            }
        });
        if (!matched) sel.value = '';
    }

    window.openAddVehicleModal = function (editData) {
        const form = document.getElementById('vehicle-form');
        if (form) form.reset();
        document.getElementById('vehicle-form-id').value = '';
        document.getElementById('vehicle-modal-title').textContent = 'ثبت خودرو جدید';
        const primaryChk = document.getElementById('veh-is-primary');
        if (primaryChk) primaryChk.checked = false;

        if (editData && typeof editData === 'object') {
            document.getElementById('vehicle-form-id').value = editData.id || '';
            selectVehicleModelOption(editData.model_name || '');
            document.getElementById('veh-year').value = editData.model_year || '';
            document.getElementById('veh-trim').value = editData.trim_name || '';
            document.getElementById('veh-vin').value = editData.vin || '';
            document.getElementById('veh-engine').value = editData.engine_code || '';
            document.getElementById('veh-notes').value = editData.notes || '';
            document.getElementById('vehicle-modal-title').textContent = 'ویرایش خودرو';
        }
        openModal('add-vehicle-modal-overlay');
    };

    window.closeAddVehicleModal = () => closeModal('add-vehicle-modal-overlay');

    window.openAddAddressModal = function (editData) {
        const form = document.getElementById('address-form');
        if (form) form.reset();
        document.getElementById('address-form-id').value = '';
        document.getElementById('address-modal-title').textContent = 'ثبت آدرس جدید';
        const defCheck = document.getElementById('addr-is-default');
        if (defCheck) defCheck.checked = false;

        if (editData && typeof editData === 'object') {
            document.getElementById('address-form-id').value = editData.id || '';
            document.getElementById('addr-province-city').value = editData.province_city || '';
            document.getElementById('addr-detail').value = editData.address_detail || '';
            document.getElementById('addr-postal').value = editData.postal_code || '';
            document.getElementById('addr-name').value = editData.recipient_name || '';
            document.getElementById('addr-phone').value = editData.recipient_phone || '';
            document.getElementById('address-modal-title').textContent = 'ویرایش آدرس';
        }
        openModal('add-address-modal-overlay');
    };

    window.closeAddAddressModal = () => closeModal('add-address-modal-overlay');

    window.openLogoutModal = () => openModal('logout-modal-overlay');
    window.closeLogoutModal = () => closeModal('logout-modal-overlay');

    window.openNewTicketModal = function () {
        const form = document.getElementById('ticket-form');
        if (form) form.reset();
        openModal('new-ticket-modal-overlay');
    };
    window.closeNewTicketModal = () => closeModal('new-ticket-modal-overlay');

    window.openTicketDetailModal = async function (ticketId) {
        const overlay = document.getElementById('ticket-detail-modal-overlay');
        const content = document.getElementById('ticket-detail-content');
        if (!overlay || !content) return;

        content.innerHTML = `<div class="py-12 text-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-brand-red mx-auto"></i></div>`;
        refreshIcons();
        openModal('ticket-detail-modal-overlay');

        try {
            const { ok, data } = await api('/api/profile/tickets/detail?id=' + encodeURIComponent(ticketId));
            if (!ok || !data.success) {
                content.innerHTML = `<p class="text-center text-rose-400 py-8 text-sm">${escapeHtml(data.message || 'خطا')}</p>`;
                return;
            }

            const t = data.ticket;
            let msgs = '';
            (data.messages || []).forEach((m) => {
                const isUser = m.sender_type === 'user';
                msgs += `
          <div class="flex ${isUser ? 'justify-start' : 'justify-end'}">
            <div class="max-w-[85%] rounded-2xl p-3.5 text-xs leading-relaxed ${isUser
                        ? 'bg-brand-dark border border-white/10 text-gray-300'
                        : 'bg-brand-red/10 border border-brand-red/20 text-white'
                    }">
              <div class="flex justify-between gap-4 mb-1.5">
                <strong class="text-[11px] ${isUser ? 'text-brand-red' : 'text-emerald-400'}">${escapeHtml(m.sender_name || (isUser ? 'شما' : 'پشتیبانی'))}</strong>
                <span class="text-[10px] text-gray-500">${escapeHtml(m.created_at_shamsi || '')}</span>
              </div>
              <p class="whitespace-pre-wrap">${escapeHtml(m.message)}</p>
            </div>
          </div>`;
            });

            const canReply = t.status !== 'closed';

            content.innerHTML = `
        <div class="flex items-start justify-between gap-3 border-b border-white/10 pb-4 mb-4">
          <div>
            <h3 class="font-black text-base text-white">${escapeHtml(t.subject)}</h3>
            <span class="text-[11px] text-gray-500 mt-1 block">#${t.id} — ${escapeHtml(t.status_label)} — ${escapeHtml(t.created_at_shamsi || '')}</span>
          </div>
          <button type="button" onclick="closeTicketDetailModal()" aria-label="بستن جزئیات تیکت" class="text-gray-400 hover:text-white p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div id="ticket-messages-list" class="space-y-3 max-h-[40vh] overflow-y-auto mb-4 pr-1">${msgs || '<p class="text-center text-gray-500 text-xs py-6">پیامی نیست</p>'}</div>
        ${canReply
                    ? `<form id="ticket-reply-form" class="space-y-3 border-t border-white/10 pt-4" onsubmit="handleTicketReply(event, ${t.id})">
                <textarea id="ticket-reply-text" rows="3" required minlength="5" placeholder="پاسخ خود را بنویسید..."
                  class="w-full bg-brand-dark border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red resize-none"></textarea>
                <div class="flex gap-2">
                  <button type="submit" class="flex-1 bg-brand-red hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs transition">ارسال پاسخ</button>
                  <button type="button" onclick="handleCloseTicket(${t.id})" class="px-4 py-3 rounded-xl border border-white/10 text-gray-400 hover:text-white text-xs font-bold transition">بستن تیکت</button>
                </div>
              </form>`
                    : `<div class="text-center text-xs text-gray-500 border-t border-white/10 pt-4">این تیکت بسته شده است.</div>`
                }`;
            refreshIcons();
        } catch (e) {
            content.innerHTML = `<p class="text-center text-rose-400 py-8 text-sm">خطا در ارتباط</p>`;
        }
    };

    window.closeTicketDetailModal = () => closeModal('ticket-detail-modal-overlay');

    /* ---------- Vehicles ---------- */
    window.handleAddVehicle = async function (e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const id = document.getElementById('vehicle-form-id').value;
        const modelSel = document.getElementById('veh-model');
        const modelSlug = modelSel ? modelSel.value.trim() : '';
        const selectedOpt = modelSel && modelSel.selectedIndex >= 0 ? modelSel.options[modelSel.selectedIndex] : null;
        const modelName = selectedOpt
            ? (selectedOpt.getAttribute('data-name') || selectedOpt.textContent || '').trim()
            : '';

        if (!modelSlug) {
            toast('لطفاً مدل خودرو را از لیست انتخاب کنید.', 'danger');
            return;
        }

        const payload = {
            model_slug: modelSlug,
            model_name: modelName,
            model_year: document.getElementById('veh-year').value.trim(),
            trim_name: document.getElementById('veh-trim').value.trim(),
            vin: document.getElementById('veh-vin').value.trim(),
            engine_code: document.getElementById('veh-engine').value.trim(),
            notes: document.getElementById('veh-notes').value.trim(),
            is_primary: document.getElementById('veh-is-primary')?.checked || false,
        };

        setBtnLoading(btn, true);
        try {
            const url = id ? '/api/profile/vehicles/update' : '/api/profile/vehicles/create';
            const body = id ? { ...payload, id: parseInt(id, 10) } : payload;
            const { ok, data } = await api(url, { method: 'POST', body });
            setBtnLoading(btn, false, id ? 'ذخیره تغییرات' : 'ثبت خودرو');

            if (ok && data.success) {
                toast(data.message, 'success');
                closeAddVehicleModal();
                setTimeout(() => location.reload(), 800);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (err) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط با سرور', 'danger');
        }
    };

    window.editVehicle = function (btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-vehicle') || '{}');
            openAddVehicleModal(data);
        } catch (_) {
            toast('خطا در خواندن اطلاعات خودرو', 'danger');
        }
    };

    window.deleteVehicle = async function (id) {
        if (!confirm('آیا از حذف این خودرو مطمئن هستید؟')) return;
        try {
            const { ok, data } = await api('/api/profile/vehicles/delete', {
                method: 'POST',
                body: { id },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.setPrimaryVehicle = async function (id) {
        try {
            const { ok, data } = await api('/api/profile/vehicles/set-primary', {
                method: 'POST',
                body: { id },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
        }
    };

    /* ---------- Addresses ---------- */
    window.handleAddAddress = async function (e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const id = document.getElementById('address-form-id').value;

        const payload = {
            province_city: document.getElementById('addr-province-city').value.trim(),
            address_detail: document.getElementById('addr-detail').value.trim(),
            postal_code: document.getElementById('addr-postal').value.trim(),
            recipient_name: document.getElementById('addr-name').value.trim(),
            recipient_phone: document.getElementById('addr-phone').value.trim(),
            is_default: document.getElementById('addr-is-default')?.checked || false,
        };

        setBtnLoading(btn, true);
        try {
            const url = id ? '/api/profile/addresses/update' : '/api/profile/addresses/create';
            const body = id ? { ...payload, id: parseInt(id, 10) } : payload;
            const { ok, data } = await api(url, { method: 'POST', body });
            setBtnLoading(btn, false, id ? 'ذخیره تغییرات' : 'ثبت آدرس');

            if (ok && data.success) {
                toast(data.message, 'success');
                closeAddAddressModal();
                setTimeout(() => location.reload(), 800);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط با سرور', 'danger');
        }
    };

    window.editAddress = function (btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-address') || '{}');
            openAddAddressModal(data);
        } catch (_) {
            toast('خطا در خواندن آدرس', 'danger');
        }
    };

    window.deleteAddress = async function (id) {
        if (!confirm('آیا از حذف این آدرس مطمئن هستید؟')) return;
        try {
            const { ok, data } = await api('/api/profile/addresses/delete', {
                method: 'POST',
                body: { id },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.setDefaultAddress = async function (id) {
        try {
            const { ok, data } = await api('/api/profile/addresses/set-default', {
                method: 'POST',
                body: { id },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
        }
    };

    /* ---------- Wishlist ---------- */
    window.removeFromWishlist = async function (productId, btn) {
        try {
            if (btn) btn.disabled = true;
            const { ok, data } = await api('/api/profile/wishlist/remove', {
                method: 'POST',
                body: { product_id: productId },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                const card = document.getElementById('wishlist-item-' + productId);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        const grid = document.getElementById('wishlist-grid');
                        if (grid && !grid.querySelector('[id^="wishlist-item-"]')) {
                            location.reload();
                        }
                    }, 300);
                }
                const badge = document.getElementById('badge-wishlist-count');
                if (badge && typeof data.count === 'number') {
                    badge.textContent = faNum(data.count);
                    if (data.count === 0) badge.classList.add('hidden');
                }
            } else {
                toast(data.message || 'خطا', 'danger');
                if (btn) btn.disabled = false;
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
            if (btn) btn.disabled = false;
        }
    };

    window.addWishlistToCart = function (productId) {
        if (typeof addToCart === 'function') {
            addToCart(productId);
        } else {
            toast('سبد خرید در دسترس نیست', 'danger');
        }
    };

    /* ---------- Wallet ---------- */
    window.setChargeAmount = function (amount) {
        const input = document.getElementById('custom-wallet-amount');
        if (input) {
            input.value = amount;
            input.focus();
        }
        document.querySelectorAll('.wallet-amount-btn').forEach((b) => {
            b.classList.remove('border-brand-red', 'text-white');
            b.classList.add('border-white/10', 'text-gray-400');
        });
        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('border-brand-red', 'text-white');
            window.event.currentTarget.classList.remove('border-white/10', 'text-gray-400');
        }
    };

    window.submitWalletCharge = async function () {
        const input = document.getElementById('custom-wallet-amount');
        const amount = parseInt(input?.value || '0', 10);
        if (!amount || amount < 50000) {
            toast('حداقل مبلغ شارژ ۵۰٬۰۰۰ تومان است.', 'danger');
            return;
        }

        const btn = document.getElementById('btn-wallet-charge');
        setBtnLoading(btn, true);
        try {
            const { ok, data } = await api('/api/profile/wallet/charge', {
                method: 'POST',
                body: { amount },
            });
            setBtnLoading(btn, false, 'پرداخت / ثبت درخواست');

            if (ok && data.success) {
                toast(data.note || data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط با سرور', 'danger');
        }
    };

    /* ---------- Tickets ---------- */
    window.handleCreateTicket = async function (e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const subject = document.getElementById('ticket-subject').value.trim();
        const message = document.getElementById('ticket-message').value.trim();
        const priority = document.getElementById('ticket-priority')?.value || 'normal';

        setBtnLoading(btn, true);
        try {
            const { ok, data } = await api('/api/profile/tickets/create', {
                method: 'POST',
                body: { subject, message, priority },
            });
            setBtnLoading(btn, false, 'ثبت تیکت');

            if (ok && data.success) {
                toast(data.message, 'success');
                closeNewTicketModal();
                setTimeout(() => location.reload(), 800);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.handleTicketReply = async function (e, ticketId) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const text = document.getElementById('ticket-reply-text').value.trim();
        setBtnLoading(btn, true);
        try {
            const { ok, data } = await api('/api/profile/tickets/reply', {
                method: 'POST',
                body: { ticket_id: ticketId, message: text },
            });
            setBtnLoading(btn, false, 'ارسال پاسخ');
            if (ok && data.success) {
                toast(data.message, 'success');
                openTicketDetailModal(ticketId);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.handleCloseTicket = async function (ticketId) {
        if (!confirm('تیکت بسته شود؟')) return;
        try {
            const { ok, data } = await api('/api/profile/tickets/close', {
                method: 'POST',
                body: { ticket_id: ticketId },
            });
            if (ok && data.success) {
                toast(data.message, 'success');
                closeTicketDetailModal();
                setTimeout(() => location.reload(), 600);
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            toast('خطا در ارتباط', 'danger');
        }
    };

    /* ---------- Settings ---------- */
    window.handleSaveSettings = async function (e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const payload = {
            full_name: document.getElementById('settings-fullname').value.trim(),
            email: document.getElementById('settings-email').value.trim(),
            national_id: document.getElementById('settings-national-id').value.trim(),
            city: document.getElementById('settings-city').value.trim(),
        };

        setBtnLoading(btn, true);
        try {
            const { ok, data } = await api('/api/profile/update', {
                method: 'POST',
                body: payload,
            });
            setBtnLoading(btn, false, 'ذخیره اطلاعات');

            if (ok && data.success) {
                toast(data.message, 'success');
                // به‌روزرسانی نام در سایدبار
                const nameEl = document.getElementById('sidebar-user-name');
                if (nameEl && data.user?.full_name) nameEl.textContent = data.user.full_name;
                const initEl = document.getElementById('sidebar-user-initials');
                if (initEl && data.user?.initials) initEl.textContent = data.user.initials;
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.handleChangePassword = async function (e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const payload = {
            current_password: document.getElementById('settings-current-pass').value,
            new_password: document.getElementById('settings-new-pass').value,
            confirm_password: document.getElementById('settings-confirm-pass').value,
        };

        setBtnLoading(btn, true);
        try {
            const { ok, data } = await api('/api/profile/change-password', {
                method: 'POST',
                body: payload,
            });
            setBtnLoading(btn, false, 'تغییر رمز عبور');

            if (ok && data.success) {
                toast(data.message, 'success');
                e.target.reset();
            } else {
                toast(data.message || 'خطا', 'danger');
            }
        } catch (_) {
            setBtnLoading(btn, false);
            toast('خطا در ارتباط', 'danger');
        }
    };

    window.confirmLogout = async function () {
        try {
            await api('/api/logout', { method: 'POST', body: {} });
        } catch (_) { }
        window.location.href = '/login';
    };

    /* ---------- Init ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        // تب از URL
        try {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab && document.getElementById('tab-content-' + tab)) {
                switchProfileTab(tab);
            }
        } catch (_) { }

        refreshIcons();
    });
})();