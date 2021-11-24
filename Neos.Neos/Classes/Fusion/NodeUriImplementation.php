<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Neos\Service\LinkingService;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Exception as NeosException;
use Psr\Log\LoggerInterface;

/**
 * Create a link to a node
 */
class NodeUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

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
     * A node object or a string node path or NULL to resolve the current document node
     *
     * @return mixed
     */
    public function getNode()
    {
        return $this->fusionValue('node');
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->fusionValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string
     */
    public function getSection()
    {
        return (string)$this->fusionValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array
     */
    public function getAdditionalParams()
    {
        return array_merge($this->fusionValue('additionalParams'), $this->fusionValue('arguments'));
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = true
     *
     * @return array
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->fusionValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If true, the current query parameters will be kept in the URI
     *
     * @return boolean
     */
    public function getAddQueryString()
    {
        return (boolean)$this->fusionValue('addQueryString');
    }

    /**
     * If true, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (boolean)$this->fusionValue('absolute');
    }

    /**
     * The name of the base node inside the Fusion context to use for resolving relative paths.
     *
     * @return string
     */
    public function getBaseNodeName()
    {
        return $this->fusionValue('baseNodeName');
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
        $currentContext = $this->runtime->getCurrentContext();
        if (isset($currentContext[$baseNodeName])) {
            $baseNode = $currentContext[$baseNodeName];
        } else {
            throw new NeosException(sprintf('Could not find a node instance in Fusion context with name "%s" and no node instance was given to the node argument. Set a node instance in the Fusion context or pass a node object to resolve the URI.', $baseNodeName), 1373100400);
        }

        try {
            return $this->linkingService->createNodeUri(
                $this->runtime->getControllerContext(),
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
            // TODO: Revisit if we actually need to store a stack trace.
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }
    }
}
