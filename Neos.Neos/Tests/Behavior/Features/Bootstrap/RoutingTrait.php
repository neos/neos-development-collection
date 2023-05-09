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
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Neos\Domain\Model\SiteConfiguration;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Tests\Unit\Http\Fixtures\SpyRequestHandler;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection;
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
 * Routing related Behat steps
 */
trait RoutingTrait
{

    /**
     * @var Uri
     */
    private $requestUrl;

    /**
     * @return ObjectManagerInterface
     */
    abstract protected function getObjectManager();

    abstract protected function getContentRepositoryId(): ContentRepositoryId;

    /**
     * @Given A site exists for node name :nodeName
     * @Given A site exists for node name :nodeName and domain :domain
     */
    public function theSiteExists(string $nodeName, string $domain = null): void
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = $this->getObjectManager()->get(SiteRepository::class);
        /** @var PersistenceManagerInterface $persistenceManager */
        $persistenceManager = $this->getObjectManager()->get(PersistenceManagerInterface::class);

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
            $domainRepository = $this->getObjectManager()->get(DomainRepository::class);
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
        $entityManager = $this->getObjectManager()->get(EntityManagerInterface::class);
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
        $resourceManager = $this->getObjectManager()->get(ResourceManager::class);
        /** @var AssetRepository $assetRepository */
        $assetRepository = $this->getObjectManager()->get(AssetRepository::class);

        $resource = $resourceManager->importResourceFromContent($content, $fileName);
        $asset = new Asset($resource);
        ObjectAccess::setProperty($asset, 'Persistence_Object_Identifier', $assetIdentifier, true);
        $assetRepository->add($asset);

        /** @var PersistenceManagerInterface $persistenceManager */
        $persistenceManager = $this->getObjectManager()->get(PersistenceManagerInterface::class);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    abstract protected function getContentRepository(): ContentRepository;

    /**
     * @When The documenturipath projection is up to date
     */
    public function theDocumenturipathProjectionIsUpToDate(): void
    {
        $this->getContentRepository()->catchUpProjection(DocumentUriPathProjection::class);
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
        $nodeAddress = $this->match($this->requestUrl);
        Assert::assertNotNull($nodeAddress, 'Routing result does not have "node" key - this probably means that the FrontendNodeRoutePartHandler did not properly resolve the result.');
        Assert::assertTrue($nodeAddress->isInLiveWorkspace());
        Assert::assertSame($nodeAggregateId, $nodeAddress->nodeAggregateId->value);
        Assert::assertSame($contentStreamId, $nodeAddress->contentStreamId->value);
        Assert::assertSame(
            DimensionSpacePoint::fromJsonString($dimensionSpacePoint),
            $nodeAddress->dimensionSpacePoint,
            sprintf(
                'Dimension space point "%s" did not match the expected "%s"',
                $nodeAddress->dimensionSpacePoint->toJson(),
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
        Assert::assertNull($matchedNodeAddress, 'Expected no node to be found, but instead the following node address was matched: ' . $matchedNodeAddress ?? '- none -');
    }

    private $eventListenerRegistered = false;

    private function match(UriInterface $uri): ?NodeAddress
    {
        $router = $this->getObjectManager()->get(RouterInterface::class);
        $serverRequestFactory = $this->getObjectManager()->get(ServerRequestFactoryInterface::class);
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

        $nodeAddressFactory = NodeAddressFactory::create($this->getContentRepository());
        return $nodeAddressFactory->createFromUriString($routeValues['node']);
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
        $dbal = $this->getObjectManager()->get(EntityManagerInterface::class)->getConnection();
        $columns = implode(', ', array_keys($expectedRows->getHash()[0]));
        $tablePrefix = DocumentUriPathProjectionFactory::projectionTableNamePrefix(
            $this->getContentRepositoryId()
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
        $nodeAddress = new NodeAddress(
            ContentStreamId::fromString($contentStreamId),
            DimensionSpacePoint::fromJsonString($dimensionSpacePoint),
            NodeAggregateId::fromString($nodeAggregateId),
            WorkspaceName::forLive()
        );
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', $this->requestUrl);
        $httpRequest = $this->addRoutingParameters($httpRequest);
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        return NodeUriBuilder::fromRequest($actionRequest)->uriFor($nodeAddress);
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

        $dimensionResolverFactory = $this->getObjectManager()->get($siteConfiguration->contentDimensionResolverFactoryClassName);
        assert($dimensionResolverFactory instanceof DimensionResolverFactoryInterface);
        $dimensionResolver = $dimensionResolverFactory->create($siteConfiguration->contentRepositoryId, $siteConfiguration);

        $siteDetectionResult = SiteDetectionResult::create(SiteNodeName::fromString("site-node"), $siteConfiguration->contentRepositoryId);
        $routeParameters = $siteDetectionResult->storeInRouteParameters(RouteParameters::createEmpty());

        $dimensionResolverContext = RequestToDimensionSpacePointContext::fromUriPathAndRouteParameters($this->requestUrl->getPath(), $routeParameters);
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
