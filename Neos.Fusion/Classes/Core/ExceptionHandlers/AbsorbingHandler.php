<?php
namespace Neos\Fusion\Core\ExceptionHandlers;

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

/**
 * Renders the element as an empty string
 */
class AbsorbingHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Returns an empty string
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        $this->systemLogger->log('Absorbed Exception: ' . $exception->getMessage(), LOG_DEBUG, array('fusionPath' => $fusionPath, 'referenceCode' => $referenceCode), 'Neos.Fusion', \Neos\Fusion\Core\ExceptionHandlers\AbsorbingHandler::class);
        return '';
    }

    /**
     * The absorbing handler is meant to catch loose evaluation errors (like missing assets) in a useful way,
     * therefor caching is desired.
     *
     * @param string $fusionPath
     * @param \Exception $exception
     * @return boolean
     */
    protected function exceptionDisablesCache($fusionPath, \Exception $exception)
    {
        return false;
    }
}
