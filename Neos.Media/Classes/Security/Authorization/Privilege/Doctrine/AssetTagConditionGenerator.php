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
 * Condition generator covering Asset <-> Tag relations (M:M relations are not supported by the Flow
 * PropertyConditionGenerator yet)
 */
class AssetTagConditionGenerator implements SqlGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $tagLabelOrIdentifier;

    /**
     * @param string $tagLabelOrIdentifier
     */
    public function __construct($tagLabelOrIdentifier)
    {
        $this->tagLabelOrIdentifier = $tagLabelOrIdentifier;
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
        $tagLabelOrIdentifier = $propertyConditionGenerator->getValueForOperand($this->tagLabelOrIdentifier);
        if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $tagLabelOrIdentifier) === 1) {
            $whereCondition = $targetTableAlias . '_t.persistence_object_identifier = ' . $this->entityManager->getConnection()->quote($tagLabelOrIdentifier);
        } else {
            $whereCondition = $targetTableAlias . '_t.label = ' . $this->entityManager->getConnection()->quote($tagLabelOrIdentifier);
        }

        return $targetTableAlias . '.persistence_object_identifier IN (
            SELECT ' . $targetTableAlias . '_a.persistence_object_identifier
            FROM neos_media_domain_model_asset AS ' . $targetTableAlias . '_a
            LEFT JOIN neos_media_domain_model_asset_tags_join ' . $targetTableAlias . '_atj ON ' . $targetTableAlias . '_a.persistence_object_identifier = ' . $targetTableAlias . '_atj.media_asset
            LEFT JOIN neos_media_domain_model_tag ' . $targetTableAlias . '_t ON ' . $targetTableAlias . '_t.persistence_object_identifier = ' . $targetTableAlias . '_atj.media_tag
            WHERE ' . $whereCondition . ')';
    }
}
