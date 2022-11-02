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


trait RendererTrait
{
    /**
     * Evaluates to a result using either `renderer`, `renderPath` or `type` from the configuration
     */
    private function evaluateRenderer(): mixed
    {
        $canRenderWithRenderer = $this->runtime->canRender($this->path . '/renderer');
        if ($canRenderWithRenderer) {
            return $this->fusionValue('renderer');
        }

        $renderPath = $this->fusionValue('renderPath');
        if ($renderPath !== null) {
            if (str_starts_with($renderPath, '/')) {
                // absolute path
                return $this->runtime->render(substr($renderPath, 1));
            }
            // relative path
            return $this->runtime->render(
                $this->path . '/' . str_replace('.', '/', $renderPath)
            );
        }

        return $this->runtime->render(
            $this->path . '/element<' . $this->fusionValue('type') . '>'
        );
    }
}
