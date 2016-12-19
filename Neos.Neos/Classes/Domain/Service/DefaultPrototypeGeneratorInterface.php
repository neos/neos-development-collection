<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * Generate a TypoScript prototype definition for a given node type
 *
 * @api
 */
interface DefaultPrototypeGeneratorInterface
{
    /**
     * Generate a TypoScript prototype definition for a given node type
     *
     * A node will be rendered by Neos.Neos:Content by default with a template in
     * resource://PACKAGE_KEY/Private/Templates/NodeTypes/NAME.html and forwards all public
     * node properties to the template TypoScript object.
     *
     * @param NodeType $nodeType
     * @return string
     */
    public function generate(NodeType $nodeType);
}
