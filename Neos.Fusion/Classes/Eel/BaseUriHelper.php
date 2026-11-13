<?php

namespace Neos\Fusion\Eel;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * This is a purely internal helper to provide baseUris for Caching.
 * It will be moved to a more sensible package in the future so do
 * not rely on the classname for now.
 *
 * @internal
 */
class BaseUriHelper implements ProtectedContextAwareInterface
{
    /**
     * @var BaseUriProvider
     */
    #[Flow\Inject]
    protected $baseUriProvider;

    /**
     * @throws \Exception
     */
    public function getConfiguredBaseUriOrFallbackToCurrentRequest(?ServerRequestInterface $fallbackRequest = null): UriInterface
    {
        try {
            $baseUri = $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest();
        } catch (\Exception) {
            // We are avoiding an exception here in favor of trying more fallback variants.
        }

        if (isset($baseUri)) {
            return $baseUri;
        }

        if ($fallbackRequest === null) {
            throw new \Exception('Could not determine baseUri for current process and no fallback HTTP request was given, maybe running in a CLI command.', 1581002260);
        }

        return RequestInformationHelper::generateBaseUri($fallbackRequest);
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
