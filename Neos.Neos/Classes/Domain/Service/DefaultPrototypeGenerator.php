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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * Generate a Fusion prototype definition based on Neos.Fusion:Template and pass all node properties to it
 *
 * @Flow\Scope("singleton")
 * @deprecated will be removed with Neos 6
 */
class DefaultPrototypeGenerator implements DefaultPrototypeGeneratorInterface
{
    /**
     * The Name of the prototype that is extended
     *
     * @var string
     */
    protected $basePrototypeName = null;

    /**
     * The node template path inside the package resources
     *
     * @var string
     */
    protected $templatePath = null;

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
    public function generate(NodeType $nodeType)
    {
        if (strpos($nodeType->getName(), ':') === false) {
            return '';
        }

        $output = 'prototype(' . $nodeType->getName() . ')';
        if ($this->basePrototypeName !== null) {
            $output .= ' < prototype(' . $this->basePrototypeName . ')';
        }
        $output .= ' {' . chr(10);

        if ($this->templatePath !== null) {
            list($packageKey, $relativeName) = explode(':', $nodeType->getName(), 2);
            $nodeTemplatePath = 'resource://' . $packageKey . '/' . $this->templatePath . '/' . $relativeName . '.html';
            $output .= "\t" . 'templatePath = \'' . $nodeTemplatePath . '\'' . chr(10);
        }

        foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
            if (isset($propertyName[0]) && $propertyName[0] !== '_') {
                $output .= "\t" . $propertyName . ' = ${q(node).property("' . $propertyName . '")}' . chr(10);
                if (isset($propertyConfiguration['type']) && isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['type'] === 'string' && $propertyConfiguration['ui']['inlineEditable'] === true) {
                    $output .= "\t" . $propertyName . '.@process.convertUris = Neos.Neos:ConvertUris' . chr(10);
                }
            }
        }

        $output .= '}' . chr(10);
        return $output;
    }
}
