<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use BinaryCEO\Component\Http\Request;

class RequestTest extends TestCase
{
    protected array $backupServer;
    protected array $backupGet;
    protected array $backupPost;

    protected function setUp(): void
    {
        // backup superglobals
        $this->backupServer = $_SERVER ?? [];
        $this->backupGet = $_GET ?? [];
        $this->backupPost = $_POST ?? [];
    }

    protected function tearDown(): void
    {
        // restore superglobals
        $_SERVER = $this->backupServer;
        $_GET = $this->backupGet;
        $_POST = $this->backupPost;
    }

    public function testQueryAndRequestAndInput()
    {
        $_GET = ['q' => 'search'];
        $_POST = ['name' => 'Bob'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test?q=search';

        $request = Request::fromGlobals();
        $this->assertSame('search', $request->query('q'));
        $this->assertSame('Bob', $request->request('name'));
        $this->assertSame('Bob', $request->input('name'));
    }

    public function testMethodOverride()
    {
        $_GET = [];
        $_POST = ['_method' => 'PUT'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Request::fromGlobals();
        $this->assertSame('PUT', $request->method());
    }

    public function testBearerTokenFromHeader()
    {
        $_SERVER = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123';

        $request = Request::fromGlobals();
        $this->assertSame('abc123', $request->bearerToken());
    }
}
