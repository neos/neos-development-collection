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

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * A Fusion UriBuilder object
 *
 * The following Fusion properties are evaluated:
 *  * package
 *  * subpackage
 *  * controller
 *  * action
 *  * arguments
 *  * format
 *  * section
 *  * additionalParams
 *  * absolute
 *
 * See respective getters for descriptions
 *
 * @deprecated in favor of ActionUriImplementation
 */
class UriBuilderImplementation extends AbstractFusionObject
{
    /**
     * Key of the target package
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->fusionValue('package');
    }

    /**
     * Key of the target sub package
     *
     * @return string
     */
    public function getSubpackage()
    {
        return $this->fusionValue('subpackage');
    }

    /**
     * Target controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->fusionValue('controller');
    }

    /**
     * Target controller action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->fusionValue('action');
    }

    /**
     * Controller arguments
     *
     * @return array
     */
    public function getArguments()
    {
        $arguments = $this->fusionValue('arguments');
        return is_array($arguments) ? $arguments: [];
    }

    /**
     * The requested format, for example "html"
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->fusionValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string
     */
    public function getSection()
    {
        return $this->fusionValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->fusionValue('additionalParams');
    }

    /**
     * If true, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return (boolean)$this->fusionValue('absolute');
    }

    /**
     * @return string
     */
    public function evaluate()
    {
        $uriBuilder = new UriBuilder();
        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $uriBuilder->setRequest($possibleRequest);
        } else {
            // unfortunately, the uri-builder always needs a request at hand and cannot build uris without
            // even, if the default param merging would not be required
            // this will improve with a reformed uri building:
            // https://github.com/neos/flow-development-collection/pull/2744
            $uriBuilder->setRequest(
                ActionRequest::fromHttpRequest(ServerRequest::fromGlobals())
            );
        }

        $format = $this->getFormat();
        if ($format !== null) {
            $uriBuilder->setFormat($format);
        }

        $additionalParams = $this->getAdditionalParams();
        if ($additionalParams !== null) {
            $uriBuilder->setArguments($additionalParams);
        }

        $absolute = $this->isAbsolute();
        if ($absolute === true) {
            $uriBuilder->setCreateAbsoluteUri(true);
        }

        $section = $this->getSection();
        if ($section !== null) {
            $uriBuilder->setSection($section);
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
            return $this->runtime->handleRenderingException($this->path, $exception);
        }
    }
}
