<?php
namespace Neos\Fusion\Exception;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Fusion\Exception;

/**
 * This exception wraps an inner exception during rendering.
 */
class RuntimeException extends Exception
{
    /**
     * @var string
     */
    protected $fusionPath;

    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     * @param null $fusionPath
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $fusionPath = null)
    {
        parent::__construct($message, $code, $previous);

        $this->fusionPath = $fusionPath;
    }

    /**
     * @return null|string
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }
}
