# راهنمای استفاده از فریم‌ورک

این فایل نحوه‌ی نصب، راه‌اندازی و کار روزمره با فریم‌ورک را توضیح می‌دهد.

## نصب

1. پروژه را روی سرور (یا Apache/PHP محلی خودتان) قرار دهید.
2. مطمئن شوید PHP نسخه `8.0` یا بالاتر و ماژول `mod_rewrite` فعال است.
3. `DocumentRoot` را روی ریشه‌ی پروژه (همان پوشه‌ای که `index.php` در آن است) تنظیم کنید تا فایل `.htaccess` عمل کند.
4. اگر از دیتابیس استفاده می‌کنید، اطلاعات اتصال را در `database/config.php` وارد کنید و در `config/config.php` مقدار `database_enabled` را `true` کنید.

## اجرای پروژه

با یک سرور PHP توکار می‌توانید پروژه را سریع تست کنید:

```bash
php -S localhost:8000
```

سپس در مرورگر به `http://localhost:8000` بروید.

## ساختار کلی

| مسیر | توضیح |
|---|---|
| `index.php` | تنها نقطه ورود درخواست‌ها (Front Controller) |
| `core/` | زیرساخت فریم‌ورک (Router، Database، PluginManager، Hook، ...) |
| `app/` | کد اختصاصی پروژه شما (Controller، Model، View، Routes) |
| `plugins/` | پلاگین‌ها |
| `config/` | تنظیمات کلی و لیست پلاگین‌های فعال |
| `database/` | اطلاعات اتصال دیتابیس |

## تعریف مسیر (Route)

مسیرها در `app/Routes.php` تعریف می‌شوند:

```php
$router->get('/', 'HomeController@index');
$router->get('/hello/{name}', 'HomeController@hello');
$router->post('/contact', function ($request, $params) {
    return 'دریافت شد';
});
```

- پارامترهای داخل `{ }` به‌صورت خودکار به آرایه‌ی `$params` در متد کنترلر پاس داده می‌شوند.
- می‌توانید Handler را به شکل `'Controller@method'`، Closure یا هر `callable` دیگری تعریف کنید.
- متدهای `get`, `post`, `put`, `patch`, `delete`, `any` در دسترس هستند.

## کنترلرها

کنترلرها در `app/Controllers/` قرار می‌گیرند و در سازنده‌شان نمونه‌ی `App` را دریافت می‌کنند:

```php
namespace App\Controllers;

use Core\App;
use Core\Request;

class HomeController
{
    public function __construct(private App $app) {}

    public function index(Request $request, array $params): string
    {
        return $this->app->view('home', ['title' => 'خوش آمدید']);
    }
}
```

## Viewها

فایل‌های View در `app/Views/` قرار می‌گیرند و از طریق `$app->view('نام‌فایل', $data)` رندر می‌شوند. داخل View می‌توانید از سینتکس زیر استفاده کنید:

- `{{ $variable }}` → چاپ امن (escape شده) مقدار.
- `{!! $html !!}` → چاپ بدون escape (برای HTML خام).
- هر سینتکس اضافه‌ای که پلاگین‌ها ثبت کرده باشند (مثلاً `@cache`, `@@hello`).

## کار با دیتابیس

```php
use Core\Database;

$users = Database::fetchAll('SELECT * FROM users WHERE active = :active', ['active' => 1]);
$id = Database::insert('users', ['name' => 'Ali', 'email' => 'ali@example.com']);
```

اگر `database_enabled` در `config/config.php` مقدار `false` باشد، لازم نیست از این کلاس استفاده کنید؛ پروژه بدون دیتابیس هم کار می‌کند.

## فعال یا غیرفعال کردن پلاگین‌ها

در `config/config.php`:

```php
'plugins' => [
    'SamplePlugin' => true,
    'AdminPanel'   => false,
],
```

هر پلاگینی که مقدارش `false` باشد اصلاً لود نمی‌شود (نه فایل‌هایش خوانده می‌شود و نه هوک‌هایش ثبت می‌شود).

## Middleware

```php
$app->addMiddleware(function ($request, $response) {
    if (!$request->header('Authorization')) {
        $response->status(401)->body('Unauthorized')->send();
        return false;
    }
    return true;
});
```

بازگرداندن `false` از میان‌افزار، اجرای درخواست را متوقف می‌کند.

## مستندات مرتبط

- برای شناخت کامل معماری هسته: `FRAMEWORK.md`
- برای ساخت پلاگین جدید:`PLUGIN_GUIDE.md`
- برای دیدن پلاگین های نوشته شده `plugins.md`

## راه های ارتباطی با من

- در پروژه معرفی من
