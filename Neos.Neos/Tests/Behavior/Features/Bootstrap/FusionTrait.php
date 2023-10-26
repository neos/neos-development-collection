<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Neos\Tests\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Routing\RequestUriHostMiddleware;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal only for behat tests within the Neos.Neos package
 */
trait FusionTrait
{
    use NodeOperationsTrait;

    private ?ActionRequest $fusionRequest = null;
    private array $fusionContext = [];

    private ?string $renderingResult = null;

    /**
     * @BeforeScenario
     */
    public function setupFusionContext(): void
    {
        $this->fusionRequest = null;
        $this->fusionContext = [];
        $this->renderingResult = null;
    }

    /**
     * @When the Fusion context node is :nodeIdentifier
     */
    public function theFusionContextNodeIs(string $nodeIdentifier): void
    {
        /** @var ContentContext $context */
        $context = $this->getContextForProperties([]);
        $this->fusionContext['node'] = $context->getNodeByIdentifier($nodeIdentifier);
        if ($this->fusionContext['node'] === null) {
            throw new \InvalidArgumentException(sprintf('Node with identifier "%s" could not be found in the "%s" workspace', $nodeIdentifier, $context->getWorkspaceName()), 1696700222);
        }
        $flowQuery = new FlowQuery([$this->fusionContext['node']]);
        $this->fusionContext['documentNode'] = $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
        if ($this->fusionContext['documentNode'] === null) {
            throw new \RuntimeException(sprintf('Failed to find closest document node for node with identifier "%s"', $nodeIdentifier), 1697790940);
        }
        $this->fusionContext['site'] = $context->getCurrentSiteNode();
        if ($this->fusionContext['site'] === null) {
            throw new \RuntimeException(sprintf('Failed to resolve site node for node with identifier "%s"', $nodeIdentifier), 1697790963);
        }
    }

    /**
     * @When the Fusion context request URI is :requestUri
     */
    public function theFusionContextRequestIs(string $requestUri = null): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', $requestUri);
        $httpRequest = $this->addRoutingParameters($httpRequest);

        $this->fusionRequest = ActionRequest::fromHttpRequest($httpRequest);
    }

    private function addRoutingParameters(ServerRequestInterface $httpRequest): ServerRequestInterface
    {
        $spyMiddleware = new SpyRequestHandler();
        (new RequestUriHostMiddleware())->process($httpRequest, $spyMiddleware);
        return $spyMiddleware->getHandledRequest();
    }


    /**
     * @When I execute the following Fusion code:
     * @When I execute the following Fusion code on path :path:
     */
    public function iExecuteTheFollowingFusionCode(PyStringNode $fusionCode, string $path = 'test'): void
    {
        if ($this->fusionRequest === null) {
            $this->theFusionContextRequestIs('http://localhost');
        }
        $fusionAst = (new Parser())->parseFromSource(FusionSourceCodeCollection::fromString($fusionCode->getRaw()));
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->fusionRequest);
        $controllerContext = new ControllerContext($this->fusionRequest, new ActionResponse(), new Arguments(), $uriBuilder);

        $fusionRuntime = (new RuntimeFactory())->createFromConfiguration($fusionAst, $controllerContext);
        $fusionRuntime->pushContextArray($this->fusionContext);
        $this->renderingResult = $fusionRuntime->render($path);
        $fusionRuntime->popContext();
    }

    /**
     * @Then I expect the following Fusion rendering result:
     */
    public function iExpectTheFollowingFusionRenderingResult(PyStringNode $expectedResult): void
    {
        Assert::assertSame($expectedResult->getRaw(), $this->renderingResult);
    }

    /**
     * @Then I expect the following Fusion rendering result as HTML:
     */
    public function iExpectTheFollowingFusionRenderingResultAsHtml(PyStringNode $expectedResult): void
    {
        Assert::assertXmlStringEqualsXmlString($expectedResult->getRaw(), $this->renderingResult);
    }
}
