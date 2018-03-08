<?php
namespace Neos\ContentRepositoryMigration\Service;

/*
 * This file is part of the Neos.ContentRepositoryMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Domain\Context\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasAddedToAggregate;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\Neos\Domain\Context\Site\Event\SiteWasCreated;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\ValueObject\NodeType;
use Neos\Neos\Domain\ValueObject\PackageKey;
use Neos\Neos\Domain\ValueObject\SiteActive;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryExportService
{

    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @var DoctrineObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalFallbackGraph;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    protected $contentStreamIdentifier;


    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var NodeIdentifier
     */
    protected $sitesRootNodeIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifierForSitesNode;

    private $nodeIdentifiers;

    private $alreadyCreatedNodeAggregateIdentifiers;



    public function injectEntityManager(DoctrineObjectManager $entityManager)
    {
        if (!$entityManager instanceof DoctrineEntityManager) {
            throw new \RuntimeException('Invalid EntityManager configured');
        }
        $this->dbal = $entityManager->getConnection();
        $this->entityManager = $entityManager;
    }


    public function reset()
    {
        $this->dbal->executeQuery('
            SET foreign_key_checks = 0;
            
            TRUNCATE neos_eventsourcing_eventstore_events;
            
            TRUNCATE neos_contentgraph_hierarchyrelation;
            TRUNCATE neos_contentgraph_referencerelation;
            TRUNCATE neos_contentgraph_node;
            TRUNCATE neos_neos_projection_site_v1;
            TRUNCATE neos_neos_projection_domain_v1;
            TRUNCATE neos_contentrepository_projection_workspace_v1;
            
            SET foreign_key_checks = 1;');
    }

    public function migrate()
    {
        $this->nodeIdentifiers = [];
        $this->alreadyCreatedNodeAggregateIdentifiers = [];

        $this->contentStreamIdentifier = new ContentStreamIdentifier();
        $this->sitesRootNodeIdentifier = new NodeIdentifier();
        $this->nodeAggregateIdentifierForSitesNode = new NodeAggregateIdentifier();

        $this->eventPublisher->publish($this->contentStreamName(), new ContentStreamWasCreated(
            $this->contentStreamIdentifier,
            UserIdentifier::forSystemUser()
        ));

        $this->createWorkspaceAndRootNode();
        $this->migrateSites();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->andWhere('n.path NOT IN(\'/sites\', \'/\')')
            ->orderBy('n.parentPath', 'ASC')
            ->addOrderBy('n.index', 'ASC')
            ->setParameter('workspace', 'live')
            ->setParameter('removed', false, \PDO::PARAM_BOOL);

        $nodeDatas = $queryBuilder->getQuery()->getResult();
        $nodeDatasToExportAtNextIteration = [];
        foreach ($nodeDatas as $nodeData) {
            $this->exportNodeData($nodeData, null, $nodeDatasToExportAtNextIteration);
        }
        var_dump("NODE DATAS IN NEXT ITER: " . count($nodeDatasToExportAtNextIteration));


        $nodeDatas = $nodeDatasToExportAtNextIteration;
        $nodeDatasToExportAtNextIteration = [];
        // TODO: correct sorting with respect to iteration!!
        foreach ($nodeDatas as $nodeData) {
            $this->exportNodeData($nodeData, null, $nodeDatasToExportAtNextIteration);
        }
        var_dump("NODE DATAS IN NEXT ITER: " . count($nodeDatasToExportAtNextIteration));


    }

    protected function exportNodeData(NodeData $nodeData, DimensionSpacePoint $dimensionRestriction = null, &$nodeDatasToExportAtNextIteration)
    {

        $nodePath = new NodePath($nodeData->getPath());

        $dimensionSpacePoint = DimensionSpacePoint::fromLegacyDimensionArray($nodeData->getDimensionValues());
        if ($dimensionRestriction !== null && $dimensionSpacePoint->getHash() !== $dimensionRestriction->getHash()) {
            // unwanted dimension; so let's skip it!
            return;
        }

        $parentNodeIdentifier = $this->findParentNodeIdentifier($nodeData->getParentPath(), $dimensionSpacePoint);
        if (!$parentNodeIdentifier) {
            // if parent node identifier not found, TRY LATER
            $nodeDatasToExportAtNextIteration[] = $nodeData;
            return;
        }


        $nodeAggregateIdentifier = new NodeAggregateIdentifier($nodeData->getIdentifier());



        $excludedSet = $this->findOtherExistingDimensionSpacePointsForNodeData($nodeData);
        $nodeIdentifier = new NodeIdentifier($this->persistenceManager->getIdentifierByObject($nodeData));
        $this->exportNodeOrNodeAggregate(
            $nodeAggregateIdentifier,
            new NodeTypeName($nodeData->getNodeType()->getName()),
            $dimensionSpacePoint,
            $excludedSet,
            $nodeIdentifier,
            $parentNodeIdentifier,
            new NodeName($nodeData->getName()),
            $this->processPropertyValues($nodeData),
            $this->processPropertyReferences($nodeData),
            $nodePath // TODO: probably pass last path-part only?
        );
    }

    protected function exportNodeOrNodeAggregate(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $excludedSet,
        NodeIdentifier $nodeIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        NodeName $nodeName,
        array $propertyValues,
        array $propertyReferences,
        NodePath $nodePath
    )
    {
        $visibleDimensionSpacePoints = $this->interDimensionalFallbackGraph->getSpecializationSet($dimensionSpacePoint, true, $excludedSet);
        $this->recordNodeIdentifier($nodePath, $dimensionSpacePoint, $nodeIdentifier);

        if (isset($this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier])) {
            // a Node of this NodeAggregate already exists; we create a Node
            $this->eventPublisher->publish($this->contentStreamName('NodeAggregate:' . $nodeIdentifier), new NodeWasAddedToAggregate(
                $this->contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $nodeTypeName,
                $dimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $nodeIdentifier,
                $parentNodeIdentifier,
                $nodeName,
                $propertyValues
            ));
        } else {
            // first time a Node of this NodeAggregate is created
            $this->eventPublisher->publish($this->contentStreamName('NodeAggregate:' . $nodeIdentifier), new NodeAggregateWithNodeWasCreated(
                $this->contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $nodeTypeName,
                $dimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $nodeIdentifier,
                $parentNodeIdentifier,
                $nodeName,
                $propertyValues
            ));
        }

        // publish reference edges
        foreach ($propertyReferences as $propertyName => $references) {
            $this->eventPublisher->publish($this->contentStreamName('NodeAggregate:' . $nodeIdentifier), new NodeReferencesWereSet(
                $this->contentStreamIdentifier,
                $visibleDimensionSpacePoints,
                new NodeIdentifier($nodeIdentifier),
                new PropertyName($propertyName),
                $references
            ));

        }

        $this->alreadyCreatedNodeAggregateIdentifiers[(string)$nodeAggregateIdentifier] = true;
    }

    protected function contentStreamName($suffix = null)
    {
        return 'Neos.ContentRepository:ContentStream:' . $this->contentStreamIdentifier . ($suffix ? ':' . $suffix : '');
    }

    private function processPropertyValues(NodeData $nodeData)
    {
        $properties = [];
        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            $type = $nodeData->getNodeType()->getPropertyType($propertyName);

            if ($type == 'reference' || $type == 'references') {
                // TODO: support other types than string
                continue;
            }
            $this->encodeObjectReference($propertyValue);
            $properties[$propertyName] = new PropertyValue($propertyValue, $type);
        }

        return $properties;
    }

    protected function encodeObjectReference(&$value)
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $this->encodeObjectReference($item);
            }
        }

        if (!is_object($value)) {
            return;
        }

        $propertyClassName = TypeHandling::getTypeForValue($value);

        if ($value instanceof \DateTimeInterface) {
            $value = [
                'date' => $value->format('Y-m-d H:i:s.u'),
                'timezone' => $value->format('e'),
                'dateFormat' => 'Y-m-d H:i:s.u'
            ];
        } else {
            $value = [
                '__flow_object_type' => $propertyClassName,
                '__identifier' => $this->persistenceManager->getIdentifierByObject($value)
            ];
        }
    }

    private function processPropertyReferences(NodeData $nodeData)
    {
        $references = [];
        foreach ($nodeData->getProperties() as $propertyName => $propertyValue) {
            $type = $nodeData->getNodeType()->getPropertyType($propertyName);
            if ($type == 'reference') {
               $references[$propertyName] = [new NodeAggregateIdentifier($propertyValue)];
            }
            if ($type == 'references' && is_array($propertyValue)) {
               $references[$propertyName] = array_map(function($identifier) { return new NodeAggregateIdentifier($identifier); }, $propertyValue);
            }
        }
        return $references;
    }

    private function findParentNodeIdentifier($parentPath, DimensionSpacePoint $dimensionSpacePoint): ?NodeIdentifier
    {
        if ($parentPath === '/sites') {
            return $this->sitesRootNodeIdentifier;
        }

        while ($dimensionSpacePoint !== null) {
            if ($dimensionSpacePoint->getCoordinates()['language'] !== 'en_US') {
                \Neos\Flow\var_dump($dimensionSpacePoint->getCoordinates());
            }
            $key = $parentPath . '__' . $dimensionSpacePoint->getHash();
            if (isset($this->nodeIdentifiers[$key])) {
                return $this->nodeIdentifiers[$key];
            }

            $dimensionSpacePoint = $this->interDimensionalFallbackGraph->getPrimaryGeneralization($dimensionSpacePoint);
        }

        return null;
    }

    private function createSitesNodeForDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint)
    {
        $this->exportNodeOrNodeAggregate(
            $this->nodeAggregateIdentifierForSitesNode,
            new NodeTypeName('Neos.Neos:Sites'),
            $dimensionSpacePoint,
            new DimensionSpacePointSet([]), // TODO: I'd say it is OK to create too-many site nodes now; as it does not contain any properties
            new NodeIdentifier(),
            $this->sitesRootNodeIdentifier,
            new NodeName('sites'),
            [],
            new NodePath('/sites') // TODO: probably pass last path-part only?
        );
    }

    private function recordNodeIdentifier(NodePath $nodePath, DimensionSpacePoint $dimensionSpacePoint, NodeIdentifier $nodeIdentifier)
    {
        $key = $nodePath . '__' . $dimensionSpacePoint->getHash();
        if (isset($this->nodeIdentifiers[$key])) {
            throw new \RuntimeException('TODO: node identifier ' . $key . 'already known!!!');
        }
        $this->nodeIdentifiers[$key] = $nodeIdentifier;
    }

    private function findOtherExistingDimensionSpacePointsForNodeData($nodeData): DimensionSpacePointSet
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('n')
            ->from(NodeData::class, 'n')
            ->where('n.workspace = :workspace')
            ->andWhere('n.movedTo IS NULL OR n.removed = :removed')
            ->andWhere('n.identifier = :identifier')
            ->setParameter('workspace', 'live')
            ->setParameter('removed', false, \PDO::PARAM_BOOL)
            ->setParameter('identifier', $nodeData->getIdentifier());

        $points = [];
        foreach ($query->getQuery()->getResult() as $relatedNodeData) {
            if ($relatedNodeData === $nodeData) {
                // skip current element
                continue;
            }

            /** @var $relatedNodeData NodeData */
            $points[] = DimensionSpacePoint::fromLegacyDimensionArray($relatedNodeData->getDimensionValues());
        }

        return new DimensionSpacePointSet($points);
    }

    private function createWorkspaceAndRootNode()
    {
        $this->eventPublisher->publish('Neos.ContentRepository:Workspace:live', new RootWorkspaceWasCreated(
            new WorkspaceName('live'),
            new WorkspaceTitle('Live'),
            new WorkspaceDescription(''),
            UserIdentifier::forSystemUser(),
            $this->contentStreamIdentifier
        ));

        $this->eventPublisher->publish($this->contentStreamName(), new RootNodeWasCreated(
            $this->contentStreamIdentifier,
            $this->sitesRootNodeIdentifier,
            new NodeTypeName('Neos.Neos:Sites'),
            UserIdentifier::forSystemUser()
        ));

    }

    private function migrateSites()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $query = $queryBuilder->select('s')
            ->from(Site::class, 's');

        foreach ($query->getQuery()->getResult() as $site) {
            /* @var $site Site */
            // TODO: move Site Handling to Neos!!
            $this->eventPublisher->publish('Neos.Neos:Site:' . $site->getNodeName(), new SiteWasCreated(
                new \Neos\ContentRepository\Domain\ValueObject\NodeName($site->getNodeName()),
                new PackageKey($site->getSiteResourcesPackageKey()),
                new NodeType('Neos.Neos:Site'), // TODO
                new \Neos\ContentRepository\Domain\ValueObject\NodeName($site->getNodeName()),
                new SiteActive(true)
            ));
        }

    }
}
