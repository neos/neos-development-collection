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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Service\LinkingService;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Exception as NeosException;
use Neos\Utility\Arrays;
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
    public function getQueryParameters(): array
    {
        return $this->fusionValue('queryParameters') ?: [];
    }

    /**
     * Option to set custom routing arguments
     *
     * Please do not use this functionality to append query parameters and use 'queryParameters' instead:
     *
     *   Neos.Neos:NodeUri {
     *     queryParameters = ${{'q':'search term'}}
     *   }
     *
     * Appending query parameters via the use of exceeding routing arguments relies
     * on `appendExceedingArguments` internally which is discouraged to leverage.
     *
     * But in case you meant to use routing arguments for advanced uri building,
     * you can leverage this low level option.
     *
     * Be aware in order for the routing framework to match and resolve the arguments,
     * your have to define a custom route in Routes.yaml
     *
     * @return array<string, mixed>
     */
    public function getRoutingArguments(): array
    {
        return $this->fusionValue('routingArguments') ?: [];
    }

    /**
     * Legacy notation for routing arguments.
     *
     * @return array
     * @deprecated additionalParams and its alias arguments are deprecated with Neos 8.4. Please use queryParameters or routingArguments instead.
     * @see getQueryParameters
     * @see getRoutingArguments
     */
    public function getAdditionalParams()
    {
        return array_merge($this->fusionValue('additionalParams'), $this->fusionValue('arguments'));
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = true
     *
     * @return array
     * @deprecated To be removed with Neos 9
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->fusionValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If true, the current query parameters will be kept in the URI
     *
     * @return boolean
     * @deprecated To be removed with Neos 9
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

        $routingArguments = $this->getRoutingArguments();
        $legacyRoutingArguments = $this->getAdditionalParams();
        if ($routingArguments && $legacyRoutingArguments) {
            throw new RuntimeException('Neos.Neos:NodeUri does not allow to combine the legacy options "arguments", "additionalParams" with "routingArguments"', 1665431866);
        }
        try {
            $uriString = $this->linkingService->createNodeUri(
                $this->runtime->getControllerContext(),
                $this->getNode(),
                $baseNode,
                $this->getFormat(),
                $this->isAbsolute(),
                $routingArguments ?: $legacyRoutingArguments,
                $this->getSection(),
                $this->getAddQueryString(),
                $this->getArgumentsToBeExcludedFromQueryString()
            );
            $queryParameters = $this->getQueryParameters();
            if (empty($queryParameters)) {
                return $uriString;
            }
            $uri = new Uri($uriString);
            parse_str($uri->getQuery(), $queryParametersFromRouting);
            $mergedQueryParameters = Arrays::arrayMergeRecursiveOverrule($queryParametersFromRouting, $queryParameters);
            return (string)$uri->withQuery(http_build_query($mergedQueryParameters, '', '&'));
        } catch (NeosException $exception) {
            // TODO: Revisit if we actually need to store a stack trace.
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }
    }
}
