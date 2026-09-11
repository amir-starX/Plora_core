# معماری فریم‌ورک

این سند نحوه‌ی کار درونی فریم‌ورک و ارتباط بین کلاس‌های هسته را توضیح می‌دهد.

## فلسفه‌ی طراحی

فریم‌ورک بر پایه‌ی دو اصل ساخته شده:

1. **Front Controller**: تمام درخواست‌ها از یک نقطه (`index.php`) عبور می‌کنند.
2. **Plugin-Based**: هسته فقط زیرساخت است؛ هر قابلیت واقعی (Route، سینتکس جدید، Middleware، اتصال به سرویس بیرونی و...) می‌تواند به‌صورت پلاگین اضافه شود، بدون تغییر در کد هسته.

## چرخه‌ی عمر یک درخواست (Request Lifecycle)

```
index.php
   │
   ├─ Autoloader ثبت می‌شود
   │
   ▼
Core\App::__construct()
   ├─ ErrorHandler::register()
   ├─ Config::load()
   ├─ Router / Request / PluginManager ساخته می‌شوند
   ├─ Hook: before_load
   ├─ PluginManager::loadAll()
   │     ├─ کشف پلاگین‌های فعال از config
   │     ├─ خواندن plugin.yml هر پلاگین با YamlParser
   │     ├─ مرتب‌سازی بر اساس dependencies
   │     ├─ require و instantiate کلاس هر پلاگین
   │     └─ فراخوانی register() هر پلاگین (در try/catch مجزا)
   ├─ Hook: after_load
   │
   ▼
Core\App::run()
   ├─ Hook: register_routes   (پلاگین‌ها و بعد app/Routes.php مسیر ثبت می‌کنند)
   ├─ Hook: before_route
   ├─ Router::match($request)
   ├─ اجرای Middlewareها
   ├─ Hook: after_route
   ├─ Dispatch به Controller یا Closure
   ├─ Hook: before_render
   ├─ Filter: render_output
   ├─ Hook: after_render
   └─ Response::send()
```

## کلاس‌های هسته

### `Core\App`
راه‌انداز اصلی. Config، Router، PluginManager و Request را می‌سازد، پلاگین‌ها را لود می‌کند و چرخه‌ی درخواست را مدیریت می‌کند.

### `Core\Router`
مسیرها را نگه می‌دارد. الگوهای `{param}` را به Regex تبدیل می‌کند و بر اساس متد HTTP و مسیر، Handler مناسب را پیدا می‌کند.

### `Core\Request` / `Core\Response`
لایه‌ی انتزاعی روی `$_GET`, `$_POST`, هدرها و خروجی HTTP. ورودی‌ها به‌صورت خودکار trim/sanitize می‌شوند.

### `Core\Database`
لایه‌ی نازک روی PDO با Prepared Statements. فقط زمانی استفاده می‌شود که در `config/config.php` فعال شده باشد.

### `Core\Config`
فایل‌های `config/config.php`, `config/plugins.php` و `database/config.php` را می‌خواند و با دسترسی نقطه‌ای (`app.debug`) در دسترس می‌گذارد.

### `Core\Hook`
پیاده‌سازی ساده‌ی الگوی Observer/Event:

- `Hook::addAction($name, $callback, $priority)` / `Hook::doAction($name, ...$args)`
- `Hook::addFilter($name, $callback, $priority)` / `Hook::applyFilter($name, $value, ...$args)`

هوک‌های اصلی فریم‌ورک: `before_load`, `after_load`, `register_routes`, `before_route`, `after_route`, `before_render`, `after_render`, `route_not_found`, `plugins_loaded`. فیلتر اصلی: `render_output`, `template_source`.

### `Core\YamlParser`
پارسر سبک YAML که بدون هیچ کتابخانه‌ی خارجی، فایل `plugin.yml` را به آرایه‌ی PHP تبدیل می‌کند. از Map، List، مقادیر تودرتو، رشته‌های quote شده و انواع پایه (bool, int, float, null) پشتیبانی می‌کند.

### `Core\PluginManager`
مسئول کشف، اعتبارسنجی، ترتیب‌دهی (بر اساس `dependencies`) و لود Isolated پلاگین‌ها. اگر یک پلاگین خطا بدهد (چه در `register()` و چه در `boot()`)، فقط همان پلاگین غیرفعال می‌شود و بقیه‌ی فریم‌ورک بدون مشکل ادامه پیدا می‌کند.

### `Core\Plugin`
کلاس پایه‌ی انتزاعی که هر پلاگین باید از آن extend کند. متدهای کمکی `hook()` و `filter()` برای ثبت راحت‌تر Hookها در اختیار پلاگین قرار می‌دهد.

### `Core\TemplateEngine`
موتور View سبک که:

- `{{ $var }}` را به خروجی escape‌شده تبدیل می‌کند.
- `{!! $var !!}` را بدون escape چاپ می‌کند.
- به پلاگین‌ها اجازه می‌دهد با `TemplateEngine::extend($pattern, $handler)` سینتکس دلخواه خودشان را (مثل `@cache` یا `@@hello`) تعریف کنند؛ این سینتکس‌ها قبل از تبدیل `{{ }}` روی متن View اعمال می‌شوند.

### `Core\ErrorHandler`
خطاها و استثناها را به‌صورت مرکزی می‌گیرد؛ در حالت `debug` جزئیات نمایش داده می‌شود و در حالت production فقط پیام عمومی.

## سیستم Autoload

`Core\Autoloader` یک نگاشت ساده از Namespace به پوشه نگه می‌دارد (شبیه PSR-4 اما بدون وابستگی به Composer). با `Autoloader::addNamespace('Prefix', '/path')` هر Namespace جدید (از جمله Namespace هر پلاگین) در زمان اجرا اضافه می‌شود.

## ایزوله بودن پلاگین‌ها

هر خطای throw شده در زمان لود یا register یا boot یک پلاگین با `try/catch` گرفته می‌شود و در `PluginManager::errors()` ثبت می‌شود؛ این یعنی یک پلاگین خراب هیچ‌وقت کل سایت را از کار نمی‌اندازد.
