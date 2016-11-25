<?php
namespace Neos\Neos\View\Service;

/*
 * This file is part of the Neos.Neos package.
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
 * A view specialised on a JSON representation of Workspaces.
 *
 * This view is used by the service controllers in Neos\Neos\Controller\Service\
 *
 * @Flow\Scope("prototype")
 */
class WorkspaceJsonView extends JsonView
{
    /**
     * Configures rendering according to the set variable(s) and calls
     * render on the parent.
     *
     * @return string
     */
    public function render()
    {
        if (isset($this->variables['workspaces'])) {
            $this->setConfiguration(
                array(
                    'workspaces' => array(
                        '_descendAll' => array()
                    )
                )
            );
            $this->setVariablesToRender(array('workspaces'));
        } else {
            $this->setConfiguration(
                array(
                    'workspace' => array()
                )
            );
            $this->setVariablesToRender(array('workspace'));
        }

        return parent::render();
    }
}
