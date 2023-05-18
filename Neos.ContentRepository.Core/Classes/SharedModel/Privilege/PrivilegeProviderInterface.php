<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Privilege;

use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal except for CR factory implementations
 */
interface PrivilegeProviderInterface
{
    public function getPrivileges(VisibilityConstraints $visibilityConstraints): Privileges;
}
