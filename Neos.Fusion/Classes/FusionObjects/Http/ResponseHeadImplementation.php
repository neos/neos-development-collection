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

use function GuzzleHttp\Psr7\str;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
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
        if (ResponseInformationHelper::getStatusMessageByCode($statusCode) === 'Unknown Status') {
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
        $httpResponse = new \GuzzleHttp\Psr7\Response($this->getStatusCode(), $this->getHeaders(), null, $this->getHttpVersion());
        return str($httpResponse);
    }
}
