<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Helpers;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * You should implement this interface with a View that should allow access
 * to the TypoScript object it is rendered from (and so the TypoScript runtime).
 *
 * The TypoScript FluidView is the reference implementation for this.
 * @see \TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView
 *
 * @api
 */
interface TypoScriptAwareViewInterface
{
    /**
     * @return AbstractTypoScriptObject
     */
    public function getTypoScriptObject();
}
