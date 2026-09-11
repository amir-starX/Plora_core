<?php

declare(strict_types=1);

namespace SamplePlugin;

use Core\Hook;
use Core\Plugin;
use Core\Router;
use Core\TemplateEngine;

class SamplePlugin extends Plugin
{
    public function register(): void
    {
        $this->hook('register_routes', [$this, 'registerRoutes']);
        $this->hook('before_route', [$this, 'onBeforeRoute']);
        $this->hook('after_render', [$this, 'onAfterRender']);

        $this->registerSyntax();
    }

    public function registerRoutes(Router $router): void
    {
        $router->get('/plugin/hello/{name}', function ($request, $params) {
            return 'Hello, ' . htmlspecialchars((string) $params['name']) . '! (from SamplePlugin)';
        });
    }

    public function onBeforeRoute($request, $app): void
    {
    }

    public function onAfterRender($request, $app): void
    {
    }

    private function registerSyntax(): void
    {
        TemplateEngine::extend(
            '/@@hello\(\s*[\'"]([^\'"]*)[\'"]\s*\)/',
            function (array $matches) {
                $name = $matches[1];
                return '<?= "Hello, " . htmlspecialchars(\'' . addslashes($name) . '\') . "!" ?>';
            }
        );

        TemplateEngine::extend(
            '/@cache\((\d+)\)(.*)$/m',
            function (array $matches) {
                $seconds = (int) $matches[1];
                $body = $matches[2];
                return '<?php echo \\SamplePlugin\\Cache::remember(' . $seconds . ', function () { ob_start(); ?>' . $body . '<?php return ob_get_clean(); }); ?>';
            }
        );
    }
}
