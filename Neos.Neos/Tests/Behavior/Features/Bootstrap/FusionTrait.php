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
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;
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

    private ?string $fusionCode = null;

    private ?\Throwable $lastRenderingException = null;

    /**
     * @BeforeScenario
     */
    public function setupFusionContext(): void
    {
        $this->fusionRequest = null;
        $this->fusionContext = [];
        $this->fusionCode = null;
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
     * @When I have the following Fusion setup:
     */
    public function iHaveTheFollowingFusionSetup(PyStringNode $fusionCode): void
    {
        $this->fusionCode = $fusionCode->getRaw();
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
        $requestHandler = new FunctionalTestRequestHandler(self::$bootstrap);
        $requestHandler->setHttpRequest($this->fusionRequest->getHttpRequest());
        self::$bootstrap->setActiveRequestHandler($requestHandler);
        $this->throwExceptionIfLastRenderingLedToAnError();
        $this->renderingResult = null;
        $fusionAst = (new Parser())->parseFromSource(FusionSourceCodeCollection::fromString($this->fusionCode . chr(10) . $fusionCode->getRaw())->union(
            // make sure all exceptions are thrown. Not needed with Neos 9
            FusionSourceCodeCollection::fromString(<<<FUSION
            $path {
                @exceptionHandler = 'Neos\\\\Fusion\\\\Core\\\\ExceptionHandlers\\\\ThrowingHandler'
            }
            FUSION)
        ));
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->fusionRequest);
        $controllerContext = new ControllerContext($this->fusionRequest, new ActionResponse(), new Arguments(), $uriBuilder);

        $fusionRuntime = (new RuntimeFactory())->createFromConfiguration($fusionAst, $controllerContext);
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

        $expectedDom = new \DomDocument();
        $expectedDom->preserveWhiteSpace = false;
        $expectedDom->loadHTML($stripWhitespace($expectedResult->getRaw()));

        $actualDom = new \DomDocument();
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
}
