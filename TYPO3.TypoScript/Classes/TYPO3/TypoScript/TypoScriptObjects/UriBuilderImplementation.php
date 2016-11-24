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

/**
 * A TypoScript UriBuilder object
 *
 * The following TS properties are evaluated:
 *  * package
 *  * subpackage
 *  * controller
 *  * action
 *  * arguments
 *  * format
 *  * section
 *  * additionalParams
 *  * addQueryString
 *  * argumentsToBeExcludedFromQueryString
 *  * absolute
 *
 * See respective getters for descriptions
 */
class UriBuilderImplementation extends AbstractTypoScriptObject
{
    /**
     * Key of the target package
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->tsValue('package');
    }

    /**
     * Key of the target sub package
     *
     * @return string
     */
    public function getSubpackage()
    {
        return $this->tsValue('subpackage');
    }

    /**
     * Target controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->tsValue('controller');
    }

    /**
     * Target controller action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->tsValue('action');
    }

    /**
     * Controller arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->tsValue('arguments');
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->tsValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string
     */
    public function getSection()
    {
        return $this->tsValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->tsValue('additionalParams');
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = TRUE
     *
     * @return array
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->tsValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If TRUE, the current query parameters will be kept in the URI
     *
     * @return boolean
     */
    public function isAddQueryString()
    {
        return (boolean)$this->tsValue('addQueryString');
    }

    /**
     * If TRUE, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (boolean)$this->tsValue('absolute');
    }

    /**
     * @return string
     */
    public function evaluate()
    {
        $controllerContext = $this->tsRuntime->getControllerContext();
        $uriBuilder = $controllerContext->getUriBuilder()->reset();

        $format = $this->getFormat();
        if ($format !== null) {
            $uriBuilder->setFormat($format);
        }

        $additionalParams = $this->getAdditionalParams();
        if ($additionalParams !== null) {
            $uriBuilder->setArguments($additionalParams);
        }

        $argumentsToBeExcludedFromQueryString = $this->getArgumentsToBeExcludedFromQueryString();
        if ($argumentsToBeExcludedFromQueryString !== null) {
            $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);
        }

        $absolute = $this->isAbsolute();
        if ($absolute === true) {
            $uriBuilder->setCreateAbsoluteUri(true);
        }

        $section = $this->getSection();
        if ($section !== null) {
            $uriBuilder->setSection($section);
        }

        $addQueryString = $this->isAddQueryString();
        if ($addQueryString === true) {
            $uriBuilder->setAddQueryString(true);
        }

        try {
            return $uriBuilder->uriFor(
                $this->getAction(),
                $this->getArguments(),
                $this->getController(),
                $this->getPackage(),
                $this->getSubpackage()
            );
        } catch (\Exception $exception) {
            return $this->tsRuntime->handleRenderingException($this->path, $exception);
        }
    }
}
