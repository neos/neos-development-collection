<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\SharedModel\NodeType\NodeType;

/**
 * Generate a Fusion prototype definition for a given node type
 *
 * @api
 */
interface DefaultPrototypeGeneratorInterface
{
    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * A node will be rendered by Neos.Neos:Content by default with a template in
     * resource://PACKAGE_KEY/Private/Templates/NodeTypes/NAME.html and forwards all public
     * node properties to the template Fusion object.
     *
     * @param NodeType $nodeType
     * @return string
     */
    public function generate(NodeType $nodeType);
}
