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

use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator as EntityConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\PropertyConditionGenerator;
use Neos\Flow\Security\Exception\InvalidPrivilegeException;
use Neos\Media\Domain\Model\AssetCollection;

/**
 * A SQL condition generator, supporting special SQL constraints for asset collections
 */
class AssetCollectionConditionGenerator extends EntityConditionGenerator
{
    /**
     * @var string
     */
    protected $entityType = AssetCollection::class;

    /**
     * @param string $entityType
     * @return boolean
     * @throws InvalidPrivilegeException
     */
    public function isType($entityType)
    {
        throw new InvalidPrivilegeException('The isType() operator must not be used in AssetCollection privilege matchers!', 1445941247);
    }

    /**
     * @param string $collectionTitle
     * @return PropertyConditionGenerator
     */
    public function isTitled($collectionTitle)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('title');

        return $propertyConditionGenerator->equals($collectionTitle);
    }

    /**
     * @param string $collectionIdentifier
     * @return PropertyConditionGenerator
     */
    public function hasId($collectionIdentifier)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('Persistence_Object_Identifier');

        return $propertyConditionGenerator->equals($collectionIdentifier);
    }
}
