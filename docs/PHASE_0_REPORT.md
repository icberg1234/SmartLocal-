# PHASE 0 — گزارش: پایه و اسکفولد

> طبق قالبِ Implementation_Playbook §۱.

## ۱) چه ساخته شد
**زیرساخت پروژه**
- `composer.json` — Laravel 11 + sanctum, horizon, telescope, spatie/permission, predis؛ dev: pest, pint, larastan, faker, collision, mockery. اسکریپت‌ها: `test/lint/stan/check`.
- Docker: `docker-compose.yml` (php-fpm, nginx, mysql8, redis7) + `docker/php/Dockerfile` (php8.3 + pdo_mysql/redis/intl/gd/bcmath) + `docker/nginx/default.conf`.
- CI: `.github/workflows/ci.yml` — pint + phpstan + pest با سرویس‌های mysql/redis.
- کیفیت: `pint.json` (preset laravel + declare_strict_types) · `phpstan.neon` (larastan، level max).
- `.env.example` (mysql/redis، درایورهای fake برای SMS/Payment، `POINT_UNIT`، `REDEEM_TOKEN_TTL`).

**معماری (Modular Monolith)**
- `app/Providers/ModuleServiceProvider.php` — کشف خودکارِ `app/Modules/*` و بارگذاری `routes.php` (prefix `/api/v1`، middleware `api`) و `Database/Migrations` هر ماژول. ثبتِ singletonِ `CurrentMall`.
- `bootstrap/providers.php` و `bootstrap/app.php` (L11): ثبت Provider، append میدلورِ `ResolveTenant` به گروه `api`، aliasهای role/permission، health `/up`.

**چندمستأجری (Tenancy)**
- `Modules/Core/Support/CurrentMall.php` — نگه‌دارنده‌ی tenantِ جاری (singleton).
- `Modules/Core/Models/Scopes/MallScope.php` — Global Scope: محدودسازیِ خودکار به `mall_id` (در نبودِ tenant، no-op).
- `Modules/Core/Models/Concerns/BelongsToMall.php` — trait: افزودن scope + پرکردنِ خودکارِ `mall_id` هنگام create.
- `app/Http/Middleware/ResolveTenant.php` — resolve از هدر `X-Mall-Id` یا subdomain.

**Event Store (CDP-ready)**
- `Modules/Core/Models/Event.php` — append-only؛ update/delete با استثنا مسدود؛ فقط `created_at`؛ payload typed (json).
- `Modules/Core/Support/EventRecorder.php` — تنها نقطه‌ی ثبت رویداد؛ actor/subject polymorphic، schema_version، mall_id خودکار.

**مدل‌ها/داده**
- `Mall` (ریشه‌ی tenant، بدون scope) · `Floor` (tenant-owned).
- migrationها: `malls`, `floors` (unique mall+level), `events` (ایندکس mall/type/created_at، nullable morphs).
- `MallFactory` · `DatabaseSeeder` (یک پاساژ نمونه «الماس» + ۳ طبقه).
- `Modules/Core/routes.php` — `GET /api/v1/health`.

**تست**
- `tests/Feature/Phase0FoundationTest.php` (۵ تست): migrate+seed · ثبت رویداد · immutability رویداد · health endpoint · scope مستأجر.

## ۲) تصمیم‌ها و انحراف از اسپک
- migrationهای هسته داخلِ خودِ ماژول (`Modules/Core/Database/Migrations`) قرار گرفتند تا الگوی ماژولار از همین فاز نمایش داده شود (به‌جای `database/migrations`).
- Factory در مسیر استانداردِ `database/factories` ماند (سادگیِ autoload) و مدل با `newFactory()` به آن وصل شد.
- Immutability رویداد با hookهای `updating/deleting` (نه DB trigger) — کافی برای MVP، قابل ارتقا به trigger در سخت‌سازی.

## ۳) اجرا
رجوع به `SETUP.md` (به‌خاطر نبودِ PHP/Composer هنگام نگارش، بوت روی ماشینِ نویسنده انجام نشده).

## ۴) تست و نگاشتِ گِیت
| موردِ گِیت | پوشش |
|---|---|
| migrate:fresh --seed | تست «migrations and seeds» |
| ثبت رویداد | تست «records an event» |
| immutability | تست «enforces immutability» |
| سرویس بالا می‌آید | تست health endpoint |
| scope مستأجر | تست «scopes tenant-owned models» |
> اجرای واقعیِ این تست‌ها به‌عهده‌ی دولوپر است (`composer test`). تا سبز نشدن، فاز ۱ شروع نشود.

## ۵) امنیت/تقلب
- فاز ۰ سطحِ پایه: Event Store برای ردگیریِ بعدی آماده شد؛ قوانینِ تقلب در فاز ۳.

## ۶) بدهیِ فنی/ریسک باقی‌مانده
- ⚠️ **بوت/تست روی این ماشین انجام نشد** (نبودِ PHP/Composer/Docker) — ریسکِ اصلیِ این فاز؛ دولوپر باید گِیت را واقعاً سبز کند.
- نسخه‌های دقیقِ پکیج‌ها هنگام `composer install` نهایی می‌شوند؛ در صورت نیاز سازگاری larastan/pest تنظیم شود.

## ۷) چک‌لیستِ آمادگیِ فاز بعد (فاز ۱ — Auth/OTP/RBAC)
- [ ] گِیتِ فاز ۰ سبز (`composer check` + health).
- [ ] `php artisan install:api` اجرا شد (Sanctum).
- [ ] جدول‌های spatie/permission مهاجرت شد.
- [ ] CI سبز روی push.
