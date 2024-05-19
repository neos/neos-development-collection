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
use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteConfiguration;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\NodeUriSpecification;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjectionFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionMiddleware;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Routing related Behat steps. This trait is impure and resets the SiteRepository
 *
 * Requires the {@see \Neos\Flow\Core\Bootstrap::getActiveRequestHandler()} to be a {@see FunctionalTestRequestHandler}.
 * For this the {@see BrowserTrait} can be used.
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait RoutingTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @var Uri
     */
    private $requestUrl;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @Given A site exists for node name :nodeName
     * @Given A site exists for node name :nodeName and domain :domain
     */
    public function theSiteExists(string $nodeName, string $domain = null): void
    {
        $siteRepository = $this->getObject(SiteRepository::class);
        $persistenceManager = $this->getObject(PersistenceManagerInterface::class);

        $site = new Site($nodeName);
        $site->setSiteResourcesPackageKey('Neos.Neos');
        $site->setState(Site::STATE_ONLINE);
        $siteRepository->add($site);

        if ($domain !== null) {
            $domainUri = new Uri($domain);
            $domainModel = new Domain();
            $domainModel->setHostname($domainUri->getHost());
            $domainModel->setPort($domainUri->getPort());
            $domainModel->setScheme($domainUri->getScheme());
            $domainModel->setSite($site);
            /** @var DomainRepository $domainRepository */
            $domainRepository = $this->getObject(DomainRepository::class);
            $domainRepository->add($domainModel);
        }

        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    private $routingTraitSiteConfigurationPostLoadHook;

    /**
     * @Given the sites configuration is:
     */
    public function theSiteConfigurationIs(\Behat\Gherkin\Node\PyStringNode $configYaml): void
    {
        $entityManager = $this->getObject(EntityManagerInterface::class);
        // clean up old PostLoad Hook
        if ($this->routingTraitSiteConfigurationPostLoadHook !== null) {
            $entityManager->getEventManager()->removeEventListener('postLoad', $this->routingTraitSiteConfigurationPostLoadHook);
        }

        $config = Yaml::parse($configYaml->getRaw());
        $this->routingTraitSiteConfigurationPostLoadHook = new class($config) {
            public function __construct(
                private readonly array $config
            ) {
            }

            public function postLoad(LifecycleEventArgs $lifecycleEventArgs)
            {
                $object = $lifecycleEventArgs->getObject();
                if ($object instanceof Site) {
                    ObjectAccess::setProperty($object, 'sitesConfiguration', $this->config['Neos']['Neos']['sites'], true);
                }
            }
        };

        $entityManager->getEventManager()->addEventListener('postLoad', $this->routingTraitSiteConfigurationPostLoadHook);
    }

    /**
     * @Given an asset with id :assetIdentifier and file name :fileName exists with the content :content
     */
    public function anAssetExists(string $assetIdentifier, string $fileName, string $content): void
    {
        /** @var ResourceManager $resourceManager */
        $resourceManager = $this->getObject(ResourceManager::class);
        /** @var AssetRepository $assetRepository */
        $assetRepository = $this->getObject(AssetRepository::class);

        $resource = $resourceManager->importResourceFromContent($content, $fileName);
        $asset = new Asset($resource);
        ObjectAccess::setProperty($asset, 'Persistence_Object_Identifier', $assetIdentifier, true);
        $assetRepository->add($asset);

        /** @var PersistenceManagerInterface $persistenceManager */
        $persistenceManager = $this->getObject(PersistenceManagerInterface::class);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    /**
     * @When I am on URL :url
     */
    public function iAmOnUrl(string $url): void
    {
        $this->requestUrl = new Uri($url);
        if (empty($this->requestUrl->getHost())) {
            $this->requestUrl = $this->requestUrl->withScheme('http')->withHost('localhost');
        }
        $activeRequestHandler = self::$bootstrap->getActiveRequestHandler();
        assert($activeRequestHandler instanceof FunctionalTestRequestHandler, 'wrong request handler - given ' . get_class($activeRequestHandler) . ' -> You need to include BrowserTrait in the FeatureContext!');
        $activeRequestHandler->setHttpRequest($activeRequestHandler->getHttpRequest()->withUri($this->requestUrl));
    }

    /**
     * @Then the matched node should be :nodeAggregateId in content stream :contentStreamId and dimension :dimensionSpacePoint
     */
    public function theMatchedNodeShouldBeInContentStreamAndOriginDimension(string $nodeAggregateId, string $contentStreamId, string $dimensionSpacePoint): void
    {
        $matchedNodeAddress = $this->match($this->requestUrl);
        Assert::assertNotNull($matchedNodeAddress, 'Routing result does not have "node" key - this probably means that the FrontendNodeRoutePartHandler did not properly resolve the result.');
        Assert::assertTrue($matchedNodeAddress->workspaceName->isLive());
        Assert::assertSame($nodeAggregateId, $matchedNodeAddress->aggregateId->value);
        // todo useless check?
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(ContentStreamId::fromString($contentStreamId));
        Assert::assertSame($contentStreamId, $workspace?->currentContentStreamId->value);
        Assert::assertSame(
            DimensionSpacePoint::fromJsonString($dimensionSpacePoint),
            $matchedNodeAddress->dimensionSpacePoint,
            sprintf(
                'Dimension space point "%s" did not match the expected "%s"',
                $matchedNodeAddress->dimensionSpacePoint->toJson(),
                $dimensionSpacePoint
            )
        );
    }

    /**
     * @Then No node should match URL :url
     */
    public function noNodeShouldMatchUrl(string $url): void
    {
        $matchedNodeAddress = $this->match(new Uri($url));
        Assert::assertNull($matchedNodeAddress, 'Expected no node to be found, but instead the following node address was matched: ' . $matchedNodeAddress?->toUriString() ?? '- none -');
    }

    /**
     * @Then The URL :url should match the node :nodeAggregateId in content stream :contentStreamId and dimension :dimensionSpacePoint
     */
    public function theUrlShouldMatchTheNodeInContentStreamAndDimension(string $url, $nodeAggregateId, $contentStreamId, $dimensionSpacePoint): void
    {
        $matchedNodeAddress = $this->match(new Uri($url));

        Assert::assertNotNull($matchedNodeAddress, 'Expected node to be found, but instead nothing was found.');
        Assert::assertEquals(NodeAggregateId::fromString($nodeAggregateId), $matchedNodeAddress->aggregateId, 'Expected nodeAggregateId doesn\'t match.');

        // todo use workspace name instead here:
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(ContentStreamId::fromString($contentStreamId));
        Assert::assertEquals($workspace->workspaceName, $matchedNodeAddress->workspaceName, 'Expected workspace doesn\'t match.');
        Assert::assertTrue($matchedNodeAddress->dimensionSpacePoint->equals(DimensionSpacePoint::fromJsonString($dimensionSpacePoint)), 'Expected dimensionSpacePoint doesn\'t match.');
    }

    private $eventListenerRegistered = false;

    private function match(UriInterface $uri): ?NodeAddress
    {
        $router = $this->getObject(RouterInterface::class);
        $serverRequestFactory = $this->getObject(ServerRequestFactoryInterface::class);
        $httpRequest = $serverRequestFactory->createServerRequest('GET', $uri);
        $httpRequest = $this->addRoutingParameters($httpRequest);

        $routeParameters = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        $routeContext = new RouteContext($httpRequest, $routeParameters);
        try {
            $routeValues = $router->route($routeContext);
            if (!isset($routeValues['node'])) {
                return null;
            }
        } catch (NoMatchingRouteException) {
            return null;
        }

        return NodeAddress::fromUriString($routeValues['node']);
    }


    /**
     * @Then The node :nodeAggregateId in content stream :contentStreamId and dimension :dimensionSpacePoint should resolve to URL :url
     */
    public function theNodeShouldResolveToUrl(string $nodeAggregateId, string $contentStreamId, string $dimensionSpacePoint, string $url): void
    {
        $resolvedUrl = $this->resolveUrl($nodeAggregateId, $contentStreamId, $dimensionSpacePoint);
        Assert::assertSame($url, (string)$resolvedUrl);
    }


    /**
     * @Then The node :nodeAggregateId in content stream :contentStreamId and dimension :dimensionSpacePoint should not resolve to an URL
     */
    public function theNodeShouldNotResolve(string $nodeAggregateId, string $contentStreamId, string $dimensionSpacePoint): void
    {
        if (
            ($this->getObject(ConfigurationManager::class)
                ->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.mvc.routes')['Neos.Flow'] ?? false) !== false
        ) {
            Assert::fail('In this distribution the Flow routes are included into the global configuration and thus any route arguments will always resolve. Please set in Neos.Flow.mvc.routes "Neos.Flow": false.');
        }

        $resolvedUrl = null;
        $exception = false;
        try {
            $resolvedUrl = $this->resolveUrl($nodeAggregateId, $contentStreamId, $dimensionSpacePoint);
        } catch (NoMatchingRouteException $exception) {
            $exception = true;
        }
        Assert::assertTrue($exception, 'Expected an NoMatchingRouteException to be thrown but instead the following URL is resolved: ' . $resolvedUrl ?? '- none -');
    }

    /**
     * @Then I expect the documenturipath table to contain exactly:
     */
    public function tableContainsExactly(TableNode $expectedRows): void
    {
        /** @var Connection $dbal */
        $dbal = $this->getObject(EntityManagerInterface::class)->getConnection();
        $columns = implode(', ', array_keys($expectedRows->getHash()[0]));
        $tablePrefix = DocumentUriPathProjectionFactory::projectionTableNamePrefix(
            $this->currentContentRepository->id
        );
        $actualResult = $dbal->fetchAll('SELECT ' . $columns . ' FROM ' . $tablePrefix . '_uri ORDER BY nodeaggregateidpath');
        $expectedResult = array_map(static function (array $row) {
            return array_map(static function (string $cell) {
                return json_decode($cell, true, 512, JSON_THROW_ON_ERROR);
            }, $row);
        }, $expectedRows->getHash());
        Assert::assertEquals($expectedResult, $actualResult);
    }

    private function resolveUrl(string $nodeAggregateId, string $contentStreamId, string $dimensionSpacePoint): UriInterface
    {
        if ($this->requestUrl === null) {
            $this->iAmOnUrl('/');
        }
        $workspace = $this->currentContentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(ContentStreamId::fromString($contentStreamId));

        $nodeAddress = NodeAddress::create(
            $this->currentContentRepository->id,
            $workspace->workspaceName, // todo always live?
            DimensionSpacePoint::fromJsonString($dimensionSpacePoint),
            \str_starts_with($nodeAggregateId, '$')
                ? $this->rememberedNodeAggregateIds[\mb_substr($nodeAggregateId, 1)]
                : NodeAggregateId::fromString($nodeAggregateId)
        );
        $httpRequest = $this->getObject(ServerRequestFactoryInterface::class)->createServerRequest('GET', $this->requestUrl);
        $httpRequest = $this->addRoutingParameters($httpRequest);

        return $this->getObject(NodeUriBuilderFactory::class)
            ->forRequest($httpRequest)
            ->uriFor(NodeUriSpecification::create($nodeAddress));
    }

    private function addRoutingParameters(ServerRequestInterface $httpRequest): ServerRequestInterface
    {
        $spyMiddleware = new SpyRequestHandler();
        (new SiteDetectionMiddleware())->process($httpRequest, $spyMiddleware);
        return $spyMiddleware->getHandledRequest();
    }


    private RequestToDimensionSpacePointContext $dimensionResolverContext;

    /**
     * @When I invoke the Dimension Resolver from site configuration:
     */
    public function iInvokeTheDimensionResolverWithOptions(PyStringNode $rawSiteConfigurationYaml)
    {
        $rawSiteConfiguration = Yaml::parse($rawSiteConfigurationYaml->getRaw()) ?? [];
        $siteConfiguration = SiteConfiguration::fromArray($rawSiteConfiguration);

        $dimensionResolverFactory = $this->getObject($siteConfiguration->contentDimensionResolverFactoryClassName);
        assert($dimensionResolverFactory instanceof DimensionResolverFactoryInterface);
        $dimensionResolver = $dimensionResolverFactory->create($siteConfiguration->contentRepositoryId, $siteConfiguration);

        $siteNodeName = SiteNodeName::fromString("site-node");
        $siteDetectionResult = SiteDetectionResult::create($siteNodeName, $siteConfiguration->contentRepositoryId);
        $routeParameters = $siteDetectionResult->storeInRouteParameters(RouteParameters::createEmpty());

        $site = new Site($siteNodeName->value);

        $dimensionResolverContext = RequestToDimensionSpacePointContext::fromUriPathAndRouteParametersAndResolvedSite($this->requestUrl->getPath(), $routeParameters, $site);
        $dimensionResolverContext = $dimensionResolver->fromRequestToDimensionSpacePoint($dimensionResolverContext);
        $this->dimensionResolverContext = $dimensionResolverContext;
    }

    /**
     * @When I invoke the Dimension Resolver from site configuration and exceptions are caught:
     */
    public function iInvokeTheDimensionResolverWithOptionsAndExceptionsAreCaught(PyStringNode $resolverOptionsYaml)
    {
        try {
            $this->iInvokeTheDimensionResolverWithOptions($resolverOptionsYaml);
        } catch (\Exception $e) {
            $this->lastCommandException = $e;
        }
    }

    /**
     * @Then the resolved dimension should be :dimensionSpacePoint and the remaining URI Path should be :remainingUriPathString
     */
    public function theResolvedDimensionShouldBe($dimensionSpacePointString, $remainingUriPathString)
    {
        $expected = DimensionSpacePoint::fromJsonString($dimensionSpacePointString);
        $actual = $this->dimensionResolverContext->resolvedDimensionSpacePoint;
        Assert::assertTrue($expected->equals($actual), 'Resolved dimension does not match - actual: ' . $actual->toJson());

        Assert::assertEquals($remainingUriPathString, $this->dimensionResolverContext->remainingUriPath, 'Remaining URI path does not match');
    }
}
