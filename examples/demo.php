<?php
require __DIR__ . '/../Request.php';

use BinaryCEO\Component\Http\Request;

// Simulate globals for demo
$_GET = ['page' => '1'];
$_POST = ['name' => 'Alice'];
$_REQUEST = array_merge($_GET, $_POST);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/users?active=1';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate JSON body
$body = json_encode(['title' => 'Hello', 'count' => 3]);
file_put_contents('php://temp', $body); // no-op for CLI, but Request reads php://input

$request = Request::fromGlobals();

echo "method: " . $request->method() . PHP_EOL;
echo "uri: " . $request->uri() . PHP_EOL;
echo "path: " . $request->path() . PHP_EOL;
echo "isAjax: " . ($request->isAjax() ? 'yes' : 'no') . PHP_EOL;
echo "query page: " . $request->query('page') . PHP_EOL;
echo "request name: " . $request->request('name') . PHP_EOL;
echo "input title: " . $request->input('title') . PHP_EOL;
print_r($request->all());
