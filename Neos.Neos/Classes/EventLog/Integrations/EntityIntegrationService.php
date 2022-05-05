<?php
namespace Neos\Neos\EventLog\Integrations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Exception;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\EventLog\Domain\Model\Event;

/**
 * Monitors entity changes
 *
 * @Flow\Scope("singleton")
 */
class EntityIntegrationService extends AbstractIntegrationService
{
    /**
     * Doctrine's Entity Manager.
     *
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration("eventLog.monitorEntities")
     * @var array
     */
    protected $monitorEntitiesSetting;

    /**
     * Dummy method which is called in a prePersist signal.
     * If we remove that, this object is never instantiated and thus cannot hook into the Doctrine EntityManager.
     *
     * @return void
     */
    public function dummyMethodToEnsureInstanceExists()
    {
        // intentionally empty
    }

    /**
     * Record events for entity changes.
     *
     * Note: this method is registered as an Doctrine event listener in the settings of this package.
     *
     * TODO: Update/Delete of Entities
     *
     * @param OnFlushEventArgs $eventArgs
     * @return void
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->eventEmittingService->isEnabled()) {
            return;
        }

        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $className = get_class($entity);
            if (isset($this->monitorEntitiesSetting[$className])) {
                $entityMonitoringConfiguration = $this->monitorEntitiesSetting[$className];

                if (isset($entityMonitoringConfiguration['events']['created'])) {
                    $data = [];
                    foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
                        $data[$key] = Utility::evaluateEelExpression(
                            $eelExpression,
                            $this->eelEvaluator,
                            ['entity' => $entity]
                        );
                    }

                    $event = $this->eventEmittingService->emit(
                        $entityMonitoringConfiguration['events']['created'],
                        $data
                    );
                    $unitOfWork->computeChangeSet($entityManager->getClassMetadata(Event::class), $event);
                }
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $className = get_class($entity);
            if (isset($this->monitorEntitiesSetting[$className])) {
                $entityMonitoringConfiguration = $this->monitorEntitiesSetting[$className];

                if (isset($entityMonitoringConfiguration['events']['deleted'])) {
                    $data = [];
                    foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
                        $data[$key] = Utility::evaluateEelExpression(
                            $eelExpression,
                            $this->eelEvaluator,
                            ['entity' => $entity]
                        );
                    }

                    $event = $this->eventEmittingService->emit(
                        $entityMonitoringConfiguration['events']['deleted'],
                        $data
                    );
                    $unitOfWork->computeChangeSet($entityManager->getClassMetadata(Event::class), $event);
                }
            }
        }
    }

    /**
     * @param array $monitorEntitiesSetting
     * @return void
     */
    public function setMonitorEntitiesSetting($monitorEntitiesSetting)
    {
        $this->monitorEntitiesSetting = $monitorEntitiesSetting;
    }
}
