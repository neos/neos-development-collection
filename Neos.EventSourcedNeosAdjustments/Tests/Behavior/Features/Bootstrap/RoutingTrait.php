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

use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\DetectContentSubgraphMiddleware;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\NodeUriBuilder;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathProjector;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
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
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriInterface;

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
        $site->setSiteResourcesPackageKey('Neos.EventSourcedNeosAdjustments');
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

    /**
     * @When The documenturipath projection is up to date
     */
    public function theDocumenturipathProjectionIsUpToDate(): void
    {
        /* @var $eventStoreFactory EventStoreFactory */
        $eventStoreFactory = $this->getObjectManager()->get(EventStoreFactory::class);
        /** @var EventStore $eventStore */
        $eventStore = $eventStoreFactory->create('ContentRepository');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getObjectManager()->get(EntityManagerInterface::class);
        /** @var DocumentUriPathProjector $projector */
        $projector = $this->getObjectManager()->get(DocumentUriPathProjector::class);
        (new EventListenerInvoker($eventStore, $projector, $entityManager->getConnection()))->catchUp();
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
        /** @var FunctionalTestRequestHandler $activeRequestHandler */
        $activeRequestHandler = self::$bootstrap->getActiveRequestHandler();
        $activeRequestHandler->setHttpRequest($activeRequestHandler->getHttpRequest()->withUri($this->requestUrl));
    }

    /**
     * @Then the matched node should be :nodeAggregateIdentifier in content stream :contentStreamIdentifier and dimension :dimensionSpacePoint
     */
    public function theMatchedNodeShouldBeInContentStreamAndOriginDimension(string $nodeAggregateIdentifier, string $contentStreamIdentifier, string $dimensionSpacePoint): void
    {
        $nodeAddress = $this->match($this->requestUrl);
        Assert::assertTrue($nodeAddress->isInLiveWorkspace());
        Assert::assertSame($nodeAggregateIdentifier, (string)$nodeAddress->getNodeAggregateIdentifier());
        Assert::assertSame($contentStreamIdentifier, (string)$nodeAddress->getContentStreamIdentifier());
        Assert::assertTrue($nodeAddress->getDimensionSpacePoint()->equals(DimensionSpacePoint::fromJsonString($dimensionSpacePoint)), sprintf('Dimension space point "%s" did not match the expected "%s"', json_encode($nodeAddress->getDimensionSpacePoint()), $dimensionSpacePoint));
    }

    /**
     * @Then No node should match URL :url
     */
    public function noNodeShouldMatchUrl(string $url): void
    {
        $matchedNodeAddress = null;
        $exception = false;
        try {
            $matchedNodeAddress = $this->match(new Uri($url));
        } catch (NoMatchingRouteException $exception) {
            $exception = true;
        }
        Assert::assertTrue($exception, 'Expected an NoMatchingRouteException to be thrown but instead the following node address was matched: ' . $matchedNodeAddress ?? '- none -');
    }

    private function match(UriInterface $uri): NodeAddress
    {
        $router = $this->getObjectManager()->get(RouterInterface::class);
        $serverRequestFactory = $this->getObjectManager()->get(ServerRequestFactoryInterface::class);
        $httpRequest = $serverRequestFactory->createServerRequest('GET', $uri);

        $middleware = new DetectContentSubgraphMiddleware();
        $spyMiddleware = new SpyRequestHandler();
        $middleware->process($httpRequest, $spyMiddleware);
        $routeParameters = $spyMiddleware->getHandledRequest()->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        $routeContext = new RouteContext($httpRequest, $routeParameters);
        $routeValues = $router->route($routeContext);

        $nodeAddressFactory = $this->getObjectManager()->get(NodeAddressFactory::class);
        return $nodeAddressFactory->createFromUriString($routeValues['node']);
    }


    /**
     * @Then The node :nodeAggregateIdentifier in content stream :contentStreamIdentifier and dimension :dimensionSpacePoint should resolve to URL :url
     */
    public function theNodeShouldResolveToUrl(string $nodeAggregateIdentifier, string $contentStreamIdentifier, string $dimensionSpacePoint, string $url): void
    {
        $resolvedUrl = $this->resolveUrl($nodeAggregateIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
        Assert::assertSame($url, (string)$resolvedUrl);
    }


    /**
     * @Then The node :nodeAggregateIdentifier in content stream :contentStreamIdentifier and dimension :dimensionSpacePoint should not resolve to an URL
     */
    public function theNodeShouldNotResolve(string $nodeAggregateIdentifier, string $contentStreamIdentifier, string $dimensionSpacePoint): void
    {
        $resolvedUrl = null;
        $exception = false;
        try {
            $resolvedUrl = $this->resolveUrl($nodeAggregateIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
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
        $actualResult = $dbal->fetchAll('SELECT ' . $columns . ' FROM neos_neos_projection_document_uri ORDER BY nodeaggregateidentifierpath');
        $expectedResult = array_map(static function (array $row) {
            return array_map(static function (string $cell) {
                return json_decode($cell, true, 512, JSON_THROW_ON_ERROR);
            }, $row);
        }, $expectedRows->getHash());
        Assert::assertEquals($expectedResult, $actualResult);
    }

    private function resolveUrl(string $nodeAggregateIdentifier, string $contentStreamIdentifier, string $dimensionSpacePoint): UriInterface
    {
        if ($this->requestUrl === null) {
            $this->iAmOnUrl('/');
        }
        putenv('FLOW_REWRITEURLS=1');
        $nodeAddress = new NodeAddress(
            ContentStreamIdentifier::fromString($contentStreamIdentifier),
            OriginDimensionSpacePoint::fromJsonString($dimensionSpacePoint),
            NodeAggregateIdentifier::fromString($nodeAggregateIdentifier),
            WorkspaceName::forLive()
        );
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', $this->requestUrl);
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        return NodeUriBuilder::fromRequest($actionRequest)->uriFor($nodeAddress);
    }
}
