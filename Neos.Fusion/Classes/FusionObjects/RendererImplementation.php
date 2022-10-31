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
    public function getRenderer(): mixed
    {
        return $this->fusionValue('renderer');
    }

    public function getRenderPath(): ?string
    {
        return $this->fusionValue('renderPath');
    }

    public function getType(): ?string
    {
        return $this->fusionValue('type');
    }

    private function canRenderWithRenderer()
    {
        return $this->runtime->canRender($this->path . '/renderer');
    }

    public function evaluate()
    {
        if ($this->canRenderWithRenderer()) {
            return $this->getRenderer();
        }

        if ($this->getRenderPath() !== null) {
            if (str_starts_with($this->getRenderPath(), '/')) {
                // absolute path
                return $this->runtime->render(substr($this->getRenderPath(), 1));
            }
            // relative path
            return $this->runtime->render(
                $this->path . '/' . str_replace('.', '/', $this->getRenderPath())
            );
        }

        return $this->runtime->render(
            $this->path . '/element<' . $this->getType() . '>'
        );
    }
}
