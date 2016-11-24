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

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Generate a TypoScript prototype definition based on TYPO3.TypoScript:Template and pass all node properties to it
 *
 * @Flow\Scope("singleton")
 */
class DefaultPluginPrototypeGenerator implements DefaultPrototypeGeneratorInterface
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
    public function generate(NodeType $nodeType)
    {
        if (strpos($nodeType->getName(), ':') === false) {
            return '';
        }

        $output = 'prototype(' . $nodeType->getName() . ') < prototype(TYPO3.Neos:Plugin) {' . chr(10);
        list($packageKey, $relativeName) = explode(':', $nodeType->getName(), 2);
        $output .= "\t" . 'package = "' . $packageKey . '"' . chr(10);
        $output .= "\t" . 'subpackage = ""' . chr(10);
        $output .= "\t" . 'controller = "Standard"' . chr(10);
        $output .= "\t" . 'action = "index"' . chr(10);
        $output .= '}' . chr(10);
        return $output;
    }
}
