<?php
declare(strict_types=1);

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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Fusion\Exception as FusionException;

/**
 * A Fusion ActionUri object
 *
 * The following Fusion properties are evaluated:
 *  * package
 *  * subpackage
 *  * controller
 *  * action
 *  * arguments
 *  * queryParameters
 *  * format
 *  * section
 *  * additionalParams
 *  * absolute
 *  * request
 *
 * See respective getters for descriptions
 */
class ActionUriImplementation extends AbstractFusionObject
{
    /**
     * @return ActionRequest
     */
    public function getRequest(): ActionRequest
    {
        return $this->fusionValue('request');
    }

    /**
     * Key of the target package
     *
     * @return string|null
     */
    public function getPackage(): ?string
    {
        return $this->fusionValue('package');
    }

    /**
     * Key of the target sub package
     *
     * @return string|null
     */
    public function getSubpackage(): ?string
    {
        return $this->fusionValue('subpackage');
    }

    /**
     * Target controller name
     *
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->fusionValue('controller');
    }

    /**
     * Target controller action name
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->fusionValue('action');
    }

    /**
     * Controller arguments
     *
     * @return array|null
     */
    public function getArguments(): ?array
    {
        $arguments = $this->fusionValue('arguments');
        return is_array($arguments) ? $arguments: [];
    }

    /**
     * Query parameters to add as query string to the generated URL
     *
     * @return array
     */
    public function getQueryParameters(): array
    {
        $queryParameters = $this->fusionValue('queryParameters');
        return is_array($queryParameters) ? $queryParameters : [];
    }

    /**
     * The requested format, for example "html"
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->fusionValue('format');
    }

    /**
     * The anchor to be appended to the URL
     *
     * @return string|null
     */
    public function getSection(): ?string
    {
        return $this->fusionValue('section');
    }

    /**
     * Additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     *
     * @return array|null
     */
    public function getAdditionalParams(): ?array
    {
        return $this->fusionValue('additionalParams');
    }

    /**
     * If true, an absolute URI is rendered
     *
     * @return boolean
     */
    public function isAbsolute(): bool
    {
        return (boolean)$this->fusionValue('absolute');
    }

    /**
     * @return string
     */
    public function evaluate()
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->getRequest());

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
            $uri = $uriBuilder->uriFor(
                $this->getAction(),
                $this->getArguments(),
                $this->getController(),
                $this->getPackage(),
                $this->getSubpackage()
            );
            $queryParameters = $this->getQueryParameters();
            if ($queryParameters !== []) {
                if (parse_url($uri, PHP_URL_QUERY) !== null) {
                    throw new FusionException('"queryParameters" must not be used for Routes with "appendExceedingArguments"', 1782317977);
                }
                [$uri, $segment] = array_pad(explode('#', $uri, 2), 2, null);
                $uri .= '?' . http_build_query($queryParameters, arg_separator: '&');
                if ($segment !== null) {
                    $uri .= '#' . $segment;
                }
            }
        } catch (\Exception $exception) {
            return $this->runtime->handleRenderingException($this->path, $exception);
        }
        return $uri;
    }
}
