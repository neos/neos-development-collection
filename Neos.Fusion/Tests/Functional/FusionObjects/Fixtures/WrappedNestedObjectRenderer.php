<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects\Fixtures;

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
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

/**
 * Renderer which wraps the nested Fusion object found at "value" with "prepend" and "append".
 *
 * Needed for more complex prototype inheritance chain testing.
 */
class WrappedNestedObjectRenderer extends AbstractArrayFusionObject
{
    /**
     * The string the current value should be prepended with
     *
     * @return string
     */
    public function getPrepend()
    {
        return $this->fusionValue('prepend');
    }

    /**
     * The string the current value should be suffixed with
     *
     * @return string
     */
    public function getAppend()
    {
        return $this->fusionValue('append');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        return $this->getPrepend() . $this->runtime->evaluate($this->path . '/value') . $this->getAppend();
    }
}
