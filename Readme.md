A lightweight PHP package that provides an object-oriented layer for handling HTTP requests, responses, cookies, and file uploads.

Installation
------------

This library uses PSR-4 autoloading. Recommended installation via Composer:

```powershell
composer require binaryceo/http-foundation
```

If you are developing the library and want to run tests:

```powershell
composer install
```

Usage
-----

```php
require 'vendor/autoload.php';

use BinaryCEO\Component\Http\Request;

$request = Request::fromGlobals();
echo $request->method();
echo $request->uri();
echo $request->input('name');
```

Running tests
-------------

To run the PHPUnit test suite locally:

```powershell
composer install
composer test
```

If you don't want to use Composer, there's a minimal CLI test runner at `tests/RequestTest.php` (but PHPUnit is recommended).

Contributing
------------

1. Fork the repository
2. Create a feature branch
3. Send a pull request

License
-------

This project is licensed under the MIT License.  
See the [LICENSE](./LICENSE) file for details.