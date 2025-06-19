# ✅ Laravel 11 Upgrade Checklist

### 🛠 Prerequisites
- [ ] Update to **PHP ≥ 8.2.0**
- [ ] Ensure **cURL ≥ 7.34.0**

### 📦 Composer Dependencies
- [ ] `laravel/framework` → `^11.0`
- [ ] `nunomaduro/collision` → `^8.1`
- [ ] `laravel/breeze` → `^2.0` *(if used)*
- [ ] `laravel/cashier` → `^15.0` *(if used)*
- [ ] `laravel/dusk` → `^8.0` *(if used)*
- [ ] `laravel/jetstream` → `^5.0` *(if used)*
- [ ] `laravel/octane` → `^2.3` *(if used)*
- [ ] `laravel/passport` → `^12.0` *(if used)*
- [ ] `laravel/sanctum` → `^4.0` *(if used)*
- [ ] `laravel/scout` → `^10.0` *(if used)*
- [ ] `laravel/spark-stripe` → `^5.0` *(if used)*
- [ ] `laravel/telescope` → `^5.0` *(if used)*
- [ ] `livewire/livewire` → `^3.4` *(if used)*
- [ ] `inertiajs/inertia-laravel` → `^1.0` *(if used)*

### 🔧 Publish Package Migrations *(if applicable)*
Run `php artisan vendor:publish --tag=…` for:
- [ ] `cashier-migrations`
- [ ] `passport-migrations`
- [ ] `sanctum-migrations`
- [ ] `spark-migrations`
- [ ] `telescope-migrations`

Also verify each package’s upgrade guide (Cashier, Passport, Sanctum, Spark Stripe, Telescope) :contentReference[oaicite:1]{index=1}.

### 🧹 Optional Cleanup
- [ ] Remove `doctrine/dbal` if present in `composer.json` :contentReference[oaicite:2]{index=2}.
- [ ] Update global Laravel installer: composer global require laravel/installer:^5.6


---

## 🏗 Application Structure
- [ ] *Optional*: Adopt new streamlined structure (fewer files), but not required—Laravel 11 supports old layout :contentReference[oaicite:4]{index=4}.

---

## 🔐 Authentication & Security

### Password Rehashing
- [ ] If using default `password` field: no action needed.
- [ ] If using custom password field, set in `User` model:
```php
protected $authPasswordName = 'your_field';
```
To disable automatic rehash-on-login, in config/hashing.php:
`'rehash_on_login' => false,`

⚠️ Additional Breaking Changes

High Impact

    [ ] Floating‑Point types

    [ ] Modifying columns (DB migrations)

    [ ] Ensure SQLite version meets minimum

Medium Impact

    [ ] Carbon 3 upgrade

    [ ] Per‑second rate limiting

    [ ] Check Spatie Once package

Low Impact

    [ ] Removal of Doctrine DBAL

    [ ] Eloquent model casts method

    Spatial types

    [ ] Changes to Enumerable, UserProvider, and Authenticatable contracts
    laravel.com
    en.wikipedia.org+7laravel.com+7laravel.com+7

🧪 Testing & Validation

    [ ] Run full test suite

    [ ] Pay attention to authentication, hashing, queue and rate limiting behaviors

✅ Final Checks

    [ ] Review laravel/laravel repo diff for recommended config or structure updates
    laravel.com+8laravel.com+8medium.com+8
    en.wikipedia.org+2laravel.com+2laravel.com+2

    [ ] Deploy to staging and verify:

        App boots without errors

        Migrations applied cleanly

        Auth/login, queue, API, and HTTP workflows are intact

    [ ] Run security and performance tests (if applicable)

# Follow up
- [ ] Go through the full upgrade guide: https://laravel.com/docs/11.x/upgrade
