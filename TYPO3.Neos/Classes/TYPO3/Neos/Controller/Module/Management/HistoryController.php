<?php
namespace TYPO3\Neos\Controller\Module\Management;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\EventLog\Domain\Model\Event;
use TYPO3\Neos\EventLog\Domain\Model\EventsOnDate;
use TYPO3\Neos\EventLog\Domain\Model\NodeEvent;
use TYPO3\Neos\EventLog\Domain\Repository\EventRepository;

class HistoryController extends AbstractModuleController {

	/**
	 * @Flow\Inject
	 * @var EventRepository
	 */
	protected $eventRepository;

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\TypoScript\View\TypoScriptView';

	/**
	 * Show event overview.
	 * @param integer $offset
	 * @param integer $limit
	 * @return void
	 */
	public function indexAction($offset = 0, $limit = 10) {

		$events = $this->eventRepository->findRelevantEvents($offset, $limit)->toArray();

		$eventsByDate = array();
		foreach ($events as $event) {
			if ($event instanceof NodeEvent && $event->getWorkspaceName() !== 'live') {
				continue;
			}
			/* @var $event Event */
			$day = $event->getTimestamp()->format('Y-m-d');
			if (!isset($eventsByDate[$day])) {
				$eventsByDate[$day] = new EventsOnDate($event->getTimestamp());
			}

			/* @var $eventsOnThisDay EventsOnDate */
			$eventsOnThisDay = $eventsByDate[$day];
			$eventsOnThisDay->add($event);
		}

		if (count($events) >= $limit) {
			$nextPage = $this->controllerContext->getUriBuilder()->setCreateAbsoluteUri(TRUE)->uriFor('Index', array('offset' => $offset + $limit), 'History', 'TYPO3.Neos');
		} else {
			$nextPage = NULL;
		}

		$this->view->assignMultiple(array(
			'eventsByDate' => $eventsByDate,
			'nextPage' => $nextPage
		));
	}

	/**
	 * Simply sets the TypoScript path pattern on the view.
	 *
	 * @param ViewInterface $view
	 * @return void
	 */
	protected function initializeView(ViewInterface $view) {
		parent::initializeView($view);
		$view->setTypoScriptPathPattern('resource://TYPO3.Neos/Private/TypoScript/Backend');
	}
}