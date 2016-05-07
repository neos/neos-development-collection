<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TYPO3CR\Domain\Model\NodeType;

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
     * A node will be rendered by TYPO3.Neos:Content by default with a template in
     * resource://PACKAGE_KEY/Private/Templates/NodeTypes/NAME.html and forwards all public
     * node properties to the template TypoScript object.
     *
     * @param NodeType $nodeType
     * @return string
     */
    public function generate(NodeType $nodeType);
}
