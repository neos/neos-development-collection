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
use Neos\Media\Domain\Model\Tag;

/**
 * A SQL condition generator, supporting special SQL constraints for tags
 */
class TagConditionGenerator extends EntityConditionGenerator
{
    /**
     * @var string
     */
    protected $entityType = Tag::class;

    /**
     * @param string $entityType
     * @return boolean
     * @throws InvalidPrivilegeException
     */
    public function isType($entityType)
    {
        throw new InvalidPrivilegeException('The isType() operator must not be used in Tag privilege matchers!', 1417083500);
    }

    /**
     * @param string $tagLabel
     * @return PropertyConditionGenerator
     */
    public function isLabeled($tagLabel)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('label');

        return $propertyConditionGenerator->equals($tagLabel);
    }

    /**
     * @param string $tagIdentifier
     * @return PropertyConditionGenerator
     */
    public function hasId($tagIdentifier)
    {
        $propertyConditionGenerator = new PropertyConditionGenerator('Persistence_Object_Identifier');

        return $propertyConditionGenerator->equals($tagIdentifier);
    }
}
