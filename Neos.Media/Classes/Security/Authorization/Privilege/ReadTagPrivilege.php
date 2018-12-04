<?php
namespace Neos\Media\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Security\Authorization\Privilege\Doctrine\TagConditionGenerator;

/**
 * Privilege for restricting reading of Tags
 */
class ReadTagPrivilege extends EntityPrivilege
{
    /**
     * @param string $entityType
     * @return boolean
     */
    public function matchesEntityType($entityType)
    {
        return $entityType === Tag::class;
    }

    /**
     * @return TagConditionGenerator
     */
    protected function getConditionGenerator()
    {
        return new TagConditionGenerator();
    }
}
