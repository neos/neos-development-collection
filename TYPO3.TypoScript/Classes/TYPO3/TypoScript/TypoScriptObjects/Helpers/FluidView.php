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

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Fluid\Core\Parser\Configuration;
use TYPO3\Fluid\View\StandaloneView;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Extended Fluid Template View for use in TypoScript.
 */
class FluidView extends StandaloneView implements TypoScriptAwareViewInterface
{
    /**
     * @var string
     */
    protected $resourcePackage;

    /**
     * @var AbstractTypoScriptObject
     */
    protected $typoScriptObject;

    /**
     * @param AbstractTypoScriptObject $typoScriptObject
     * @param ActionRequest $request The current action request. If none is specified it will be created from the environment.
     */
    public function __construct(AbstractTypoScriptObject $typoScriptObject, ActionRequest $request = null)
    {
        parent::__construct($request);
        $this->typoScriptObject = $typoScriptObject;
    }

    /**
     * @param string $resourcePackage
     */
    public function setResourcePackage($resourcePackage)
    {
        $this->resourcePackage = $resourcePackage;
    }

    /**
     * @return string
     */
    public function getResourcePackage()
    {
        return $this->resourcePackage;
    }

    /**
     * @return AbstractTypoScriptObject
     */
    public function getTypoScriptObject()
    {
        return $this->typoScriptObject;
    }

    /**
     * Build parser configuration
     *
     * @return Configuration
     */
    protected function buildParserConfiguration()
    {
        $parserConfiguration = $this->objectManager->get('TYPO3\Fluid\Core\Parser\Configuration');
        if (in_array($this->controllerContext->getRequest()->getFormat(), array('html', null))) {
            $resourceInterceptor = $this->objectManager->get('TYPO3\Fluid\Core\Parser\Interceptor\Resource');
            if ($this->resourcePackage !== null) {
                $resourceInterceptor->setDefaultPackageKey($this->resourcePackage);
            }
            $parserConfiguration->addInterceptor($this->objectManager->get('TYPO3\Fluid\Core\Parser\Interceptor\Escape'));
            $parserConfiguration->addInterceptor($resourceInterceptor);
        }
        return $parserConfiguration;
    }
}
