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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Generate a TypoScript prototype definition for a given node type
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DefaultPrototypeGenerator implements DefaultPrototypeGeneratorInterface
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

        if ($nodeType->isOfType('TYPO3.Neos:Content')) {
            $basePrototypeName = 'TYPO3.Neos:Content';
        } elseif ($nodeType->isOfType('TYPO3.Neos:Document')) {
            $basePrototypeName = 'TYPO3.Neos:Document';
        } else {
            $basePrototypeName = 'TYPO3.TypoScript:Template';
        }

        $output = 'prototype(' . $nodeType->getName() . ') < prototype(' . $basePrototypeName . ') {' . chr(10);

        list($packageKey, $relativeName) = explode(':', $nodeType->getName(), 2);
        $templatePath = 'resource://' . $packageKey . '/Private/Templates/NodeTypes/' . $relativeName . '.html';
        $output .= "\t" . 'templatePath = \'' . $templatePath . '\'' . chr(10);

        foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
            if (isset($propertyName[0]) && $propertyName[0] !== '_') {
                $output .= "\t" . $propertyName . ' = ${q(node).property("' . $propertyName . '")}' . chr(10);
                if (isset($propertyConfiguration['type']) && isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['type'] === 'string' && $propertyConfiguration['ui']['inlineEditable'] === true) {
                    $output .= "\t" . $propertyName . '.@process.convertUris = TYPO3.Neos:ConvertUris' . chr(10);
                }
            }
        }

        $output .= '}' . chr(10);
        return $output;
    }
}
