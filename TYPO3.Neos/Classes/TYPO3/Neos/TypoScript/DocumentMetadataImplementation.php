<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Neos\Service\ContentElementWrappingService;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * TypoScript object for rendering the document metadata that is required for the backend to work
 */
class DocumentMetadataImplementation extends AbstractTypoScriptObject {

	/**
	 * @Flow\Inject
	 * @var AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;


	/**
	 * @Flow\Inject
	 * @var ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * @return string
	 */
	public function evaluate() {
		$content = $this->tsValue('value');
		if (!$this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess')) {
			return $content;
		}

		/** @var $node NodeInterface */
		$documentNode = $this->tsValue('documentNode');

		/** @var $contentContext ContentContext */
		$contentContext = $documentNode->getContext();
		if ($contentContext->getWorkspaceName() === 'live') {
			return $content;
		}

		$tagName = $this->tsValue('tagName');
		$idAttribute = $this->tsValue('id');

		if (strlen($content) > 0 || $this->tsValue('forceClosingTag') === TRUE) {
			$content = sprintf('<%s id="%s">%s</%s>', $tagName, htmlspecialchars($idAttribute), $content, $tagName);
		} else {
			$content = sprintf('<%s id="%s" />', $tagName, htmlspecialchars($idAttribute), $content, $tagName);
		}
		return $this->contentElementWrappingService->wrapContentObject($documentNode, $this->path, $content);
	}
}
