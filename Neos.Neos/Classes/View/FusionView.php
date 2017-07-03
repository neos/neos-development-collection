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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Exception;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
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
        $currentNode = $this->getCurrentNode();
        $currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $dimensions = $currentNode->getContext()->getDimensions();
        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== array()) {
            $currentLocale = new Locale($dimensions['language'][0]);
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            $this->i18nService->getConfiguration()->setFallbackRule(array('strict' => false, 'order' => array_reverse($dimensions['language'])));
        }

        $fusionRuntime->pushContextArray(array(
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode,
            'editPreviewMode' => isset($this->variables['editPreviewMode']) ? $this->variables['editPreviewMode'] : null
        ));
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
                        $response->setHeader($headerName, $headerValues[0]);
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
        $currentNode = $this->getCurrentNode();
        $currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();
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
    protected function getCurrentNode()
    {
        $currentNode = isset($this->variables['value']) ? $this->variables['value'] : null;
        if (!$currentNode instanceof Node) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1329736456);
        }
        return $currentNode;
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
