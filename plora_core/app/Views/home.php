<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $content }}</p>

    <p>@@hello('Ali')</p>

    <div>
        @cache(60) <span>This block is cached for 60 seconds. Generated at: {{ date('H:i:s') }}</span>
    </div>

    <p><a href="/tasks">مدیریت تسک‌ها (TasksPlugin + Database)</a></p>
</body>
</html>
