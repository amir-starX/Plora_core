<?php

declare(strict_types=1);

namespace Core;

class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function body(string $content): static
    {
        $this->body = $content;
        return $this;
    }

    public function json(array $data, int $status = 200): static
    {
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    public function redirect(string $url, int $status = 302): static
    {
        $this->status = $status;
        $this->headers['Location'] = $url;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }
        echo $this->body;
    }
}
