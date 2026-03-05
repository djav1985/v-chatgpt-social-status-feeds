<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Response.php
 * Description: Simple HTTP response representation.
 */

namespace App\Core;

/**
 * Simple HTTP response representation.
 *
 * @phpstan-type Headers array<string, list<string>>
 */
class Response
{
    /** @var int */
    private int $statusCode;

    /** @var string */
    private string $reasonPhrase;

    /**
     * Headers stored as name => list of values (multiple values per header are
     * supported, e.g. multiple Set-Cookie headers).
     *
     * @var array<string, list<string>>
     */
    private array $headers;

    /** @var string */
    private string $body;

    /** @var array<int, string> */
    private static array $reasonPhrases = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * @param int    $statusCode  HTTP status code (default 200).
     * @param array<string, string|list<string>> $headers Initial headers.
     * @param string $body        Response body.
     * @param string $reasonPhrase Custom reason phrase; resolved from $statusCode when empty.
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        string $body = '',
        string $reasonPhrase = ''
    ) {
        $this->statusCode   = $statusCode;
        $this->body         = $body;
        $this->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::$reasonPhrases[$statusCode] ?? '');
        $this->headers = [];

        foreach ($headers as $name => $value) {
            $this->headers[self::normalizeHeaderName($name)] = is_array($value) ? $value : [$value];
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Return the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return the reason phrase.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Return all header values for the given name, or an empty array when the
     * header is not present.
     *
     * @return list<string>
     */
    public function getHeader(string $name): array
    {
        return $this->headers[self::normalizeHeaderName($name)] ?? [];
    }

    /**
     * Return all header values for the given name joined by ', '.
     * Returns an empty string when the header is not present.
     */
    public function getHeaderLine(string $name): string
    {
        $values = $this->getHeader($name);
        return implode(', ', $values);
    }

    /**
     * Return true when the response carries the named header.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[self::normalizeHeaderName($name)]);
    }

    /**
     * Return all headers as name => list<string>.
     *
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Return the response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    // -------------------------------------------------------------------------
    // Immutable-style mutators  (return a new instance)
    // -------------------------------------------------------------------------

    /**
     * Return a new instance with the given status code (and optional reason phrase).
     */
    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->statusCode   = $code;
        $clone->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::$reasonPhrases[$code] ?? '');
        return $clone;
    }

    /**
     * Return a new instance with the specified header value(s) replacing any
     * existing values for that header name.
     *
     * @param string|list<string> $value
     */
    public function withHeader(string $name, string|array $value): self
    {
        $clone = clone $this;
        $clone->headers[self::normalizeHeaderName($name)] = is_array($value) ? $value : [$value];
        return $clone;
    }

    /**
     * Return a new instance with an additional value appended to the header.
     */
    public function withAddedHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $key = self::normalizeHeaderName($name);
        $existing = $clone->headers[$key] ?? [];
        $existing[] = $value;
        $clone->headers[$key] = $existing;
        return $clone;
    }

    /**
     * Return a new instance without the specified header.
     */
    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        unset($clone->headers[self::normalizeHeaderName($name)]);
        return $clone;
    }

    /**
     * Return a new instance with the given body.
     */
    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Emit
    // -------------------------------------------------------------------------

    /**
     * Send the response: emit the status line, headers, and body to the client.
     *
     * Should be called only once, and only when no output has already been sent.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $values) {
                $replace = true;
                foreach ($values as $value) {
                    header($name . ': ' . $value, $replace);
                    $replace = false; // subsequent values for same name must not replace
                }
            }
        }

        echo $this->body;
    }

    // -------------------------------------------------------------------------
    // Static factories
    // -------------------------------------------------------------------------

    /**
     * Create a redirect response.
     *
     * @param string $url        Target URL.
     * @param int    $statusCode 301, 302, 303, or 307 (defaults to 302).
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self($statusCode, ['Location' => $url]);
    }

    /**
     * Create a plain-text response.
     */
    public static function text(string $body, int $statusCode = 200): self
    {
        return new self($statusCode, ['Content-Type' => 'text/plain; charset=UTF-8'], $body);
    }

    /**
     * Create an HTML response.
     */
    public static function html(string $body, int $statusCode = 200): self
    {
        return new self($statusCode, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }

    /**
     * Create a JSON response.
     *
     * @param array<mixed>|object $data Data to JSON-encode.
     * @param int                 $statusCode HTTP status code.
     * @param int                 $flags json_encode() flags (defaults to JSON_THROW_ON_ERROR).
     *                                  JSON_THROW_ON_ERROR is always OR-ed in to guarantee that
     *                                  a \JsonException is thrown rather than returning false.
     * @throws \JsonException When encoding fails.
     */
    public static function json(array|object $data, int $statusCode = 200, int $flags = JSON_THROW_ON_ERROR): self
    {
        // Always include JSON_THROW_ON_ERROR so the function never silently
        // returns false regardless of what the caller passes in $flags.
        $body = json_encode($data, $flags | JSON_THROW_ON_ERROR);
        return new self($statusCode, ['Content-Type' => 'application/json; charset=UTF-8'], $body);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Normalize a header name to Title-Case so lookups are case-insensitive.
     *
     * e.g. "content-type" -> "Content-Type"
     */
    private static function normalizeHeaderName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower(trim($name)))));
    }
}
