<?php
namespace TYPO3\Neos\TypoScript;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;
use TYPO3\Neos\Exception as NeosException;

/**
 * Create a link to a node
 */
class NodeUriImplementation extends AbstractTypoScriptObject
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * A node object or a string node path or NULL to resolve the current document node
     *
     * @return mixed
     */
    public function getNode()
    {
        return $this->tsValue('node');
    }

    /**
     * Additional arguments to be passed to the UriBuilder (for example pagination parameters)
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->tsValue('arguments');
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->tsValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string
     */
    public function getSection()
    {
        return (string)$this->tsValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->tsValue('additionalParams');
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = TRUE
     *
     * @return array
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->tsValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If TRUE, the current query parameters will be kept in the URI
     *
     * @return boolean
     */
    public function getAddQueryString()
    {
        return (boolean)$this->tsValue('addQueryString');
    }

    /**
     * If TRUE, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (boolean)$this->tsValue('absolute');
    }

    /**
     * The name of the base node inside the TypoScript context to use for resolving relative paths.
     *
     * @return string
     */
    public function getBaseNodeName()
    {
        return $this->tsValue('baseNodeName');
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws NeosException
     */
    public function evaluate()
    {
        $baseNode = null;
        $baseNodeName = $this->getBaseNodeName() ?: 'documentNode';
        $currentContext = $this->tsRuntime->getCurrentContext();
        if (isset($currentContext[$baseNodeName])) {
            $baseNode = $currentContext[$baseNodeName];
        } else {
            throw new NeosException(sprintf('Could not find a node instance in TypoScript context with name "%s" and no node instance was given to the node argument. Set a node instance in the TypoScript context or pass a node object to resolve the URI.', $baseNodeName), 1373100400);
        }

        try {
            return $this->linkingService->createNodeUri(
                $this->tsRuntime->getControllerContext(),
                $this->getNode(),
                $baseNode,
                $this->getFormat(),
                $this->isAbsolute(),
                $this->getAdditionalParams(),
                $this->getSection(),
                $this->getAddQueryString(),
                $this->getArgumentsToBeExcludedFromQueryString()
            );
        } catch (NeosException $exception) {
            $this->systemLogger->logException($exception);
            return '';
        }
    }
}
