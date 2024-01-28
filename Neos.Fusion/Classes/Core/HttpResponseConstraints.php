<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc\ActionResponse;
use Psr\Http\Message\ResponseInterface;

final class HttpResponseConstraints
{
    private ResponseInterface $partialResponse;

    public function __construct()
    {
        $this->partialResponse = new Response();
    }

    /**
     * @deprecated
     */
    public function setAndMergeFromActionResponse(ActionResponse $actionResponse)
    {
        $this->partialResponse = $this->applyToResponse($actionResponse->buildHttpResponse());
    }

    /**
     * Gets the response status code.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->partialResponse->getStatusCode();
    }

    /**
     * @param int $code The 3-digit integer result code to set.
     */
    public function setStatus(int $code)
    {
        $this->partialResponse = $this->partialResponse->withStatus($code);
    }

    /**
     * Retrieves all message header values.
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getPartialResponse(): ResponseInterface
    {
        return $this->partialResponse->getHeaders();
    }

    /**
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     */
    public function setHeader(string $name, $value)
    {
        $this->partialResponse = $this->partialResponse->withHeader($name, $value);
    }

    /**
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     */
    public function setAndMergeHeader(string $name, $value)
    {
        $this->partialResponse = $this->partialResponse->withAddedHeader($name, $value);
    }

    /**
     * @param string $name Case-insensitive header field name to remove.
     */
    public function unsetHeader(string $name)
    {
        $this->partialResponse = $this->partialResponse->withoutHeader($name);
    }

    public function applyToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->partialResponse->getHeaders() as $name => $values) {
            $response = $response->withAddedHeader($name, $values);
        }

        // preserve non 200 status codes that would otherwise be overwritten
        if ($this->partialResponse->getStatusCode() !== 200) {
            $response = $response->withStatus($this->partialResponse->getStatusCode());
        }

        // reset internal state
        $this->partialResponse = new Response();

        return $response;
    }
}
