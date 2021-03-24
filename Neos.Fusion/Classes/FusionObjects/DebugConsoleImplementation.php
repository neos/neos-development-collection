<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A Fusion object for debugging fusion-values via the browser console
 *
 * //fusionPath value The variable to serialize and output to the console.
 * //fusionPath title Optional custom title for the debug output.
 * //fusionPath method Optional alternative method to call on the browser console.
 * //fusionPath content When used as process the console script will be appended to it.
 * @api
 */
class DebugConsoleImplementation extends DataStructureImplementation
{
    protected $ignoreProperties = ['__meta', 'title', 'method', 'value', 'content'];

    public function getTitle(): string
    {
        return $this->fusionValue('title') ?: '';
    }

    public function getMethod(): string
    {
        return $this->fusionValue('method') ?: 'log';
    }

    public function getContent(): string
    {
        return $this->fusionValue('content') ?: '';
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->fusionValue('value') ?: '';
    }

    /**
     * Appends a console script call to the output
     */
    public function evaluate(): string
    {
        $title = trim($this->getTitle());
        $method = $this->getMethod();
        $content = $this->getContent();

        $arguments = parent::evaluate();
        array_unshift($arguments, $this->getValue());

        if ($title) {
            $arguments[] = $this->getTitle();
        }

        $arguments = array_map('json_encode', $arguments);

        return sprintf('%s<script>console.%s(%s)</script>', $content, $method, implode(', ', $arguments));
    }
}
