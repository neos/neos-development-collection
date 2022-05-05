<?php
namespace Neos\Neos\Controller\Backend;

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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 */
class SettingsController extends ActionController
{
    /**
     * @return string
     */
    public function editPreviewAction()
    {
        $this->response->setContentType('application/json');
        $configuration = new PositionalArraySorter(Arrays::getValueByPath(
            $this->settings,
            'userInterface.editPreviewModes'
        ));
        return json_encode($configuration->toArray());
    }
}
