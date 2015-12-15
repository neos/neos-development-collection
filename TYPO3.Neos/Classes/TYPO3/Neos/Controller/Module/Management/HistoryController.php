<?php
namespace TYPO3\Neos\Controller\Module\Management;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\EventLog\Domain\Model\Event;
use TYPO3\Neos\EventLog\Domain\Model\EventsOnDate;
use TYPO3\Neos\EventLog\Domain\Model\NodeEvent;
use TYPO3\Neos\EventLog\Domain\Repository\EventRepository;

class HistoryController extends AbstractModuleController
{
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
    public function indexAction($offset = 0, $limit = 10)
    {
        $events = $this->eventRepository->findRelevantEvents($offset, $limit + 1)->toArray();

        if (count($events) == $limit + 1) {
            $events = array_slice($events, 0, $limit);

            $nextPage = $this
                ->controllerContext
                ->getUriBuilder()
                ->setCreateAbsoluteUri(true)
                ->uriFor('Index', array('offset' => $offset + $limit), 'History', 'TYPO3.Neos');
        } else {
            $nextPage = null;
        }

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
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setTypoScriptPathPattern('resource://TYPO3.Neos/Private/TypoScript/Backend');
    }
}
