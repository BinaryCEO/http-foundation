<?php
namespace BinaryCEO\Component\Http;

/**
 * A lightweight, object-oriented HTTP Request representation.
 *
 * This class wraps PHP superglobals (or provided arrays) and
 * exposes convenient helpers for accessing query, post, headers,
 * JSON body and uploaded files.
 */
class Request
{
    protected array $query;
    protected array $request;
    protected array $server;
    protected array $headers;
    protected array $cookies;
    protected array $files;
    protected mixed $content;

    public function __construct(array $query = [], array $request = [], array $server = [], array $cookies = [], array $files = [], mixed $content = null)
    {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->content = $content;
        $this->headers = $this->parseHeadersFromServer($server);
    }

    /**
     * Create a Request instance from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        $content = null;
        // Read raw body when available
        $content = file_get_contents('php://input');

        return new self($_GET, $_REQUEST, $_SERVER, $_COOKIE, $_FILES, $content);
    }

    // Basic accessors -------------------------------------------------
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function request(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->request)) {
            return $this->request[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        // if JSON body, try to read
        $json = $this->json();
        if (is_array($json) && array_key_exists($key, $json)) {
            return $json[$key];
        }

        return $default;
    }

    public function all(): array
    {
        $body = $this->json();
        return array_merge($this->query, $this->request, is_array($body) ? $body : []);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    // Server / headers ------------------------------------------------
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $lk = strtolower($key);
        return $this->headers[$lk] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    protected function parseHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        // Common non-HTTP_ headers
        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    // Method / URL helpers --------------------------------------------
    public function method(): string
    {
        $method = $this->server('REQUEST_METHOD', 'GET');
        // support method override via _method or header
    $override = $this->request('_method') ?? $this->header('x-http-method-override');
        if ($override) {
            return strtoupper($override);
        }

        return strtoupper($method);
    }

    public function uri(): string
    {
        return $this->server('REQUEST_URI', '/') ?: '/';
    }

    public function path(): string
    {
        $uri = $this->uri();
        $parts = parse_url($uri);
        return $parts['path'] ?? '/';
    }

    // Content / JSON --------------------------------------------------
    public function content(): mixed
    {
        return $this->content;
    }

    public function json(): mixed
    {
        $ctype = $this->header('content-type') ?? '';
        if (str_contains($ctype, 'application/json')) {
            $raw = $this->content();
            if ($raw === null || $raw === '') {
                return null;
            }

            $decoded = json_decode($raw, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return null;
    }

    // Files -----------------------------------------------------------
    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    // Convenience -----------------------------------------------------
    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        $ctype = $this->header('content-type') ?? '';
        return str_contains($ctype, 'application/json');
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if (!$auth) return null;
        if (str_starts_with(strtolower($auth), 'bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
