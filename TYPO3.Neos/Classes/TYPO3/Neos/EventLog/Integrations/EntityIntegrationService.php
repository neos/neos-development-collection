<?php
namespace TYPO3\Neos\EventLog\Integrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Event\OnFlushEventArgs;
use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\Utility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;

/**
 * Monitors entity changes
 *
 * @Flow\Scope("singleton")
 */
class EntityIntegrationService extends AbstractIntegrationService {

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
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
	 * @Flow\Inject(setting="eventLog.monitorEntities")
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
	public function dummyMethodToEnsureInstanceExists() {
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
	 * @throws \TYPO3\Eel\Exception
	 */
	public function onFlush(OnFlushEventArgs $eventArgs) {
		$entityManager = $eventArgs->getEntityManager();
		$unitOfWork = $entityManager->getUnitOfWork();

		foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
			$className = get_class($entity);
			$this->logger->log('onFlush ' . $className, LOG_DEBUG);
			if (isset($this->monitorEntitiesSetting[$className])) {
				$entityMonitoringConfiguration = $this->monitorEntitiesSetting[$className];

				if (isset($entityMonitoringConfiguration['events']['created'])) {
					$this->initializeAccountIdentifier();
					$data = array();
					foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
						$data[$key] = Utility::evaluateEelExpression($eelExpression, $this->eelEvaluator, array('entity' => $entity));
					}

					$event = $this->eventEmittingService->emit($entityMonitoringConfiguration['events']['created'], $data);
					$unitOfWork->computeChangeSet($entityManager->getClassMetadata('TYPO3\Neos\EventLog\Domain\Model\Event'), $event);
				}
			}
		}

		foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
		}

		foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
			$className = get_class($entity);
			$this->logger->log('onFlush ' . $className, LOG_DEBUG);
			if (isset($this->monitorEntitiesSetting[$className])) {
				$entityMonitoringConfiguration = $this->monitorEntitiesSetting[$className];

				if (isset($entityMonitoringConfiguration['events']['deleted'])) {
					$this->initializeAccountIdentifier();
					$data = array();
					foreach ($entityMonitoringConfiguration['data'] as $key => $eelExpression) {
						$data[$key] = Utility::evaluateEelExpression($eelExpression, $this->eelEvaluator, array('entity' => $entity));
					}

					$event = $this->eventEmittingService->emit($entityMonitoringConfiguration['events']['deleted'], $data);
					$unitOfWork->computeChangeSet($entityManager->getClassMetadata('TYPO3\Neos\EventLog\Domain\Model\Event'), $event);
				}
			}
		}

		foreach ($unitOfWork->getScheduledCollectionDeletions() as $col) {

		}

		foreach ($unitOfWork->getScheduledCollectionUpdates() as $col) {

		}
	}
}