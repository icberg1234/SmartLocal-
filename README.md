# SmartLocal — Platform (MVP)

سامانه‌ی بازاریابیِ محلیِ پاساژها: مشتری با QR وارد می‌شود، عضو می‌شود، سرِ صندوق تخفیفِ عضو + امتیازِ کل‌پاساژ می‌گیرد، فروشگاه‌ها را فالو می‌کند و نوتیفِ جشنواره دریافت می‌کند. Laravel 11 (Modular Monolith، API-first، event-sourced core) + PWA مشتری (Vue 3).

## وضعیت
**MVP بک‌اند + PWA مشتری + پنلِ مدیریتیِ Vue — هر ۹ فاز (۰–۸) + اطلاعات پایه + رمزنگاریِ سکرت‌ها، ساخته و CI-سبز** (~۵۹ تستِ بک‌اند + تستِ فرانت).

| فاز | محتوا |
|---|---|
| ۰ | پایه · Tenancy (Global Scope) · Event Store |
| ۱ | احراز هویت OTP · Sanctum · RBAC (spatie) |
| ۲ | فروشگاه/محصول/دسته · whitelist · سهمیه پکیج |
| ۳ | ⭐ ریل Redemption (QR داینامیک، تخفیف، امتیاز، ضدتقلب) |
| ۴ | امتیاز/سطح/انقضا/خرج · CRM (پروفایل/سگمنت) |
| ۵ | جشنواره · فالو · نوتیفِ consent-gated |
| ۶ | نقشه · پارکینگ (ظرفیت/لاتاری) · پرداخت (Fake/Zarinpal) |
| ۷ | PWA مشتری (Vue3+Vite): ورود/خانه/کد تخفیف |
| ۸ | آنالیتیکس/KPI (MRC، GMV، Active Stores، Repeat Rate) |

## معماری
- **Modular Monolith:** `app/Modules/{Core,Auth,BusinessUnits,Redemption,Festival,Venue,Analytics}` — هر ماژول routes/migrations خودکار (ModuleServiceProvider).
- **چندمستأجری:** `mall_id` + Global Scope (`BelongsToMall`) + middleware `ResolveTenant` (هدر `X-Mall-Id`).
- **Event Store:** هر اکشن مهم → `events` (immutable، CDP-ready).
- **اطلاعات پایه:** `plans` (پکیج‌ها، master؛ `subscriptions.plan_id`) + `categories`/`roles` سیدشده + accessorِ `Mall::setting()` برای کانفیگِ هر پاساژ؛ کاتالوگِ عمومیِ `GET /api/v1/plans`.
- **هسته‌ی پولی:** «ریلِ Redemption» — QR داینامیکِ رمزنگاری‌شده → تخفیف + امتیاز + پروفایل + رویداد، در یک تراکنش اتمیک.

## راه‌اندازی
⚠️ این ریپو **overlayِ کدِ سفارشی** است، نه اپ کاملِ vanilla (بدهی D6). برای بوت، `SETUP.md` را ببینید (یک `composer create-project` لازم است). CI خودش اپ کامل را اسمبل و تست می‌کند (`.github/workflows/ci.yml`).

```bash
# گِیت (پس از اسمبل/بوت طبق SETUP):
composer test     # Pest
composer stan     # PHPStan (larastan)
# فرانت:
cd pwa && npm install && npm run build && npm run test
```

## دموی سریع (بدون پیامک)
پس از `php artisan migrate --seed`، `DemoSeeder` یک پاساژِ کاملِ نمونه می‌سازد: یک فروشگاهِ پر (**بوتیک رویا** + محصولات)، چند فروشگاهِ قابل‌مرور، امتیازِ مشتری، و سه حسابِ آزمایشی. در محیطِ **غیرپروداکشن**، با `POST /api/v1/dev/login {phone}` یا دکمه‌های «ورودِ سریع» در صفحه‌ی ورودِ PWA (فقط در حالتِ dev) بدونِ SMS وارد شو:

| نقش | موبایل |
|---|---|
| مشتری | `09120000001` |
| مدیر پاساژ | `09120000002` |
| فروشنده | `09120000003` |

## مستندات
- `SETUP.md` — راه‌اندازی · `docs/PHASE_0_REPORT.md` — گزارش فاز ۰
- `docs/TECH_DEBT.md` — بدهیِ فنیِ شناخته‌شده (D3/D4/D5/D6 باز)
- اسناد محصول/استراتژی (در ریشه‌ی پروژه‌ی والد): پروپوزال، معماری، فلوچارت‌ها، نقشه اجرا، سناریو، PMF Assumptions، KPI Spec، Developer Handoff، MVC Blueprint، PreBuild Specs.

## بدهیِ فنیِ باز (قبل از پروداکشن)
- **D4/D5:** بازگرداندنِ `pint --test` و بالا‌بردنِ PHPStan به max (نیازمند محیط PHP محلی).
- **D6:** snapshot ریپو به vanilla Laravel.
- موکول: پنل‌های مدیر/فروشگاه (Inertia)، E2E، اتصالِ واقعیِ FCM/Kavenegar/Zarinpal، fraud-ML، CDP کامل.

> **یادآوری:** کدِ سبز ≠ PMF. اعتبارسنجیِ فرض‌های A0–A10 (پذیرشِ مشتری/فروشگاه/مدیر) در پاساژِ واقعی، قدمِ بعدیِ کسب‌وکار است (رجوع به سند PMF).
