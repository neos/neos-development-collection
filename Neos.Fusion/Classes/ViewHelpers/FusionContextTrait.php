<?php
namespace Neos\Fusion\ViewHelpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\FusionObjects\Helpers\FusionAwareViewInterface;

/**
 * This trait is to be used in ViewHelpers that need to get information from the Fusion runtime context.
 * It will only work when the ViewHelper in question is used in a FusionAwareViewInterface.
 *
 * A property "viewHelperVariableContainer" is expected in classes that use this, which will be the case for any Fluid ViewHelper.
 */
trait FusionContextTrait
{

    /**
     * Get a variable value from the Fusion runtime context.
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
        if ($view instanceof FusionAwareViewInterface) {
            $fusionObject = $view->getFusionObject();
            $currentContext = $fusionObject->getRuntime()->getCurrentContext();
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
        if (!$view instanceof FusionAwareViewInterface) {
            return false;
        }

        $fusionObject = $view->getFusionObject();
        $currentContext = $fusionObject->getRuntime()->getCurrentContext();

        return array_key_exists($variableName, $currentContext);
    }
}
