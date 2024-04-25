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

use Behat\Gherkin\Node\PyStringNode;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\ProjectedNodeTrait;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Fusion\Core\ExceptionHandlers\ThrowingHandler;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\RenderingModeService;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Neos\Fusion\Core\Cache\ContentCache;

/**
 * @internal only for behat tests within the Neos.Neos package
 */
trait FusionTrait
{
    use RoutingTrait;
    use ProjectedNodeTrait;
    use CRTestSuiteRuntimeVariables;

    private array $fusionGlobalContext = [];
    private array $fusionContext = [];

    private ?string $renderingResult = null;

    private ?string $fusionCode = null;

    private ?\Throwable $lastRenderingException = null;

    private $contentCacheEnabled = false;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    public function setupFusionContext(): void
    {
        $this->fusionGlobalContext = [];
        $this->fusionContext = [];
        $this->fusionCode = null;
        $this->contentCacheEnabled = false;
        $this->renderingResult = null;
    }

    /**
     * @When the Fusion renderingMode is :requestUri
     */
    public function iAmInFusionRenderingMode(string $renderingModeName): void
    {
        $renderingMode = $this->getObject(RenderingModeService::class)->findByName($renderingModeName);
        $this->fusionGlobalContext['renderingMode'] = $renderingMode;
    }

    /**
     * @When the Fusion context node is :nodeAggregateId
     */
    public function theFusionContextNodeIs(string $nodeAggregateId): void
    {
        $subgraph = $this->getCurrentSubgraph();
        $this->fusionContext['node'] = $subgraph->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
        if ($this->fusionContext['node'] === null) {
            throw new InvalidArgumentException(sprintf('Node with aggregate id "%s" could not be found in the current subgraph', $nodeAggregateId), 1696700222);
        }
        $this->fusionContext['documentNode'] = $subgraph->findClosestNode(NodeAggregateId::fromString($nodeAggregateId), FindClosestNodeFilter::create('Neos.Neos:Document'));
        if ($this->fusionContext['documentNode'] === null) {
            throw new \RuntimeException(sprintf('Failed to find closest document node for node with aggregate id "%s"', $nodeAggregateId), 1697790940);
        }
        $this->fusionContext['site'] = $subgraph->findClosestNode($this->fusionContext['documentNode']->nodeAggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        if ($this->fusionContext['site'] === null) {
            throw new \RuntimeException(sprintf('Failed to resolve site node for node with aggregate id "%s"', $nodeAggregateId), 1697790963);
        }
    }

    /**
     * @When the Fusion context request URI is :requestUri
     */
    public function theFusionContextRequestIs(string $requestUri = null): void
    {
        $httpRequest = $this->getObject(ServerRequestFactoryInterface::class)->createServerRequest('GET', $requestUri);
        $httpRequest = $this->addRoutingParameters($httpRequest);

        $this->fusionGlobalContext['request'] = ActionRequest::fromHttpRequest($httpRequest);
    }

    /**
     * @When I have the following Fusion setup:
     */
    public function iHaveTheFollowingFusionSetup(PyStringNode $fusionCode): void
    {
        $this->fusionCode = $fusionCode->getRaw();
    }

    /**
     * @When I have Fusion content cache enabled
     */
    public function iHaveFusionContentCacheEnabled(): void
    {
        $this->contentCacheEnabled = true;
    }

    /**
     * @When I execute the following Fusion code:
     * @When I execute the following Fusion code on path :path:
     */
    public function iExecuteTheFollowingFusionCode(PyStringNode $fusionCode, string $path = 'test'): void
    {
        if (isset($this->fusionGlobalContext['request'])) {
            $requestHandler = new FunctionalTestRequestHandler(self::$bootstrap);
            $requestHandler->setHttpRequest($this->fusionGlobalContext['request']->getHttpRequest());
        }
        $this->throwExceptionIfLastRenderingLedToAnError();
        $this->renderingResult = null;
        $fusionAst = (new Parser())->parseFromSource(FusionSourceCodeCollection::fromString($this->fusionCode . chr(10) . $fusionCode->getRaw()));

        $fusionGlobals = FusionGlobals::fromArray($this->fusionGlobalContext);

        $fusionRuntime = (new RuntimeFactory())->createFromConfiguration($fusionAst, $fusionGlobals);
        $fusionRuntime->setEnableContentCache($this->contentCacheEnabled);
        $fusionRuntime->overrideExceptionHandler($this->getObject(ThrowingHandler::class));
        $fusionRuntime->pushContextArray($this->fusionContext);
        try {
            $this->renderingResult = $fusionRuntime->render($path);
        } catch (\Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                $this->lastRenderingException = $exception->getPrevious();
            } else {
                $this->lastRenderingException = $exception;
            }
        }
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
        Assert::assertIsString($this->renderingResult, 'Previous Fusion rendering did not produce a string');
        $stripWhitespace = static fn (string $input): string => preg_replace(['/>[^\S ]+/s', '/[^\S ]+</s', '/(\s)+/s', '/> </s'], ['>', '<', '\\1', '><'], $input);

        $expectedDom = new DomDocument();
        $expectedDom->preserveWhiteSpace = false;
        $expectedDom->loadHTML($stripWhitespace($expectedResult->getRaw()));

        $actualDom = new DomDocument();
        $actualDom->preserveWhiteSpace = false;
        $actualDom->loadHTML($stripWhitespace($this->renderingResult));

        Assert::assertSame($expectedDom->saveHTML(), $actualDom->saveHTML());
    }

    /**
     * @Then I expect the following Fusion rendering error:
     */
    public function iExpectTheFollowingFusionRenderingError(PyStringNode $expectedError): void
    {
        Assert::assertNotNull($this->lastRenderingException, 'The previous rendering did not lead to an error');
        Assert::assertSame($expectedError->getRaw(), $this->lastRenderingException->getMessage());
        $this->lastRenderingException = null;
    }

    /**
     * @AfterScenario
     */
    public function throwExceptionIfLastRenderingLedToAnError(): void
    {
        if ($this->lastRenderingException !== null) {
            throw new \RuntimeException(sprintf('The last rendering led to an error: %s', $this->lastRenderingException->getMessage()), 1698319254, $this->lastRenderingException);
        }
    }

    /**
     * @BeforeScenario
     */
    public function clearFusionCaches()
    {
        $this->getObject(ContentCache::class)->flush();
    }

}
