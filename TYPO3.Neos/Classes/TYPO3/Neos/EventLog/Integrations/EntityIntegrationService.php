<?php
namespace TYPO3\Neos\EventLog\Integrations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\Exception;
use TYPO3\Eel\Utility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Neos\EventLog\Domain\Model\Event;
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;

/**
 * Monitors entity changes
 *
 * @Flow\Scope("singleton")
 */
class EntityIntegrationService extends AbstractIntegrationService
{
    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var EventEmittingService
     */
    protected $eventEmittingService;

    /**
     * @Flow\Inject(lazy=FALSE)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration("eventLog.monitorEntities")
     * @var array
     */
    protected $monitorEntitiesSetting;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $logger;

    /**
     * Dummy method which is called in a prePersist signal. If we remove that, this object is never instantiated and thus
     * cannot hook into the Doctrine EntityManager.
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
                    $this->initializeAccountIdentifier();
                    $data = array();
                    foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
                        $data[$key] = Utility::evaluateEelExpression($eelExpression, $this->eelEvaluator, array('entity' => $entity));
                    }

                    $event = $this->eventEmittingService->emit($entityMonitoringConfiguration['events']['created'], $data);
                    $unitOfWork->computeChangeSet($entityManager->getClassMetadata(Event::class), $event);
                }
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $className = get_class($entity);
            if (isset($this->monitorEntitiesSetting[$className])) {
                $entityMonitoringConfiguration = $this->monitorEntitiesSetting[$className];

                if (isset($entityMonitoringConfiguration['events']['deleted'])) {
                    $this->initializeAccountIdentifier();
                    $data = array();
                    foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
                        $data[$key] = Utility::evaluateEelExpression($eelExpression, $this->eelEvaluator, array('entity' => $entity));
                    }

                    $event = $this->eventEmittingService->emit($entityMonitoringConfiguration['events']['deleted'], $data);
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
