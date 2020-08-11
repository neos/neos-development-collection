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

use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Response;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Response Head generate a standard HTTP response head
 * @api
 */
class ResponseHeadImplementation extends AbstractFusionObject
{
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
        if (Response::getStatusMessageByCode($statusCode) === 'Unknown Status') {
            throw new \InvalidArgumentException('Unknown HTTP status code', 1412085703);
        }
        return (integer)$statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
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
     * @return mixed
     */
    public function evaluate()
    {
        // TODO: Adjust for Neos 5.0 (PSR-7)
        $httpResponse = new Response();
        $httpResponse->setVersion($this->getHttpVersion());
        $httpResponse->setStatus($this->getStatusCode());
        $httpResponse->setHeaders(new Headers());

        foreach ($this->getHeaders() as $name => $value) {
            $httpResponse->setHeader($name, $value);
        }

        return implode("\r\n", $httpResponse->renderHeaders()) . "\r\n\r\n";
    }
}
