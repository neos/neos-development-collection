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
 * Renderer Fusion Object
 *
 * The Renderer object will evaluate to a result using either ``renderer``,
 * ``renderPath`` or ``type`` from the configuration.
 */
class RendererImplementation extends AbstractFusionObject
{

    /**
     * The type to render if condition is true
     *
     * @return string
     */
    public function getType()
    {
        return $this->fusionValue('type');
    }

    /**
     * A path to a Fusion configuration
     *
     * @return string
     */
    public function getRenderPath()
    {
        return $this->fusionValue('renderPath');
    }

    /**
     * Render $type and return it.
     *
     * @return mixed
     */
    public function evaluate()
    {
        $rendererPath = sprintf('%s/renderer', $this->path);
        $canRenderWithRenderer = $this->runtime->canRender($rendererPath);
        $renderPath = $this->getRenderPath();

        if ($canRenderWithRenderer) {
            $renderedElement = $this->runtime->evaluate($rendererPath, $this);
        } elseif ($renderPath !== null) {
            if (substr($renderPath, 0, 1) === '/') {
                $renderedElement = $this->runtime->render(substr($renderPath, 1));
            } else {
                $renderedElement = $this->runtime->render($this->path . '/' . str_replace('.', '/', $renderPath));
            }
        } else {
            $renderedElement = $this->runtime->render(
                sprintf('%s/element<%s>', $this->path, $this->getType())
            );
        }
        return $renderedElement;
    }
}
