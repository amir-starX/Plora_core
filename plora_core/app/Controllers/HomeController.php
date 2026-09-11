<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\App;
use Core\Request;

class HomeController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function index(Request $request, array $params): string
    {
        return $this->app->view('home', [
            'title' => 'Welcome',
            'content' => 'This page is rendered through the plugin-based framework.',
        ]);
    }

    public function hello(Request $request, array $params): string
    {
        return $this->app->view('hello', [
            'name' => $params['name'] ?? 'World',
        ]);
    }
}
