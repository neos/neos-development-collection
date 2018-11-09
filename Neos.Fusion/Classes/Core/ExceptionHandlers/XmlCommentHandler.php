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

use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

/**
 * Creates xml comments from exceptions
 */
class XmlCommentHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ThrowableStorageInterface
     */
    private $throwableStorage;

    /**
     * @param LoggerInterface $logger
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function injectThrowableStorage(ThrowableStorageInterface $throwableStorage)
    {
        $this->throwableStorage = $throwableStorage;
    }

    /**
     * Provides an XML comment containing the exception
     *
     * @param string $fusionPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($fusionPath, \Exception $exception, $referenceCode)
    {
        $logMessage = $this->throwableStorage->logThrowable($exception);
        $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
        if (isset($referenceCode)) {
            return sprintf(
                '<!-- Exception while rendering %s: %s (%s) -->',
                $this->formatScriptPath($fusionPath, ''),
                htmlspecialchars($exception->getMessage()),
                $referenceCode
            );
        } else {
            return sprintf(
                '<!-- Exception while rendering %s: %s -->',
                $this->formatScriptPath($fusionPath, ''),
                htmlspecialchars($exception->getMessage())
            );
        }
    }
}
