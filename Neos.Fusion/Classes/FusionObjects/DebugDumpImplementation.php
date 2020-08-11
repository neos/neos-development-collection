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

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Service\DebugStack;

/**
 * A Fusion object for dumping debugging fusion-values
 *
 * This needs to be used as a processor
 *
 * @api
 */
class DebugDumpImplementation extends AbstractFusionObject
{
    /**
     * @var DebugStack
     * @Flow\Inject
     */
    protected $stack;

    public function isEnabled() : bool
    {
        return $this->fusionValue('enabled') ?: false;
    }


    /**
     * Return the values in a human readable form
     *
     * @return string
     */
    public function evaluate()
    {
        if ($this->stack->hasMessage() && $this->isEnabled()) {
            $this->getRuntime()->setEnableContentCache(false);
            return $this->stack->dump();
        }
        return $this->fusionValue('value');
    }
}
