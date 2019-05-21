<?php
namespace Neos\Fusion\FusionObjects\Http;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Response Head generate a standard HTTP response head
 * @api
 */
class ResponseHeadImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * Get HTTP protocol version
     *
     * @return string
     */
    public function getHttpVersion()
    {
        $httpVersion = $this->fusionValue('httpVersion');
        if ($httpVersion === null) {
            $httpVersion = 'HTTP/1.1';
        }
        return trim($httpVersion);
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        $statusCode = $this->fusionValue('statusCode');
        if ($statusCode === null) {
            $statusCode = 200;
        }
        if (ResponseInformationHelper::getStatusMessageByCode($statusCode) === 'Unknown Status') {
            throw new \InvalidArgumentException('Unknown HTTP status code', 1412085703);
        }
        return (integer)$statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = $this->fusionValue('headers');
        if (!is_array($headers)) {
            $headers = [];
        }
        return $headers;
    }

    /**
     * Just return the processed value
     *
     * @return ResponseInterface
     */
    public function evaluate(): ResponseInterface
    {
        $httpVersion = $this->getHttpVersion();
        if (strpos($httpVersion, 'HTTP/') === 0) {
            $httpVersion = substr($httpVersion, 5);
        }

        $response = $this->responseFactory->createResponse($this->getStatusCode())->withProtocolVersion($httpVersion);
        foreach ($this->getHeaders() as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }
        return $response;
    }
}
