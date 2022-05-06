<?php
namespace Neos\ContentRepository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * The ContentRepository Package
 */
class Package extends BasePackage
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', NodeDataRepository::class, 'flushNodeRegistry');
        $dispatcher->connect(NodeDataRepository::class, 'repositoryObjectsPersisted', NodeDataRepository::class, 'flushNodeRegistry');
        $dispatcher->connect(Node::class, 'nodePathChanged', function () use ($bootstrap) {
            $contextFactory = $bootstrap->getObjectManager()->get(ContextFactoryInterface::class);
            /** @var Context $contextInstance */
            foreach ($contextFactory->getInstances() as $contextInstance) {
                $contextInstance->getFirstLevelNodeCache()->flush();
            }
        });

        // this fixes https://github.com/neos/neos-development-collection/issues/3173
        $dispatcher->connect(Workspace::class, 'afterNodePublishing', function () use ($bootstrap) {
            $contextFactory = $bootstrap->getObjectManager()->get(ContextFactoryInterface::class);
            foreach ($contextFactory->getInstances() as $contextInstance) {
                $contextInstance->getFirstLevelNodeCache()->flush();
            }
        });
    }
}
