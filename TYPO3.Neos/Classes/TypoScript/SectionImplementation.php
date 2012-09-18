<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * TypoScript object for specific sections, which also renders a "create-new-content" button
 * when not being in live workspace.
 */
class SectionImplementation extends \TYPO3\TypoScript\TypoScriptObjects\CollectionImplementation {

	/**
	 * The identifier of the section Node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @return string the identifier of the section node which shall be rendered
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * @param string $nodePath the identifier of the section node which shall be rendered
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Render the list of nodes, and if there are none and we are not inside the live
	 * workspace, render a button to create new content.
	 *
	 * @throws \TYPO3\TYPO3\Exception
	 * @return string
	 */
	public function evaluate() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		$node = $currentContext['node'];
		$output = parent::evaluate();
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_TYPO3_Backend_BackendController');
		} catch (\TYPO3\FLOW3\Security\Exception\AccessDeniedException $e) {
			return $output;
		}

		if ($node->getContentType()->isOfType('TYPO3.TYPO3:Section')) {
			$sectionNode = $node;
		} else {
			$sectionNode = $node->getNode($this->getNodePath());

			if ($sectionNode === NULL && $this->nodeRepository->getContext()->getWorkspaceName() !== 'live') {
					/**
					 * In case the user created a new page, this page does not have the necessary sections created yet.
					 * The problem is that we only know during TypoScript rendering which sections we expect to have
					 * on a certain page; as it is only stored in the "nodePath" property of this Section TypoScript object.
					 *
					 * Thus, as a workaround, we create new section nodes as we need them during rendering, although we
					 * know it is ugly.
					 */
				$sectionNode = $node->createNode($this->getNodePath(), $this->contentTypeManager->getContentType('TYPO3.TYPO3:Section'));
			}
		}

		if ($sectionNode === NULL) {
				// It might still happen that there is no section node on the page,
				// f.e. when we are in live workspace. In this case, we just silently
				// return what we have so far.
			return $output;
		}

		return sprintf('<div about="%s" typeof="typo3:%s" rel="typo3:content-collection" class="t3-contentsection"><script type="text/x-typo3" property="typo3:_typoscriptPath">%s</script><script type="text/x-typo3" property="typo3:__workspacename">%s</script>%s</div>', $sectionNode->getContextPath(), 'TYPO3.TYPO3:Section', $this->path, $sectionNode->getWorkspace()->getName(), $output);
	}
}
?>