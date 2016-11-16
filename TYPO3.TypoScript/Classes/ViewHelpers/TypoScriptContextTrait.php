<?php
namespace TYPO3\TypoScript\ViewHelpers;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;

/**
 * This trait is to be used in ViewHelpers that need to get information from the TypoScript runtime context.
 * It will only work when the ViewHelper in question is used in a TypoScriptAwareViewInterface.
 *
 * A property "viewHelperVariableContainer" is expected in classes that use this, which will be the case for any Fluid ViewHelper.
 */
trait TypoScriptContextTrait
{

    /**
     * Get a variable value from the TypoScript runtime context.
     *
     * Note: This will return NULL if the variable didn't exist.
     *
     * @see hasContextVariable()
     *
     * @param string $variableName
     * @return mixed
     */
    protected function getContextVariable($variableName)
    {
        $value = null;

        $view = $this->viewHelperVariableContainer->getView();
        if ($view instanceof TypoScriptAwareViewInterface) {
            $typoScriptObject = $view->getTypoScriptObject();
            $currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();
            if (isset($currentContext[$variableName])) {
                $value = $currentContext[$variableName];
            }
        }

        return $value;
    }

    /**
     * @param string $variableName
     * @return boolean
     */
    protected function hasContextVariable($variableName)
    {
        $view = $this->viewHelperVariableContainer->getView();
        if (!$view instanceof TypoScriptAwareViewInterface) {
            return false;
        }

        $typoScriptObject = $view->getTypoScriptObject();
        $currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();

        return array_key_exists($variableName, $currentContext);
    }
}
