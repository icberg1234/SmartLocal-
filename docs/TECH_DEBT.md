# SmartLocal — Tech Debt Register

بدهی‌های شناخته‌شده که عمداً موکول شده‌اند (تا silently drop نشوند).

## از فاز ۱ (Auth)
| # | مورد | شدت | وضعیت |
|---|---|---|---|
| D1 | **قفلِ OTP دور‌زدنی** | 🔴 امنیتی | ✅ **حل شد** — موقع قفل بودن، `request-otp` با ۴۲۹ بلاک می‌شود (تست D1). |
| D2 | **throttle بر اساس IP نه phone** | 🟠 | ✅ **حل شد** — RateLimiter نام‌دارِ `otp` با کلیدِ phone (تست D2). |
| D3 | حالتِ locked → 422 (نه 429 معنادار) | 🟡 | باز — بهبود سمانتیک (verify هنوز 422). |

## از زیرساخت (نبودِ PHP محلی)
| # | مورد | توضیح |
|---|---|---|
| D4 | Pint روی auto-fix نه `--test` | گِیتِ استایل enforce نمی‌شود. بعد از داشتن محیط PHP، به `--test` برگردد. |
| D5 | PHPStan level 6 نه max | برنامه ratchet: 6 → 8 → max. |
| D6 | ریپو overlay + CIِ self-assembling | یک `composer create-project` یک‌باره روی PHP env آن را vanilla می‌کند. |

## اطلاعات پایه (Master / Base Data)
| مورد | وضعیت |
|---|---|
| Malls · Floors · Categories · Roles · Parking | ✅ seed می‌شوند (RolesSeeder/CategoriesSeeder/DatabaseSeeder) |
| **`plans` (پکیج‌ها)** | ✅ افزوده شد — مطابقِ `Technical_Architecture` (جدولِ plans + `subscriptions.plan_id`)؛ سیدرِ silver/gold؛ endpoint عمومیِ `GET /api/v1/plans` |
| تنظیماتِ هر پاساژ (`malls.settings`) | ✅ ستون + accessorِ `Mall::setting()` — مبنای کانفیگِ per-mall (درگاه/فیچرفلگ/ارز/ساعات) |
| **سرویسِ پیامک + درگاهِ پرداخت، per-mall** | ✅ افزوده شد — `CurrentMall::setting()` نوعِ درایور و کلیدِ هر پاساژ را از `malls.settings` می‌خواند؛ fallback به configِ سراسری (تستِ `ProviderResolutionTest`) |
| CRUDِ ادمین برای اطلاعات پایه | موکول — به پنلِ ادمینِ Inertia گره خورده (الان فقط seeder/مهاجرت) |
| رمزنگاریِ سکرت‌ها در `settings` | 🟠 باز — کلیدِ APIِ هر پاساژ فعلاً plaintext در JSON؛ باید `encrypted:array` شود (نیازمندِ ستونِ `text` به‌جای `json`) |
| قالبِ پیام (OTP/جشنواره) + برند | ✅ افزوده شد — `config('smartlocal.templates.*')` با placeholderِ `{brand}/{code}/{title}`؛ برندِ per-mall از `settings` (تستِ `BaseDataConfigTest`) |
| پارامترهای OTP (TTL/تلاش/قفل) | ✅ از ثابتِ هاردکد به `config('smartlocal.otp.*')` منتقل شد |
| انواعِ Venue (mall/bازار/...) | ✅ `config('smartlocal.venue_types')` به‌عنوان داده‌ی مرجع |

## موکول‌شده طبق فازبندی
- Permissions دانه‌ریز (فعلاً فقط Role)
- مدیریت/withdrawal رضایت (Consent)
- تستِ KavenegarSmsSender
- enforcement ثبت‌نام تدریجی در سطح API
