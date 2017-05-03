<?php
namespace Neos\ContentRepository\Domain\Utility;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;

/**
 * The subgraph utility library
 */
class SubgraphUtility
{
    /**
     * @param array $identifierComponents
     * @return string
     */
    public static function hashIdentityComponents(array $identifierComponents)
    {
        Arrays::sortKeysRecursively($identifierComponents);

        return md5(json_encode($identifierComponents));
    }
}
