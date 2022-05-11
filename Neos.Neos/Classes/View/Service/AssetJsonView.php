<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;

/**
 * A view specialised on a JSON representation of Assets.
 *
 * This view is used by the service controllers in Neos\Neos\Controller\Service\
 *
 * @Flow\Scope("prototype")
 */
class AssetJsonView extends JsonView
{
    /**
     * Configures rendering according to the set variable(s) and calls
     * render on the parent.
     *
     * @return string
     */
    public function render()
    {
        if (isset($this->variables['assets'])) {
            $this->setConfiguration(
                [
                    'assets' => [
                        '_descendAll' => [
                            '_only' => ['label', 'tags', 'identifier']
                        ]
                    ]
                ]
            );
            $this->setVariablesToRender(['assets']);
        } else {
            $this->setConfiguration(
                [
                    'asset' => [
                        '_only' => ['label', 'tags', 'identifier']
                    ]
                ]
            );
            $this->setVariablesToRender(['asset']);
        }

        return parent::render();
    }
}
