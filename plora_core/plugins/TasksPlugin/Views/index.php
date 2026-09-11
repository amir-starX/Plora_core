<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لیست تسک‌ها</title>
</head>
<body>
    <h1>لیست تسک‌ها</h1>
    <p><a href="/tasks/create">+ افزودن تسک جدید</a></p>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>#</th>
            <th>عنوان</th>
            <th>وضعیت</th>
            <th>عملیات</th>
        </tr>
        <?php foreach ($tasks as $task): ?>
        <tr>
            <td>{{ $task['id'] }}</td>
            <td>{{ $task['title'] }}</td>
            <td><?= ((int) $task['completed'] === 1) ? 'انجام‌شده' : 'در انتظار' ?></td>
            <td>
                <a href="/tasks/{{ $task['id'] }}/edit">ویرایش</a>
                <form method="post" action="/tasks/{{ $task['id'] }}/delete" style="display:inline">
                    <button type="submit" onclick="return confirm('حذف شود؟')">حذف</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($tasks)): ?>
        <tr>
            <td colspan="4">هنوز تسکی ثبت نشده است.</td>
        </tr>
        <?php endif; ?>
    </table>
</body>
</html>
