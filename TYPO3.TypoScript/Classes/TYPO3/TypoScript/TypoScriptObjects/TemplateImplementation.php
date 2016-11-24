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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;

/**
 * TypoScript object rendering a fluid template
 *
 * //tsPath variables TODO The result of this TS object is made available inside the template as "variables"
 * @api
 */
class TemplateImplementation extends AbstractArrayTypoScriptObject
{
    /**
     * Path to the template which should be rendered
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->tsValue('templatePath');
    }

    /**
     * Path to the partial root
     *
     * @return string
     */
    public function getPartialRootPath()
    {
        return $this->tsValue('partialRootPath');
    }

    /**
     * Path to the layout root
     *
     * @return string
     */
    public function getLayoutRootPath()
    {
        return $this->tsValue('layoutRootPath');
    }

    /**
     * Name of a specific section, if only this section should be rendered.
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->tsValue('sectionName');
    }

    /**
     * @return string
     * @internal
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        $actionRequest =  $this->tsRuntime->getControllerContext()->getRequest();
        if (!$actionRequest instanceof ActionRequest) {
            $actionRequest = null;
        }
        $fluidTemplate = new Helpers\FluidView($this, $actionRequest);

        $templatePath = $this->getTemplatePath();
        if ($templatePath === null) {
            throw new \Exception(sprintf("
				No template path set.
				Most likely you didn't configure `templatePath` in your TypoScript object correctly.
				For example you could add and adapt the following line to your TypoScript:
				`prototype(%s) < prototype(TYPO3.TypoScript:Template) {
					templatePath = 'resource://Vendor.Package/Private/Templates/MyObject.html'
				}`
			", $templatePath, $this->typoScriptObjectName));
        }
        $fluidTemplate->setTemplatePathAndFilename($templatePath);

        $partialRootPath = $this->getPartialRootPath();
        if ($partialRootPath !== null) {
            $fluidTemplate->setPartialRootPath($partialRootPath);
        }

        $layoutRootPath = $this->getLayoutRootPath();
        if ($layoutRootPath !== null) {
            $fluidTemplate->setLayoutRootPath($layoutRootPath);
        }

            // Template resources need to be evaluated from the templates package not the requests package.
        if (strpos($templatePath, 'resource://') === 0) {
            $templateResourcePathParts = parse_url($templatePath);
            $fluidTemplate->setResourcePackage($templateResourcePathParts['host']);
        }

        foreach ($this->properties as $key => $value) {
            if (in_array($key, $this->ignoreProperties)) {
                continue;
            }
            if (!is_array($value)) {
                // if a value is a SIMPLE TYPE, e.g. neither an Eel expression nor a TypoScript object,
                    // we can just evaluate it (to handle processors) and then assign it to the template.
                $evaluatedValue = $this->tsValue($key);
                $fluidTemplate->assign($key, $evaluatedValue);
            } else {
                // It is an array; so we need to create a "proxy" for lazy evaluation, as it could be a
                    // nested TypoScript object, Eel expression or simple value.
                $fluidTemplate->assign($key, new Helpers\TypoScriptPathProxy($this, $this->path . '/' . $key, $value));
            }
        }

        $this->initializeView($fluidTemplate);

        $sectionName = $this->getSectionName();
        if ($sectionName !== null) {
            return $fluidTemplate->renderSection($sectionName);
        } else {
            return $fluidTemplate->render();
        }
    }

    /**
     * This is a template method which can be overridden in subclasses to add new variables which should
     * be available inside the Fluid template. It is needed e.g. for Expose.
     *
     * @param Helpers\FluidView $view
     * @return void
     */
    protected function initializeView(Helpers\FluidView $view)
    {
        // template method
    }
}
