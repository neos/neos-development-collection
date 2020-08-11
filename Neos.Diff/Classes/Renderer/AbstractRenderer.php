<?php
namespace Neos\Diff\Renderer;

/**
 * This file is part of the Neos.Diff package.
 *
 * (c) 2009 Chris Boulton <chris.boulton@interspire.com>
 * Portions (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Abstract Diff Renderer
 */
abstract class AbstractRenderer
{
    /**
     * @var object Instance of the diff class that this renderer is generating the rendered diff for.
     */
    public $diff;

    /**
     * @var array Array of the default options that apply to this renderer.
     */
    protected $defaultOptions = [];

    /**
     * @var array Array containing the user applied and merged default options for the renderer.
     */
    protected $options = [];

    /**
     * The constructor. Instantiates the rendering engine and if options are passed,
     * sets the options for the renderer.
     *
     * @param array $options Optionally, an array of the options for the renderer.
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Set the options of the renderer to those supplied in the passed in array.
     * Options are merged with the default to ensure that there aren't any missing
     * options.
     *
     * @param array $options Array of options to set.
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Render the diff.
     *
     * @return string The diff
     */
    abstract public function render();
}
