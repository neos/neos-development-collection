<?php
namespace Neos\Media\Security\Authorization\Privilege\Doctrine;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\PropertyConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\SqlGeneratorInterface;
use Neos\Flow\Validation\Validator\UuidValidator;

/**
 * Condition generator covering Asset <-> AssetCollection relations (M:M relations are not supported by the Flow
 * PropertyConditionGenerator yet)
 */
class AssetAssetCollectionConditionGenerator implements SqlGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $collectionTitleOrIdentifier;

    /**
     * @param string $collectionTitleOrIdentifier
     */
    public function __construct($collectionTitleOrIdentifier)
    {
        $this->collectionTitleOrIdentifier = $collectionTitleOrIdentifier;
    }

    /**
     * @param DoctrineSqlFilter $sqlFilter
     * @param ClassMetadata $targetEntity Metadata object for the target entity to create the constraint for
     * @param string $targetTableAlias The target table alias used in the current query
     * @return string
     */
    public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('');
        $collectionTitleOrIdentifier = $propertyConditionGenerator->getValueForOperand($this->collectionTitleOrIdentifier);
        if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $collectionTitleOrIdentifier) === 1) {
            $whereCondition = $targetTableAlias . '_ac.persistence_object_identifier = ' . $this->entityManager->getConnection()->quote($collectionTitleOrIdentifier);
        } else {
            $whereCondition = $targetTableAlias . '_ac.title = ' . $this->entityManager->getConnection()->quote($collectionTitleOrIdentifier);
        }

        return $targetTableAlias . '.persistence_object_identifier IN (
            SELECT ' . $targetTableAlias . '_a.persistence_object_identifier
            FROM neos_media_domain_model_asset AS ' . $targetTableAlias . '_a
            LEFT JOIN neos_media_domain_model_assetcollection_assets_join ' . $targetTableAlias . '_acj ON ' . $targetTableAlias . '_a.persistence_object_identifier = ' . $targetTableAlias . '_acj.media_asset
            LEFT JOIN neos_media_domain_model_assetcollection ' . $targetTableAlias . '_ac ON ' . $targetTableAlias . '_ac.persistence_object_identifier = ' . $targetTableAlias . '_acj.media_assetcollection
            WHERE ' . $whereCondition . ')';
    }
}
