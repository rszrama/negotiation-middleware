# NegotiationMiddleware

NegotiationMiddleware provides content negotiation middleware for PHP applications using a middleware signature that
requires a request object, a response object, and the next callable in the middleware stack.

This library depends on willdurand/negotation for content negotiation. It allows you to add negotiation to a middleware
stack that:

1. Identifies and matches against a list of acceptable media types.
2. Supplies a default media type in the absence of an Accept request header.
3. Enriches the request object with the negotiated media type.

If the negotiator cannot determine which media type to use in response to the request, it will return a response with a
406 Not Acceptable status.

## Installation

Use [Composer](https://getcomposer.org/) to install NegotiationMiddleware:

```bash
$ composer require rszrama/negotiation-middleware
```

This will install the library and its dependencies. NegotiationMiddleware requires PHP 5.4.0 or newer.

## Usage

Add an instance of NegotiationMiddleware\Negotiator to an application or route level middleware stack, passing two
arguments to the constructor: an array of acceptable media types to be matched against and a boolean indicating
whether or not the middleware should simply match the first acceptable media type in the absence of an Accept header
in the request.

Example from Slim 3.x:

```php
<?php

require 'vendor/autoload.php';

use \NegotiationMiddleware\Negotiator;

$app = new \Slim\App();

$app
    ->get('/hello', function(Slim\Http\Request $request, Slim\Http\Response $response, $args) {
        return $response->write('Hello, world!');
    })
    ->add(new Negotiator(['text/html', 'application/json'], TRUE));

$app->run();

```

In this case, a request to /hello from a web browser will more than likely match the text/html media type specified as
an acceptable media type for this route (which is just as well, given our response isn't valid JSON). However, if the
server received a request that did not have an Accept header, it will still match to text/html since we instructed the
object to supply a default media type.

Note that the Slim\Http\Request object implements PSR-7's ServerRequestInterface. Negotiator::__invoke() requires an
instance of this interface to store the negotiated media type in the request object using an attribute named mediaType.

Thus, the route closure could print the negotiated media type like so:

```php
$app
    ->get('/hello', function(Slim\Http\Request $request, Slim\Http\Response $response, $args) {
        $mediaType = $request->getAttribute('mediaType')->getValue();
        return $response->write($mediaType);
    })
    ->add(new Negotiator(['text/html', 'application/json'], TRUE));

```

The mediaType attribute is an instance of \Negotiation\Accept, which contains a variety of methods for inspecting the
actual matched media type from the request header. Refer to the class documentation of [willdurand/negotiation]
(https://github.com/willdurand/Negotiation) for more information.

## Middleware Signature

Negotiation Middleware uses the middleware signature required by [Slim 3.x](http://www.slimframework.com/docs/concepts/middleware.html),
that of a callable that accepts three arguments: a PSR-7 request object, a PSR-7 response object, and a callable that
represents the next middleware in the stack.

This pattern and its usefulness thanks to the adoption of PSR-7 are discussed in a helpful [blog post by Matthew O'Phinney]
(https://mwop.net/blog/2015-01-08-on-http-middleware-and-psr-7.html), including a similar example:

```php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Middleware {
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        // Execute code before calling the next middleware.
        $next($request, $response);
        // Execute code after calling the next middleware.
        return $response;
    }
}
```

Since this implementation is not specific to Slim (or any other framework), it can be reused by any application that
uses the same middleware signature or adapted to work with other middleware patterns that still make use of PSR-7
request and response objects.

## License

Negotiation Middleware is licensed under the MIT license. See [LICENSE.md](LICENSE.md) for more information.
