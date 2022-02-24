<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathProjector;
use Neos\EventSourcedNeosAdjustments\Ui\EditorContentStreamZookeeper;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Security\Authentication\AuthenticationProviderManager;
use Neos\RedirectHandler\Storage\RedirectStorageInterface;

class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = true;

    /**
     * @param Bootstrap $bootstrap
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(
            AuthenticationProviderManager::class,
            'authenticatedToken',
            EditorContentStreamZookeeper::class,
            'relayEditorAuthentication'
        );
        $dispatcher->connect(
            DocumentUriPathProjector::class,
            'documentUriPathChanged',
            function (string $oldUriPath, string $newUriPath, NodePropertiesWereSet $event) use ($bootstrap) {
                /** @var RouterCachingService $routerCachingService */
                $routerCachingService = $bootstrap->getObjectManager()->get(RouterCachingService::class);
                $routerCachingService->flushCachesForUriPath($oldUriPath);

                if (class_exists(RedirectStorageInterface::class)) {
                    if (!$bootstrap->getObjectManager()->isRegistered(RedirectStorageInterface::class)) {
                        return;
                    }
                    /** @var RedirectStorageInterface $redirectStorage */
                    $redirectStorage = $bootstrap->getObjectManager()->get(RedirectStorageInterface::class);
                    $redirectStorage->addRedirect(
                        $oldUriPath,
                        $newUriPath,
                        301,
                        [],
                        (string)$event->getInitiatingUserIdentifier(),
                        'via DocumentUriPathProjector'
                    );
                }
            }
        );
    }
}
