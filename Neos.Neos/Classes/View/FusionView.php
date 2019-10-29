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

use function GuzzleHttp\Psr7\parse_response;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Exception;
use Neos\ContentRepository\Domain\Model\NodeInterface as LegacyNodeInterface;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Flow\Security\Context;
use Psr\Http\Message\ResponseInterface;

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
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array
     */
    protected $supportedOptions = [
        'enableContentCache' => [null, 'Flag to enable content caching inside Fusion (overriding the global setting).', 'boolean']
    ];

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
     * @return string|ResponseInterface The rendered view
     * @throws \Exception if no node is given
     * @api
     */
    public function render()
    {
        $currentNode = $this->getCurrentNode();
        $currentSiteNode = $this->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $dimensions = $currentNode->getContext()->getDimensions();
        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            $currentLocale = new Locale($dimensions['language'][0]);
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            array_shift($dimensions['language']);
            $this->i18nService->getConfiguration()->setFallbackRule(['strict' => true, 'order' => $dimensions['language']]);
        }

        $fusionRuntime->pushContextArray([
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode,
            'editPreviewMode' => isset($this->variables['editPreviewMode']) ? $this->variables['editPreviewMode'] : null
        ]);
        try {
            $output = $fusionRuntime->render($this->fusionPath);
            $output = $this->parsePotentialRawHttpResponse($output);
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious();
        }
        $fusionRuntime->popContext();

        return $output;
    }

    /**
     * @param string $output
     * @return string|ResponseInterface If output is a string with a HTTP preamble a ResponseInterface otherwise the original output.
     */
    protected function parsePotentialRawHttpResponse($output)
    {
        if ($this->isRawHttpResponse($output)) {
            return parse_response($output);
        }

        return $output;
    }

    /**
     * Checks if the mixed input looks like a raw HTTTP response.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isRawHttpResponse($value): bool
    {
        if (is_string($value) && strpos($value, 'HTTP/') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Is it possible to render $node with $his->fusionPath?
     *
     * @return boolean true if $node can be rendered at fusionPath
     *
     * @throws Exception
     */
    public function canRenderWithNodeAndPath()
    {
        $currentSiteNode = $this->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

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
     * @param TraversableNodeInterface $node
     * @return TraversableNodeInterface
     */
    protected function getClosestDocumentNode(TraversableNodeInterface $node)
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->findParentNode();
        }
        return $node;
    }

    /**
     * @return TraversableNodeInterface
     * @throws Exception
     */
    protected function getCurrentSiteNode(): TraversableNodeInterface
    {
        $currentNode = isset($this->variables['site']) ? $this->variables['site'] : null;
        if ($currentNode === null && $this->getCurrentNode() instanceof LegacyNodeInterface) {
            // fallback to Legacy node API
            /* @var $node LegacyNodeInterface */
            $node = $this->getCurrentNode();
            return $node->getContext()->getCurrentSiteNode();
        }
        if (!$currentNode instanceof TraversableNodeInterface) {
            throw new Exception('FusionView needs a variable \'site\' set with a Node object.', 1538996432);
        }
        return $currentNode;
    }

    /**
     * @return TraversableNodeInterface
     * @throws Exception
     */
    protected function getCurrentNode(): TraversableNodeInterface
    {
        $currentNode = isset($this->variables['value']) ? $this->variables['value'] : null;
        if (!$currentNode instanceof TraversableNodeInterface) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1329736456);
        }
        return $currentNode;
    }


    /**
     * @param TraversableNodeInterface $currentSiteNode
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function getFusionRuntime(TraversableNodeInterface $currentSiteNode)
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
