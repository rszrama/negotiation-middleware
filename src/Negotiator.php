<?php

namespace NegotiationMiddleware;

use Negotiation\Accept;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Negotiator {
    // @var $negotiator The object that performs the negotiation.
    protected $negotiator;

    // @var $mediaType The object that represents a negotiated media type.
    protected $mediaType;

    // @var string[] $priorities An array of acceptable media types.
    protected $priorities;

    // @var bool $supplyDefault Boolean indicating whether or not to supply a
    // default media type if negotiation cannot determine a match.
    protected $supplyDefault;

    /**
     * @param string[] $priorities An array of acceptable media types.
     * @param bool $supplyDefault
     */
    public function __construct($priorities = [], $supplyDefault = FALSE) {
        // Create the negotiator object.
        $this->negotiator = new \Negotiation\Negotiator();
        $this->mediaType = NULL;

        // Set the priorities array and supply default boolean.
        $this->priorities = $priorities;
        $this->supplyDefault = $supplyDefault;
    }

    /**
     * Performs content negotiation for the request.
     *
     * Content negotiation uses willdurand/negotiation to determine if a request
     * specifies an acceptable media type and, if not, responds immediately with
     * a 406 error (Not Acceptable).
     *
     * If the negotiator middleware has been instructed to supply a default
     * media type and the accept header is empty, it will negotiate a match
     * against the first given priority.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request A PSR-7 request object.
     * @param \Psr\Http\Message\ResponseInterface $response A PSR-7 response object.
     * @param callable $next The next callable in the middleware chain.
     *
     * @return \Psr\Http\Message\ResponseInterface The update response object.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        // Negotiate a media type for the given request.
        $this->negotiateMediaType($request);

        // If an appropriate media type couldn't be determined, respond with a 406.
        if (empty($this->mediaType)) {
            return $response->withStatus(406);
        }

        // Store the negotiated media type in the request object.
        $request = $request->withAttribute('mediaType', $this->mediaType);

        // Call the next middleware.
        return $next($request, $response);
    }

    /**
     * Negotiates a media type for the request and stores it in a property on
     * the middleware object.
     *
     * @param \Psr\Http\Message\RequestInterface $request A PSR-7 request object.
     */
    public function negotiateMediaType(ServerRequestInterface $request) {
        // Look for an accept header in the request object.
        $acceptHeader = $request->getHeaderLine('accept');

        // If the request did not include an accept header...
        if (empty($acceptHeader)) {
            // If a default should be supplied and the priorities array was set...
            if ($this->supplyDefault && !empty($this->priorities)) {
                // Supply a default media type from the priorities array.
                $this->mediaType = new Accept(reset($this->priorities));
            }
        }
        else {
            // Determine the best media type based on the accept header and the
            // server's priorities.
            $this->mediaType = $this->negotiator->getBest($acceptHeader, $this->priorities);
        }
    }
}
