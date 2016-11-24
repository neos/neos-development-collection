<?php
namespace TYPO3\TypoScript\Core\ExceptionHandlers;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Creates xml comments from exceptions
 */
class XmlCommentHandler extends AbstractRenderingExceptionHandler
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Provides an XML comment containing the exception
     *
     * @param string $typoScriptPath path causing the exception
     * @param \Exception $exception exception to handle
     * @param integer $referenceCode
     * @return string
     */
    protected function handle($typoScriptPath, \Exception $exception, $referenceCode)
    {
        $this->systemLogger->logException($exception);
        if (isset($referenceCode)) {
            return sprintf(
                '<!-- Exception while rendering %s: %s (%s) -->',
                $this->formatScriptPath($typoScriptPath, ''),
                htmlspecialchars($exception->getMessage()),
                $referenceCode
            );
        } else {
            return sprintf(
                '<!-- Exception while rendering %s: %s -->',
                $this->formatScriptPath($typoScriptPath, ''),
                htmlspecialchars($exception->getMessage())
            );
        }
    }
}
