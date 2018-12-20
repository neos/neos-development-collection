<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\View;

/*
 * This file is part of the Neos.EventSourcedNeosAdjustments package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class BackendFusionView extends \Neos\Neos\Ui\View\BackendFusionView
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->setFusionPathPatterns([
            'resource://Neos.Neos.Ui/Private/Fusion/Backend',
            'resource://Neos.EventSourcedNeosAdjustments/Private/Fusion/Backend'
        ]);
    }
}
