<?php
namespace TYPO3\Neos\View\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\JsonView;

/**
 * A view specialised on a JSON representation of Nodes.
 *
 * This view is used by the service controllers in TYPO3\Neos\Controller\Service\
 *
 * @Flow\Scope("prototype")
 */
class NodeJsonView extends JsonView
{
    /**
     * Configures rendering according to the set variable(s) and calls
     * render on the parent.
     *
     * @return string
     */
    public function render()
    {
        if (isset($this->variables['nodes'])) {
            $this->setConfiguration(
                array(
                    'nodes' => array(
                        '_descendAll' => array(
                            '_only' => array('name', 'path', 'identifier', 'properties', 'nodeType')
                        )
                    )
                )
            );
            $this->setVariablesToRender(array('nodes'));
        } else {
            $this->setConfiguration(
                array(
                    'node' => array(
                        '_only' => array('name', 'path', 'identifier', 'properties', 'nodeType')
                    )
                )
            );
            $this->setVariablesToRender(array('node'));
        }

        return parent::render();
    }
}
