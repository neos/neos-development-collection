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
    private string $fusionPath;

    public function __construct(string $message, int $code, \Exception $previous, string $fusionPath)
    {
        parent::__construct($message, $code, $previous);
        $this->fusionPath = $fusionPath;
    }

    /**
     * @return string
     */
    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    /**
     * Unwrap this Fusion RuntimeException
     */
    public function getWrappedException(): \Exception
    {
        /** @phpstan-ignore-next-line due to overridden construction, we are sure that the previous exists. */
        return $this->getPrevious();
    }
}
