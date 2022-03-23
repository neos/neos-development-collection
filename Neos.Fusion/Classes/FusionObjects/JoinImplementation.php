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

/**
 * Fusion object to render a list of items as single concatenated string
 */
class JoinImplementation extends AbstractArrayFusionObject
{

    /**
     * Get the glue to insert between items
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->fusionValue('__meta/glue') ?? '';
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function evaluate()
    {
        $glue = $this->getGlue();

        $resultParts = $this->evaluateNestedProperties();
        if ($resultParts !== []) {
            return implode($glue, $resultParts);
        }

        return null;
    }
}
