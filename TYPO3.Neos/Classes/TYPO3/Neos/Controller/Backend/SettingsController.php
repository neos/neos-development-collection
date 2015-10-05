<?php
namespace TYPO3\Neos\Controller\Backend;

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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 */
class SettingsController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    /**
     * @return string
     */
    public function editPreviewAction()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $configuration = new PositionalArraySorter(Arrays::getValueByPath($this->settings, 'userInterface.editPreviewModes'));
        return json_encode($configuration->toArray());
    }
}
