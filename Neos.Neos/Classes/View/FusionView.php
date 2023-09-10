<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View;

use GuzzleHttp\Psr7\Message;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Security\Context;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Domain\Model\FusionRenderingStuff;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Domain\Service\RenderingUtility;
use Neos\Neos\Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * A Fusion view for Neos
 */
class FusionView extends AbstractView
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array<string,array<int,mixed>>
     */
    protected $supportedOptions = [
        'enableContentCache' => [
            null,
            'Flag to enable content caching inside Fusion (overriding the global setting).',
            'boolean'
        ]
    ];

    #[Flow\Inject]
    protected RuntimeFactory $runtimeFactory;

    #[Flow\Inject]
    protected RenderingUtility $renderingUtility;

    #[Flow\Inject]
    protected FusionService $fusionService;

    #[Flow\Inject]
    protected Context $securityContext;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected Runtime $fusionRuntime;

    /** The Fusion path to use for rendering the node given in "value", defaults to "root". */
    protected string $fusionPath = 'root';

    protected FusionRenderingStuff $fusionRenderingStuff;

    /** Set the Fusion path to use for rendering the node given in "value" */
    public function setFusionPath(string $fusionPath): void
    {
        $this->fusionPath = $fusionPath;
    }

    public function getFusionPath(): string
    {
        return $this->fusionPath;
    }

    /**
     * Assigns the Node to this view. The key must be "value".
     * The documentNode and siteNode as well the site are derived from this entry Node.
     *
     * @throws \Exception If 'value' is not a Node
     */
    public function assign($key, $value): AbstractView
    {
        if ($key !== 'value') {
            // noop -> we only allow "value"
            return $this;
        }

        // 'value' is expected to be a Node!
        if (!$value instanceof Node) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1329736456);
        }

        // clear the cached runtime instance
        unset($this->fusionRuntime);

        $this->fusionRenderingStuff = $this->renderingUtility->createFusionRenderingStuff(
            $value,
            $this->controllerContext->getRequest()
        );
        return $this;
    }

    /**
     * Renders the view
     *
     * @return string|ResponseInterface The rendered view
     * @throws \Exception If no node is given
     * @api
     */
    public function render(): string|ResponseInterface
    {
        $this->initializeFusionRuntime();

        $this->fusionRuntime->pushContextArray(
            $this->fusionRenderingStuff->fusionContext->toContextArray()
        );

        try {
            $output = $this->fusionRuntime->render($this->fusionPath);

            /**
             * parse potential raw http response possibly rendered via "Neos.Fusion:Http.Message"
             * {@see \Neos\Fusion\FusionObjects\HttpResponseImplementation}
             */
            $outputStringHasHttpPreamble = is_string($output) && str_starts_with($output, 'HTTP/');
            if ($outputStringHasHttpPreamble) {
                return Message::parseResponse($output);
            }

            return $output;
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious() ?: $exception;
        } finally {
            $this->fusionRuntime->popContext();
        }
    }

    /** Is it possible to render $this->fusionPath? */
    public function canRenderWithNodeAndPath(): bool
    {
        if (!isset($this->fusionRenderingStuff)) {
            return false;
        }
        $this->initializeFusionRuntime();
        return $this->fusionRuntime->canRender($this->fusionPath);
    }

    protected function initializeFusionRuntime(): void
    {
        if (isset($this->fusionRuntime)) {
            return;
        }

        if (!isset($this->fusionRenderingStuff)) {
            throw new Exception('FusionView needs a variable \'value\' set with a Node object.', 1694347478247);
        }

        $fusionConfiguration = $this->fusionService->createFusionConfigurationFromSite(
            $this->fusionRenderingStuff->site
        );

        $this->fusionRuntime = $this->runtimeFactory->createFromConfiguration(
            $fusionConfiguration,
            $this->fusionRenderingStuff->fusionGlobals
        );
        $this->fusionRuntime->setControllerContext($this->controllerContext);

        if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
            $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
        }
    }
}
