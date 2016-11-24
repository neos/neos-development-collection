<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Render a TypoScript collection of nodes as an array
 *
 * //tsPath collection *Collection
 * //tsPath itemRenderer the TS object which is triggered for each element in the node collection
 */
class RawCollectionImplementation extends AbstractCollectionImplementation
{
    /**
     * Evaluate the collection nodes
     *
     * @return string
     */
    public function evaluate()
    {
        return parent::evaluateAsArray();
    }
}
