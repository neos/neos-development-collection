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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Utility\Arrays;

/**
 * A Fusion ActionUri object
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
     * Option to set custom routing arguments
     *
     * Please do not use this functionality to append query parameters and use 'queryParameters' instead:
     *
     *   Neos.Fusion:ActionUri {
     *     queryParameters = ${{'q':'search term'}}
     *   }
     *
     * Appending query parameters via the use of exceeding routing arguments relies
     * on `appendExceedingArguments` internally which is discouraged to leverage.
     *
     * But in case you meant to use routing arguments for advanced uri building,
     * you can leverage this low level option.
     *
     * Be aware in order for the routing framework to match and resolve the arguments,
     * your have to define a custom route in Routes.yaml
     *
     * @return array<string, mixed>
     */
    public function getRoutingArguments(): array
    {
        return $this->fusionValue('routingArguments') ?: [];
    }

    /**
     * Controller arguments
     *
     * @return array|null
     * @deprecated with Neos 8.4 please use routingArguments or queryParameters instead
     */
    public function getArguments(): ?array
    {
        $arguments = $this->fusionValue('arguments');
        return is_array($arguments) ? $arguments : [];
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
     * @deprecated with Neos 8.4 please use routingArguments or queryParameters instead
     */
    public function getAdditionalParams(): ?array
    {
        return $this->fusionValue('additionalParams');
    }

    /**
     * Query parameters that are appended to the url
     *
     * @return array
     */
    public function getQueryParameters(): array
    {
        return $this->fusionValue('queryParameters') ?: [];
    }

    /**
     * Arguments to be removed from the URI. Only active if addQueryString = true
     *
     * @return array|null
     * @deprecated to be removed with Neos 9
     */
    public function getArgumentsToBeExcludedFromQueryString(): ?array
    {
        return $this->fusionValue('argumentsToBeExcludedFromQueryString');
    }

    /**
     * If true, the current query parameters will be kept in the URI
     *
     * @return boolean
     * @deprecated to be removed with Neos 9
     */
    public function isAddQueryString(): bool
    {
        return (boolean)$this->fusionValue('addQueryString');
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
     * @return UriBuilder
     */
    public function createUriBuilder(): UriBuilder
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->getRequest());
        return $uriBuilder;
    }

    /**
     * @return string
     */
    public function evaluate()
    {
        $uriBuilder = $this->createUriBuilder();

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
            $arguments = $this->getArguments();
            $routingArguments = $this->getRoutingArguments();
            if ($arguments && $routingArguments) {
                throw new RuntimeException('Neos.Fusion:ActionUri does not allow to combine "arguments" and "routingArguments"', 1665431866);
            }
            $uriString = $uriBuilder->uriFor(
                $this->getAction(),
                $routingArguments ?: $arguments,
                $this->getController(),
                $this->getPackage(),
                $this->getSubpackage()
            );
            $queryParameters = $this->getQueryParameters();
            if (empty($queryParameters)) {
                return $uriString;
            }
            $uri = new Uri($uriString);
            parse_str($uri->getQuery(), $queryParametersFromRouting);
            $mergedQueryParameters = Arrays::arrayMergeRecursiveOverrule($queryParametersFromRouting, $queryParameters);
            return (string)$uri->withQuery(http_build_query($mergedQueryParameters, '', '&'));
        } catch (\Exception $exception) {
            return $this->runtime->handleRenderingException($this->path, $exception);
        }
    }
}
