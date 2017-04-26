<?php
namespace Neos\Neos\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A subject for the ModulePrivilege
 */
class ModulePrivilegeSubject implements PrivilegeSubjectInterface
{
    /**
     * @var string
     */
    private $modulePath;

    /**
     * @param string $modulePath
     */
    public function __construct($modulePath)
    {
        $this->modulePath = $modulePath;
    }

    /**
     * @return string
     */
    public function getModulePath()
    {
        return $this->modulePath;
    }

}
