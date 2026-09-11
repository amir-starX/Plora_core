<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش تسک</title>
</head>
<body>
    <h1>ویرایش تسک</h1>
    <form method="post" action="/tasks/{{ $task['id'] }}/update">
        <input type="text" name="title" value="{{ $task['title'] }}" required>
        <label>
            <input type="checkbox" name="completed" <?= ((int) $task['completed'] === 1) ? 'checked' : '' ?>>
            انجام‌شده
        </label>
        <button type="submit">به‌روزرسانی</button>
    </form>
    <p><a href="/tasks">بازگشت به لیست</a></p>
</body>
</html>
