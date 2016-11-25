<?php
namespace Neos\Fusion\TypoScriptObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Renderer TypoScript Object
 *
 * The Renderer object will evaluate to a result using either ``renderer``,
 * ``renderPath`` or ``type`` from the configuration.
 */
class RendererImplementation extends AbstractTypoScriptObject
{

    /**
     * The type to render if condition is TRUE
     *
     * @return string
     */
    public function getType()
    {
        return $this->tsValue('type');
    }

    /**
     * A path to a TypoScript configuration
     *
     * @return string
     */
    public function getRenderPath()
    {
        return $this->tsValue('renderPath');
    }

    /**
     * Render $type and return it.
     *
     * @return mixed
     */
    public function evaluate()
    {
        $rendererPath = sprintf('%s/renderer', $this->path);
        $canRenderWithRenderer = $this->tsRuntime->canRender($rendererPath);
        $renderPath = $this->getRenderPath();

        if ($canRenderWithRenderer) {
            $renderedElement = $this->tsRuntime->evaluate($rendererPath, $this);
        } elseif ($renderPath !== null) {
            if (substr($renderPath, 0, 1) === '/') {
                $renderedElement = $this->tsRuntime->render(substr($renderPath, 1));
            } else {
                $renderedElement = $this->tsRuntime->render($this->path . '/' . str_replace('.', '/', $renderPath));
            }
        } else {
            $renderedElement = $this->tsRuntime->render(
                sprintf('%s/element<%s>', $this->path, $this->getType())
            );
        }
        return $renderedElement;
    }
}
