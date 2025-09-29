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

        return new self($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES, $content);
    }

    /**
     * Accessing query string data by name.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Getting data submitted through the POST method.
     */
    public function request(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Retrieve input regardless of whether it is in the query string or sent via POST.
     */
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

    /**
     * Collecting all inputs.
     */
    public function all(): array
    {
        $body = $this->json();
        return array_merge($this->query, $this->request, is_array($body) ? $body : []);
    }

    /**
     * Extract a subset of the input array by the given keys.
     *
     * Useful for retrieving only the fields you care about
     * from the full request data.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Return the input array without the specified keys.
     *
     * Useful for excluding sensitive or unnecessary fields
     * from the full request data.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    // Accessing server and headers.
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    // Access a header using its key.
    public function header(string $key, mixed $default = null): mixed
    {
        $lk = strtolower($key);
        return $this->headers[$lk] ?? $default;
    }

    // Access headers.
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Parse HTTP headers from the given server array.
     *
     * This method extracts all entries starting with "HTTP_" and converts
     * them into normalized header names (lowercase, with dashes).
     * It also includes common non-HTTP_ headers such as CONTENT_TYPE
     * and CONTENT_LENGTH for completeness.
     */
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

    // Access request method.
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

    // Getting request uri.
    public function uri(): string
    {
        return $this->server('REQUEST_URI', '/') ?: '/';
    }

    // Getting path from request uri.
    public function path(): string
    {
        $uri = $this->uri();
        $parts = parse_url($uri);
        return $parts['path'] ?? '/';
    }

    // Getting content.
    public function content(): mixed
    {
        return $this->content;
    }

    /**
     * Decode the request body as JSON if the content type is application/json.
     *
     * Returns the decoded array on success, or null if empty or invalid.
     */
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

    // Access file using its key.
    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    // Access all files.
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Determine if the request was made via AJAX.
     *
     * Checks the "X-Requested-With" header for "XMLHttpRequest".
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    /**
     * Determine if the request content type is JSON.
     *
     * Checks the "Content-Type" header for "application/json".
     */
    public function isJson(): bool
    {
        $ctype = $this->header('content-type') ?? '';
        return str_contains($ctype, 'application/json');
    }

    /**
     * Retrieve the Bearer token from the Authorization header.
     *
     * Returns the token string if present and valid, or null otherwise.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if (!$auth) {
            return null;
        }
        if (str_starts_with(strtolower($auth), 'bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}