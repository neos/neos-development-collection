<?php

declare(strict_types=1);

namespace Neos\Fusion\Core;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Mvc\Routing\UriBuilder;

/**
 * Legacy stub to replace the original {@see ControllerContext} inside Fusion {@see Runtime::getControllerContext()}.
 *
 * The concept of the controller context inside Fusion has been deprecated.
 *
 * You should definitely not pass this object along further, which will also most likely not work as it doesn't
 * satisfy the constraint of `instanceof ControllerContext`!
 *
 * To migrate the use case of fetching the active request, please look into {@see FusionGlobals::get()} instead.
 * By convention, an {@see ActionRequest} will be available as `request`:
 *
 *     $actionRequest = $this->runtime->fusionGlobals->get('request');
 *     if (!$actionRequest instanceof ActionRequest) {
 *         // fallback or error
 *     }
 *
 * To get an {@see UriBuilder} proceed with:
 *
 *     $uriBuilder = new UriBuilder();
 *     $uriBuilder->setRequest($actionRequest);
 *
 * WARNING regarding {@see Runtime::getControllerContext()}:
 *      Invoking this backwards-compatible layer is possibly unsafe, if the rendering was not started
 *      in {@see self::renderResponse()} or no `request` global is available. This will raise an exception.
 *
 * @deprecated with Neos 9.0 can be removed with 10
 * @internal
 */
final class LegacyFusionControllerContext
{
    /**
     * @Flow\Inject
     * @var FlashMessageService
     */
    protected $flashMessageService;

    public function __construct(
        private readonly ActionRequest $request,
        private readonly ActionResponse $legacyActionResponseForCurrentRendering
    ) {
    }

    /**
     * To migrate the use case of fetching the active request, please look into {@see FusionGlobals::get()} instead.
     * By convention, an {@see ActionRequest} will be available as `request`:
     *
     *     $actionRequest = $this->runtime->fusionGlobals->get('request');
     *     if (!$actionRequest instanceof ActionRequest) {
     *         // fallback or error
     *     }
     *
     * @deprecated with Neos 9.0 can be removed with 10
     */
    public function getRequest(): ActionRequest
    {
        return $this->request;
    }

    /**
     * To migrate the use case of getting the UriBuilder please use this instead:
     *
     *     $actionRequest = $this->runtime->fusionGlobals->get('request');
     *     if (!$actionRequest instanceof ActionRequest) {
     *         // fallback or error
     *     }
     *     $uriBuilder = new UriBuilder();
     *     $uriBuilder->setRequest($actionRequest);
     *
     * @deprecated with Neos 9.0 can be removed with 10
     */
    public function getUriBuilder(): UriBuilder
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($this->request);
        return $uriBuilder;
    }

    /**
     * To migrate this use case please use {@see FlashMessageService::getFlashMessageContainerForRequest()} in
     * combination with fetching the active request as described here {@see getRequest} instead.
     *
     * @deprecated with Neos 9.0 can be removed with 10
     */
    public function getFlashMessageContainer(): FlashMessageContainer
    {
        return $this->flashMessageService->getFlashMessageContainerForRequest($this->request);
    }

    /**
     * PURELY INTERNAL with partially undefined behaviour!!!
     *
     * Gives access to the legacy mutable action response simulation {@see Runtime::withSimulatedLegacyControllerContext()}
     *
     * Initially it was possible to mutate the current response of the active MVC controller through this getter.
     *
     * While *HIGHLY* internal behaviour and *ONLY* to be used by Neos.Fusion.Form or Neos.Neos:Plugin
     * this legacy layer is in place to still allow this functionality.
     *
     * @deprecated with Neos 9.0 can be removed with 10
     * @internal THIS SHOULD NEVER BE CALLED ON USER-LAND
     */
    public function getResponse(): ActionResponse
    {
        // expose action response to be possibly mutated in neos forms or fusion plugins.
        // this behaviour is highly internal and deprecated!
        return $this->legacyActionResponseForCurrentRendering;
    }

    /**
     * The method {@see ControllerContext::getArguments()} was removed without replacement.
     */
    // public function getArguments(): Arguments;
}
