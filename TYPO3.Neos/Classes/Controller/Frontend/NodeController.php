<?php
namespace TYPO3\TYPO3\Controller\Frontend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Controller for displaying nodes in the frontend
 *
 * @FLOW3\Scope("singleton")
 */
class NodeController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var array
	 */
	protected $supportedFormats = array('html');

	/**
	 * @var array
	 */
	protected $defaultViewObjectName = 'TYPO3\TYPO3\View\TypoScriptView';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string View output for the specified node
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if (!$node->isAccessible()) {
			$this->authenticationManager->authenticate();
		}
		if (!$node->isAccessible() && !$this->nodeRepository->getContext()->isInaccessibleContentShown()) {
			$this->throwStatus(403);
		}
		if (!$node->isVisible() && !$this->nodeRepository->getContext()->isInvisibleContentShown()) {
			$this->throwStatus(404);
		}
		if ($node->getContentType() === 'TYPO3.TYPO3:Shortcut') {
			while ($node->getContentType() === 'TYPO3.TYPO3:Shortcut') {
				$childNodes = $node->getChildNodes('TYPO3.TYPO3:Page,TYPO3.TYPO3:Shortcut');
				$node = current($childNodes);
			}
			$this->redirect('show', NULL, NULL, array('node' => $node));
		}

		$this->nodeRepository->getContext()->setCurrentNode($node);
		$this->view->assign('value', $node);

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);
	}

}
?>