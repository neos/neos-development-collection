<?php
namespace Neos\Fusion\View;

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
 * Deprecated View for using Fusion for standard MVC controllers. For more Details check `FusionView`
 * This class will be removed with the release of Neos 4.0.
 *
 * @deprecated since 3.0
 */
class TypoScriptView extends FusionView
{
    /**
     * Sets the Fusion path to be rendered to an explicit value;
     * to be used mostly inside tests.
     *
     * @param string $typoScriptPath
     * @return void
     */
    public function setTypoScriptPath($typoScriptPath)
    {
        parent::setFusionPath($typoScriptPath);
    }

    /**
     * @param string $pathPattern
     * @return void
     */
    public function setTypoScriptPathPattern($pathPattern)
    {
        parent::setFusionPathPattern($pathPattern);
    }

    /**
     * @param array $pathPatterns
     * @return void
     */
    public function setTypoScriptPathPatterns(array $pathPatterns)
    {
        parent::setFusionPathPatterns($pathPatterns);
    }

    /**
     * Load the Fusion Files form the defined
     * paths and construct a Runtime from the
     * parsed results
     *
     * @return void
     */
    public function initializeTypoScriptRuntime()
    {
        parent::initializeFusionRuntime();
    }

    /**
     * Set a specific option of this View
     * Reset runtime cache if an option is changed
     * The typoScript-options are mapped to their fusion counterparts
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     */
    public function setOption($optionName, $value)
    {
        switch ($optionName) {
            case 'typoScriptPath':
                $optionName = 'fusionPath';
                break;
            case 'typoScriptPathPatterns':
                $optionName = 'fusionPathPatterns';
                break;
        }
        parent::setOption($optionName, $value);
    }

    /**
     * Get a specific option of this View
     * The typoScript-options are mapped to their fusion counterparts
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        switch ($optionName) {
            case 'typoScriptPath':
                $optionName = 'fusionPath';
                break;
            case 'typoScriptPathPatterns':
                $optionName = 'fusionPathPatterns';
                break;
        }
        return parent::getOption($optionName);
    }
}
