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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;

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
