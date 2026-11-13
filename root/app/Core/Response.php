<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Response.php
 * Description: PSR-7 HTTP response with application view support.
 */

declare(strict_types=1);

namespace App\Core;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Response as PsrResponse;

/** PSR-7 response with application view and file-streaming support. */
class Response extends PsrResponse
{
    private ?string $view = null;
    /** @var array<string, mixed> */
    private array $viewData = [];
    private ?string $file = null;

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        $body = '',
        string $reasonPhrase = ''
    ) {
        parent::__construct($statusCode, $headers, $body, '1.1', $reasonPhrase);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self($statusCode, ['Location' => $url]);
    }

    public static function text(string $body, int $statusCode = 200): self
    {
        return new self($statusCode, ['Content-Type' => 'text/plain; charset=UTF-8'], $body);
    }

    public static function html(string $body, int $statusCode = 200): self
    {
        return new self($statusCode, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }

    /** @param array<mixed>|object $data @throws \JsonException */
    public static function json(array|object $data, int $statusCode = 200, int $flags = JSON_THROW_ON_ERROR): self
    {
        return new self(
            $statusCode,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            json_encode($data, $flags | JSON_THROW_ON_ERROR)
        );
    }

    /** @param array<string, mixed> $data */
    public static function view(string $view, array $data = [], int $statusCode = 200): self
    {
        return (new self($statusCode))->withView($view, $data);
    }

    public static function file(
        string $filePath,
        string $contentType = 'application/octet-stream',
        int $statusCode = 200
    ): self {
        $response = new self($statusCode, ['Content-Type' => $contentType]);
        return $response->withFile($filePath);
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    /** @param array<string, mixed> $data */
    public function withView(string $view, array $data = []): self
    {
        $clone = clone $this;
        $clone->view = $view;
        $clone->viewData = $data;
        return $clone;
    }

    /** @param array<string, mixed> $data */
    public function withViewData(array $data): self
    {
        $clone = clone $this;
        $clone->viewData = array_merge($clone->viewData, $data);
        return $clone;
    }

    public function withFile(string $filePath): self
    {
        $clone = clone $this;
        $clone->file = $filePath;
        return $clone->withBody(new LazyOpenStream($filePath, 'r'));
    }

    /** Emit the response for the native PHP web server. */
    public function send(): void
    {
        if ($this->file !== null && (!is_file($this->file) || !is_readable($this->file))) {
            ErrorManager::log('File stream failed readability check: ' . $this->file);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8', true);
            }
            echo 'Internal Server Error';
            return;
        }

        if (!headers_sent()) {
            http_response_code($this->getStatusCode());
            foreach ($this->getHeaders() as $name => $values) {
                $replace = true;
                foreach ($values as $value) {
                    header($name . ': ' . $value, $replace);
                    $replace = false;
                }
            }
        }

        if ($this->view !== null) {
            self::renderView($this->view, $this->viewData);
            return;
        }

        $body = $this->getBody();
        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }

    /** @param array<string, mixed> $data */
    private static function renderView(string $view, array $data): void
    {
        if (!preg_match('/^[A-Za-z0-9_\/-]+$/', $view)) {
            throw new \RuntimeException('Invalid view name');
        }

        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}
