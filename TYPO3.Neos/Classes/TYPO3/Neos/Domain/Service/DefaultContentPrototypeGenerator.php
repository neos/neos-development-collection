<?php
namespace TYPO3\Neos\Domain\Service;

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

/**
 * Generate a TypoScript prototype definition based on TYPO3.Neos:Content
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DefaultContentPrototypeGenerator extends DefaultPrototypeGenerator
{
    /**
     * The Name of the prototype that is extended
     *
     * @var string
     */
    protected $basePrototypeName = 'TYPO3.Neos:Content';
}
