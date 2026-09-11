# راهنمای ساخت پلاگین

این سند مرحله‌به‌مرحله نشان می‌دهد چطور یک پلاگین جدید برای فریم‌ورک بسازید.

## ۱. ساختار پوشه

هر پلاگین یک پوشه‌ی مستقل داخل `plugins/` دارد:

```
plugins/
└── MyPlugin/
    ├── plugin.yml
    └── MyPlugin.php
```

می‌توانید فایل‌های دیگر (کلاس‌های کمکی، Viewهای اختصاصی، Migrationها و ...) را هم داخل همین پوشه اضافه کنید؛ چون هنگام لود پلاگین، Namespace آن به همین پوشه نگاشت می‌شود.

## ۲. فایل `plugin.yml`

```yaml
name: MyPlugin
version: 1.0.0
author: YourName
description: توضیح کوتاه درباره‌ی این پلاگین.
main: MyPlugin.php
class: MyPlugin\MyPlugin
requires:
  php: ">=8.0"
dependencies: []
hooks:
  - before_route
  - after_render
syntax:
  - "@myTag"
```

| فیلد | توضیح |
|---|---|
| `name` | نام یکتای پلاگین (باید با نام پوشه یکی باشد) |
| `main` | فایل اصلی PHP که کلاس پلاگین در آن است |
| `class` | نام کامل کلاس (با Namespace) که باید از `Core\Plugin` ارث‌بری کند |
| `requires.php` | حداقل نسخه‌ی PHP لازم |
| `dependencies` | آرایه‌ای از نام سایر پلاگین‌هایی که باید قبل از این پلاگین لود شوند |
| `hooks` | فقط جنبه‌ی مستندسازی دارد؛ ثبت واقعی هوک‌ها داخل کد پلاگین انجام می‌شود |
| `syntax` | فقط جنبه‌ی مستندسازی دارد؛ ثبت واقعی سینتکس داخل کد پلاگین انجام می‌شود |

## ۳. کلاس اصلی پلاگین

کلاس شما باید از `Core\Plugin` ارث‌بری کند و متد `register()` را پیاده‌سازی کند:

```php
namespace MyPlugin;

use Core\Plugin;
use Core\Router;

class MyPlugin extends Plugin
{
    public function register(): void
    {
        $this->hook('register_routes', [$this, 'registerRoutes']);
        $this->hook('before_route', [$this, 'onBeforeRoute']);
    }

    public function registerRoutes(Router $router): void
    {
        $router->get('/my-plugin', function ($request, $params) {
            return 'سلام از MyPlugin!';
        });
    }

    public function onBeforeRoute($request, $app): void
    {
    }

    public function boot(): void
    {
    }
}
```

- `register()` بلافاصله بعد از لود شدن کلاس فراخوانی می‌شود؛ اینجا بهترین جا برای ثبت Hookها و سینتکس‌هاست.
- `boot()` اختیاری است و بعد از اینکه **همه‌ی** پلاگین‌ها `register` شدند اجرا می‌شود؛ برای کارهایی که به وجود پلاگین‌های دیگر وابسته‌اند مناسب است.
- داخل کلاس به `$this->app()` (نمونه‌ی `Core\App`)، `$this->meta()` (محتوای `plugin.yml`) و `$this->path()` (مسیر پوشه‌ی پلاگین) دسترسی دارید.

## ۴. اضافه کردن Route

از طریق هوک `register_routes`:

```php
$this->hook('register_routes', function (\Core\Router $router) {
    $router->get('/products', 'ProductController@index');
    $router->post('/products', 'ProductController@store');
});
```

اگر کنترلر پلاگین را داخل خود پوشه‌ی پلاگین تعریف کنید، نام کامل کلاس (با Namespace) را در Route بنویسید:

```php
$router->get('/products', 'MyPlugin\\Controllers\\ProductController@index');
```

## ۵. قلاب‌ شدن (Hook) به رویدادها

هوک‌های موجود در فریم‌ورک:

| هوک | زمان اجرا |
|---|---|
| `before_load` | قبل از لود پلاگین‌ها |
| `after_load` | بعد از لود همه‌ی پلاگین‌ها |
| `plugins_loaded` | بعد از register شدن همه‌ی پلاگین‌ها، قبل از boot |
| `register_routes` | زمانی که باید Routeها ثبت شوند |
| `before_route` | قبل از تطبیق Route با درخواست |
| `after_route` | بعد از تطبیق موفق Route |
| `route_not_found` | وقتی هیچ Routeای پیدا نشود |
| `before_render` | قبل از پردازش خروجی نهایی |
| `after_render` | بعد از پردازش خروجی نهایی |

```php
$this->hook('after_render', function ($request, $app) {
});
```

برای تغییر خروجی نهایی از Filter استفاده کنید:

```php
$this->filter('render_output', function (string $output) {
    return $output . "\n<!-- MyPlugin -->";
});
```

## ۶. اضافه کردن سینتکس جدید به Viewها

با `Core\TemplateEngine::extend($pattern, $handler)` می‌توانید هر سینتکس دلخواهی (حتی شبیه زبان‌های دیگر) تعریف کنید. `$handler` یک تابع است که نتیجه‌ی `preg_match` را می‌گیرد و باید کد PHP معادل را برگرداند:

```php
use Core\TemplateEngine;

TemplateEngine::extend('/@upper\((.+?)\)/', function (array $matches) {
    return '<?= strtoupper(' . $matches[1] . ') ?>';
});
```

حالا در View می‌توانید بنویسید:

```
@upper($name)
```

می‌توانید همین روش را برای شبیه‌سازی سینتکس زبان‌های دیگر هم به کار ببرید؛ کافیست الگوی Regex مناسب بنویسید و در `handler` خروجی معادل PHP آن را تولید کنید.

## ۷. Middleware سفارشی

```php
public function register(): void
{
    $this->app()->addMiddleware(function ($request, $response) {
        if ($request->header('X-Api-Key') !== 'secret') {
            $response->status(403)->body('Forbidden')->send();
            return false;
        }
        return true;
    });
}
```

## ۸. وابستگی بین پلاگین‌ها

اگر پلاگین شما به پلاگین دیگری نیاز دارد، نامش را در `dependencies` بنویسید تا `PluginManager` ترتیب لود را رعایت کند:

```yaml
dependencies:
  - CorePlugin
```

## ۹. فعال کردن پلاگین

در نهایت پلاگین را در `config/config.php` فعال کنید:

```php
'plugins' => [
    'MyPlugin' => true,
],
```

## ۱۰. ایزوله‌سازی و خطاها

اگر در `register()` یا `boot()` پلاگین شما Exception پرتاب شود، فریم‌ورک آن را می‌گیرد، در لاگ داخلی ثبت می‌کند و به لود سایر پلاگین‌ها ادامه می‌دهد؛ یعنی لازم نیست نگران خراب کردن کل سایت باشید، اما توصیه می‌شود همیشه ورودی‌ها و شرایط را قبل از اجرا بررسی کنید.
