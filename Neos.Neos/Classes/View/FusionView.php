<?php
namespace Neos\Neos\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\Domain\Context\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Domain\Context\Dimension\ContentDimensionValue;
use Neos\ContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Exception;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Flow\Security\Context;

/**
 * A Fusion view for Neos
 */
class FusionView extends AbstractView
{
    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array
     */
    protected $supportedOptions = array(
        'enableContentCache' => array(null, 'Flag to enable content caching inside Fusion (overriding the global setting).', 'boolean')
    );

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * The Fusion path to use for rendering the node given in "value", defaults to "page".
     *
     * @var string
     */
    protected $fusionPath = 'root';

    /**
     * @var Runtime
     */
    protected $fusionRuntime;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @throws \Exception if no node is given
     * @api
     */
    public function render()
    {
        $currentSite = $this->getCurrentSite();
        $fusionRuntime = $this->getFusionRuntime($currentSite);

        $this->initializeLanguage();

        $currentNode = $this->getCurrentNode();
        $fusionRuntime->pushContextArray([
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSite,
            'subgraph' => $this->getCurrentSubgraph(),
            'contextParameters' => $this->getCurrentContextParameters(),
            'editPreviewMode' => isset($this->variables['editPreviewMode']) ? $this->variables['editPreviewMode'] : null
        ]);
        try {
            $output = $fusionRuntime->render($this->fusionPath);
            $output = $this->mergeHttpResponseFromOutput($output, $fusionRuntime);
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious();
        }
        $fusionRuntime->popContext();

        return $output;
    }

    /**
     * @throws Exception
     */
    protected function initializeLanguage()
    {
        $languageIdentifier = new ContentDimensionIdentifier('language');
        if ($this->getCurrentSubgraph()->getDimensionSpacePoint()->hasCoordinate($languageIdentifier)) {
            $language = $this->contentDimensionSource->getDimension($languageIdentifier);
            $requestedLanguage = $language->getValue($this->getCurrentSubgraph()->getDimensionSpacePoint()->getCoordinates()['language']);
            $currentLocale = new Locale((string) $requestedLanguage);
            $fallbackOrder = [];
            $language->traverseGeneralizations($requestedLanguage, function (ContentDimensionValue $generalization) use (&$fallbackOrder) {
                $fallbackOrder[] = (string) $generalization;
            });
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            $this->i18nService->getConfiguration()->setFallbackRule(array('strict' => false, 'order' => array_reverse($fallbackOrder)));
        }
    }

    /**
     * @param string $output
     * @param Runtime $fusionRuntime
     * @return string The message body without the message head
     */
    protected function mergeHttpResponseFromOutput($output, Runtime $fusionRuntime)
    {
        if (substr($output, 0, 5) === 'HTTP/') {
            $endOfHeader = strpos($output, "\r\n\r\n");
            if ($endOfHeader !== false) {
                $header = substr($output, 0, $endOfHeader + 4);
                try {
                    $renderedResponse = Response::createFromRaw($header);

                    /** @var Response $response */
                    $response = $fusionRuntime->getControllerContext()->getResponse();
                    $response->setStatus($renderedResponse->getStatusCode());
                    foreach ($renderedResponse->getHeaders()->getAll() as $headerName => $headerValues) {
                        $response->setHeader($headerName, $headerValues);
                    }

                    $output = substr($output, strlen($header));
                } catch (\InvalidArgumentException $exception) {
                }
            }
        }

        return $output;
    }

    /**
     * Is it possible to render $node with $his->fusionPath?
     *
     * @return boolean TRUE if $node can be rendered at fusionPath
     *
     * @throws Exception
     */
    public function canRenderWithNodeAndPath()
    {
        $fusionRuntime = $this->getFusionRuntime($this->getCurrentSite());

        return $fusionRuntime->canRender($this->fusionPath);
    }

    /**
     * Set the Fusion path to use for rendering the node given in "value"
     *
     * @param string $fusionPath
     * @return void
     */
    public function setFusionPath($fusionPath)
    {
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
     * @param NodeInterface $node
     * @return NodeInterface
     */
    protected function getClosestDocumentNode(NodeInterface $node)
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }
        return $node;
    }

    /**
     * @return NodeInterface
     * @throws Exception
     */
    protected function getCurrentNode(): NodeInterface
    {
        $currentNode = isset($this->variables['value']) ? $this->variables['value'] : null;
        if (!$currentNode instanceof NodeInterface) {
            throw new Exception('FusionView needs a variable \'value\' set with a NodeInterface object.', 1329736456);
        }
        return $currentNode;
    }

    /**
     * @return ContentSubgraphInterface
     * @throws Exception
     */
    protected function getCurrentSubgraph(): ContentSubgraphInterface
    {
        $currentSubgraph = $this->variables['subgraph'] ?? null;
        if (!$currentSubgraph instanceof ContentSubgraphInterface) {
            throw new Exception('FusionView needs a variable \'subgraph\' set with a ContentSubgraphInterface object.', 1519167201);
        }
        return $currentSubgraph;
    }

    /**
     * @return NodeInterface
     * @throws Exception
     */
    protected function getCurrentSite(): NodeInterface
    {
        $currentSite = $this->variables['site'] ?? null;
        if (!$currentSite instanceof NodeInterface) {
            throw new Exception('FusionView needs a variable \'site\' set with a NodeInterface object.', 1329736456);
        }
        return $currentSite;
    }

    /**
     * @return ContextParameters
     * @throws Exception
     */
    protected function getCurrentContextParameters(): ContextParameters
    {
        $currentContextParameters = $this->variables['contextParameters'] ?? null;
        if (!$currentContextParameters instanceof ContextParameters) {
            throw new Exception('FusionView needs a variable \'contextParameters\' set with a ContextParameters object.', 1519167300);
        }
        return $currentContextParameters;
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function getFusionRuntime(NodeInterface $currentSiteNode)
    {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $this->controllerContext);

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }
        return $this->fusionRuntime;
    }

    /**
     * Clear the cached runtime instance on assignment of variables
     *
     * @param string $key
     * @param mixed $value
     * @return FusionView
     */
    public function assign($key, $value)
    {
        $this->fusionRuntime = null;
        return parent::assign($key, $value);
    }
}
