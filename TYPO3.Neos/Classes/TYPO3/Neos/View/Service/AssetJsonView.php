<?php
namespace TYPO3\Neos\View\Service;

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
use TYPO3\Flow\Mvc\View\JsonView;

/**
 * A view specialised on a JSON representation of Assets.
 *
 * This view is used by the service controllers in TYPO3\Neos\Controller\Service\
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
                array(
                    'assets' => array(
                        '_descendAll' => array(
                            '_only' => array('label', 'tags', 'identifier')
                        )
                    )
                )
            );
            $this->setVariablesToRender(array('assets'));
        } else {
            $this->setConfiguration(
                array(
                    'asset' => array(
                        '_only' => array('label', 'tags', 'identifier')
                    )
                )
            );
            $this->setVariablesToRender(array('asset'));
        }

        return parent::render();
    }
}
