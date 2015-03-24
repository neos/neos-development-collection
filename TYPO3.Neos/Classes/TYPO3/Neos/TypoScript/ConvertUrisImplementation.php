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
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * A TypoScript Object that converts link references in the format "<type>://<UUID>" to proper URIs
 *
 * Right now node://<UUID> and asset://<UUID> are supported URI schemes.
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = TYPO3.Neos:ConvertUris
 *
 * The optional property ``forceConversion`` can be used to have the links converted even when not
 * rendering the live workspace. This is used for links that are not inline editable (for
 * example links on images)::
 *
 *   someTextProperty.@process.1 = TYPO3.Neos:ConvertUris {
 *     forceConversion = true
 *   }
 *
 * The optional property ``externalLinkTarget`` can be modified to disable or change the target attribute of the
 * link tag for links to external targets::
 *
 *   prototype(TYPO3.Neos:ConvertUris) {
 *     externalLinkTarget = '_blank'
 *     resourceLinkTarget = '_blank'
 *   }
 */
class ConvertUrisImplementation extends AbstractTypoScriptObject {

	/**
	 * @Flow\Inject
	 * @var LinkingService
	 */
	protected $linkingService;

	/**
	 * Convert URIs matching a supported scheme with generated URIs
	 *
	 * If the workspace of the current node context is not live, no replacement will be done unless forceConversion is
	 * set. This is needed to show the editable links with metadata in the content module.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function evaluate() {
		$text = $this->tsValue('value');

		if ($text === '' || $text === NULL) {
			return '';
		}

		if (!is_string($text)) {
			throw new Exception(sprintf('Only strings can be processed by this TypoScript object, given: "%s".', gettype($text)), 1382624080);
		}

		$node = $this->tsValue('node');

		if (!$node instanceof NodeInterface) {
			throw new Exception(sprintf('The current node must be an instance of NodeInterface, given: "%s".', gettype($text)), 1382624087);
		}

		if ($node->getContext()->getWorkspace()->getName() !== 'live' && !($this->tsValue('forceConversion'))) {
			return $text;
		}

		$unresolvedUris = array();
		$linkingService = $this->linkingService;
		$controllerContext = $this->tsRuntime->getControllerContext();

		$processedContent = preg_replace_callback(LinkingService::PATTERN_SUPPORTED_URIS, function(array $matches) use ($node, $linkingService, $controllerContext, &$unresolvedUris) {
			switch ($matches[1]) {
				case 'node':
					$resolvedUri = $linkingService->resolveNodeUri($matches[0], $node, $controllerContext);
					break;
				case 'asset':
					$resolvedUri = $linkingService->resolveAssetUri($matches[0]);
					break;
				default:
					$resolvedUri = NULL;
			}

			if ($resolvedUri === NULL) {
				$unresolvedUris[] = $matches[0];
				return $matches[0];
			}

			return $resolvedUri;
		}, $text);

		if ($unresolvedUris !== array()) {
			$processedContent = preg_replace('/<a[^>]* href="(node|asset):\/\/[^"]+"[^>]*>(.*?)<\/a>/', '$2', $processedContent);
			$processedContent = preg_replace(LinkingService::PATTERN_SUPPORTED_URIS, '', $processedContent);
		}

		$processedContent = $this->replaceLinkTargets($processedContent);

		return $processedContent;
	}

	/**
	 * Replace the target attribute of link tags in processedContent with the target
	 * specified by externalLinkTarget and resourceLinkTarget options.
	 *
	 * @param string $processedContent
	 * @return string
	 */
	protected function replaceLinkTargets($processedContent) {
		$externalLinkTarget = trim($this->tsValue('externalLinkTarget'));
		$resourceLinkTarget = trim($this->tsValue('resourceLinkTarget'));
		if ($externalLinkTarget === '' && $resourceLinkTarget === '') {
			return $processedContent;
		}
		$controllerContext = $this->tsRuntime->getControllerContext();
		$host = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();
		$processedContent = preg_replace_callback(
			'~<a.*?href="(.*?)".*?>~i',
			function ($matches) use ($externalLinkTarget, $resourceLinkTarget, $host) {
				list($linkText, $linkHref) = $matches;
				$uriHost = parse_url($linkHref, PHP_URL_HOST);
				$target = NULL;
				if ($externalLinkTarget !== '' && is_string($uriHost) && $uriHost !== $host) {
					$target = $externalLinkTarget;
				}
				if ($resourceLinkTarget !== '' && strpos($linkHref, '_Resources') !== FALSE) {
					$target = $resourceLinkTarget;
				}
				if ($target === NULL) {
					return $linkText;
				}
				if (preg_match_all('~target="(.*?)~i', $linkText, $targetMatches)) {
					return preg_replace('/target=".*?"/', sprintf('target="%s"', $target), $linkText);
				}
				return str_replace('<a', sprintf('<a target="%s"', $target), $linkText);
			},
			$processedContent
		);
		return $processedContent;
	}

}
