# SmartLocal — Tech Debt Register

بدهی‌های شناخته‌شده که عمداً موکول شده‌اند (تا silently drop نشوند).

## از فاز ۱ (Auth)
| # | مورد | شدت | توضیح |
|---|---|---|---|
| D1 | **قفلِ OTP دور‌زدنی** | 🔴 امنیتی | `request-otp` رکورد کد را delete و attempts را صفر می‌کند → قفلِ ۳-خطا با درخواست کد جدید ریست می‌شود. باید attempts/lock **per-phone مستقل از رکورد کد** ذخیره شود. |
| D2 | **throttle بر اساس IP نه phone** | 🟠 | روی IPهای مشترک موبایل برخورد می‌کند. باید با phone کلید بخورد. |
| D3 | حالتِ locked → 422 (نه 429/423 معنادار) | 🟡 | بهبود سمانتیک. |

## از زیرساخت (نبودِ PHP محلی)
| # | مورد | توضیح |
|---|---|---|
| D4 | Pint روی auto-fix نه `--test` | گِیتِ استایل enforce نمی‌شود. بعد از داشتن محیط PHP، به `--test` برگردد. |
| D5 | PHPStan level 5 نه max | برنامه ratchet: 5 → 8 → max. |
| D6 | ریپو overlay + CIِ self-assembling | یک `composer create-project` یک‌باره روی PHP env آن را vanilla می‌کند. |

## موکول‌شده طبق فازبندی
- Permissions دانه‌ریز (فعلاً فقط Role)
- مدیریت/withdrawal رضایت (Consent)
- تستِ KavenegarSmsSender
- enforcement ثبت‌نام تدریجی در سطح API
