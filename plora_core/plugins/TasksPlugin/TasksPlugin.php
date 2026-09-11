<?php

declare(strict_types=1);

namespace TasksPlugin;

use Core\Database;
use Core\Plugin;
use Core\Request;
use Core\Response;
use Core\Router;

class TasksPlugin extends Plugin
{
    public function register(): void
    {
        $this->hook('register_routes', [$this, 'registerRoutes']);
    }

    public function boot(): void
    {
        if (Database::isEnabled()) {
            $this->migrate();
        }
    }

    public function registerRoutes(Router $router): void
    {
        $router->get('/tasks', [$this, 'index']);
        $router->get('/tasks/create', [$this, 'create']);
        $router->post('/tasks', [$this, 'store']);
        $router->get('/tasks/{id}/edit', [$this, 'edit']);
        $router->post('/tasks/{id}/update', [$this, 'update']);
        $router->post('/tasks/{id}/delete', [$this, 'destroy']);
    }

    private function migrate(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function index(Request $request, array $params): string
    {
        if (!Database::isEnabled()) {
            return $this->disabledMessage();
        }

        try {
            $tasks = Database::fetchAll('SELECT * FROM tasks ORDER BY id DESC');
        } catch (\Throwable $e) {
            return $this->errorMessage($e);
        }

        return $this->view('index', ['tasks' => $tasks]);
    }

    public function create(Request $request, array $params): string
    {
        if (!Database::isEnabled()) {
            return $this->disabledMessage();
        }

        return $this->view('create');
    }

    public function store(Request $request, array $params): Response
    {
        $title = trim((string) $request->input('title', ''));

        if ($title !== '' && Database::isEnabled()) {
            try {
                Database::insert('tasks', [
                    'title' => $title,
                    'completed' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                return (new Response())->status(500)->body($this->errorMessage($e));
            }
        }

        return (new Response())->redirect('/tasks');
    }

    public function edit(Request $request, array $params): string
    {
        if (!Database::isEnabled()) {
            return $this->disabledMessage();
        }

        try {
            $task = Database::fetchOne('SELECT * FROM tasks WHERE id = :id', ['id' => $params['id']]);
        } catch (\Throwable $e) {
            return $this->errorMessage($e);
        }

        if ($task === null) {
            return '<h1>Task not found</h1>';
        }

        return $this->view('edit', ['task' => $task]);
    }

    public function update(Request $request, array $params): Response
    {
        $title = trim((string) $request->input('title', ''));
        $completed = $request->input('completed') !== null ? 1 : 0;

        if ($title !== '' && Database::isEnabled()) {
            try {
                Database::update(
                    'tasks',
                    ['title' => $title, 'completed' => $completed],
                    'id = :id',
                    ['id' => $params['id']]
                );
            } catch (\Throwable $e) {
                return (new Response())->status(500)->body($this->errorMessage($e));
            }
        }

        return (new Response())->redirect('/tasks');
    }

    public function destroy(Request $request, array $params): Response
    {
        if (Database::isEnabled()) {
            try {
                Database::delete('tasks', 'id = :id', ['id' => $params['id']]);
            } catch (\Throwable $e) {
                return (new Response())->status(500)->body($this->errorMessage($e));
            }
        }

        return (new Response())->redirect('/tasks');
    }

    private function disabledMessage(): string
    {
        return '<h1>TasksPlugin</h1>'
            . '<p>دیتابیس فعال نیست. مقدار <code>database_enabled</code> را در <code>config/config.php</code> و اطلاعات اتصال را در <code>database/config.php</code> تنظیم کنید.</p>';
    }

    private function errorMessage(\Throwable $e): string
    {
        return '<h1>Database Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
