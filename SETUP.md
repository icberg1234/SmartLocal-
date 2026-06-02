# SmartLocal — راه‌اندازی (Phase 0)

> ⚠️ این کد روی ماشینی نوشته شده که **PHP/Composer/Docker نداشت**، پس **بوت/تست نشده** —
> این فایل‌ها یک **overlay روی یک پروژه‌ی تازه‌ی Laravel 11** هستند. مراحل زیر را اجرا کن تا بوت شود و **گِیتِ فاز ۰** سبز شود.

## پیش‌نیاز
PHP 8.3 · Composer 2 · Docker (اختیاری ولی توصیه‌شده) · MySQL 8 · Redis

## مراحل
```bash
# ۱) اسکلتِ تازه‌ی لاراول ۱۱ را در یک پوشه‌ی موقت بساز
composer create-project laravel/laravel:^11 _skeleton

# ۲) فایل‌های اسکلت را که ما نساخته‌ایم، کنار این پروژه بگذار
#    (همه‌ی فایل‌های _skeleton را در این پوشه کپی کن، ولی فایل‌های Overlay زیر را بازنویسی نکن)

# ۳) نصب وابستگی‌ها (composer.json ما همه را دارد: sanctum, horizon, telescope, spatie/permission)
composer install

# ۴) Sanctum (مهاجرت توکن‌ها) و انتشار پکیج‌ها
php artisan install:api --no-interaction        # توکن‌های Sanctum (routing از قبل wire شده)
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan horizon:install
php artisan telescope:install

# ۵) محیط
cp .env.example .env
php artisan key:generate

# ۶) سرویس‌ها + دیتابیس
docker compose up -d                              # mysql + redis + nginx + php
php artisan migrate --seed

# ۷) گِیتِ فاز ۰  ✅
composer lint   # pint
composer stan   # phpstan (larastan) سطح max
composer test   # pest
curl http://localhost:8080/api/v1/health          # {"status":"ok","malls":1}
```

## فایل‌های Overlay (ساخته‌شده در این فاز — اینها را بازنویسی نکن)
```
composer.json · .env.example · phpstan.neon · pint.json
docker-compose.yml · docker/php/Dockerfile · docker/nginx/default.conf
.github/workflows/ci.yml
bootstrap/app.php · bootstrap/providers.php · routes/api.php
app/Providers/ModuleServiceProvider.php
app/Http/Middleware/ResolveTenant.php
app/Modules/Core/...  (Models, Support, Scopes, Concerns, Database/Migrations, routes.php)
database/factories/MallFactory.php · database/seeders/DatabaseSeeder.php
tests/Feature/Phase0FoundationTest.php
docs/PHASE_0_REPORT.md
```

## معیارِ سبز شدن گِیت
- `composer test` → همه سبز (۵ تست در Phase0FoundationTest).
- `pint --test` و `phpstan` → صفر خطا.
- `/api/v1/health` → `malls: 1`.
- اگر همه سبز شد → آماده‌ی **فاز ۱** (پرامت در Implementation_Playbook).
