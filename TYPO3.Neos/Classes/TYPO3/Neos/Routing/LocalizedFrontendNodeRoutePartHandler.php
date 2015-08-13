<?php
namespace TYPO3\Neos\Routing;

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
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Routing\Exception\NoSuchLanguageException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A frontend node route part handler that handles a "languages" dimension
 *
 * It always matches a language identifier in the first path segment for now, for example:
 *
 *   "all/features/try-me" matches the node with path "features/try-me" with the languages configured for language chain "all"
 */
class LocalizedFrontendNodeRoutePartHandler extends FrontendNodeRoutePartHandler {

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	/**
	 * Prepend the Language Chain Identifier to the route path.
	 *
	 * This takes the node's context "languages" dimension value into account.
	 *
	 * @param NodeInterface $siteNode
	 * @param NodeInterface $node
	 * @return string
	 */
	protected function resolveRoutePathForNode($siteNode, $node) {
		$routePath = parent::resolveRoutePathForNode($siteNode, $node);

		$dimensions = $node->getContext()->getDimensions();
		$uriSegment = $this->getUriSegmentForLanguages($dimensions);
		$routePath = $uriSegment . '/' . $routePath;

		return $routePath;
	}

	/**
	 * Get the language locales from the request path and build a context using these locales and the full context information.
	 *
	 * @param string $requestPath
	 * @return ContentContext
	 */
	protected function buildContextFromRequestPath($requestPath) {
		list($locales, $requestPath) = $this->parseLocalesAndNodePathFromRequestPath($requestPath);

		$contextPathParts = array();
		if ($requestPath !== '' && strpos($requestPath, '@') !== FALSE) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $requestPath, $contextPathParts);
		}
		$workspaceName = isset($contextPathParts['WorkspaceName']) && $contextPathParts['WorkspaceName'] !== '' ? $contextPathParts['WorkspaceName'] : 'live';
		return $this->buildContextFromWorkspaceNameAndLanguageLocales($workspaceName, $locales);
	}

	/**
	 * Strip off the language chain identifier (which is the first part of $requestPath).
	 *
	 * @param string $requestPath
	 * @return string
	 */
	protected function removeContextFromRequestPath($requestPath) {
		list(, $requestPath) = $this->parseLocalesAndNodePathFromRequestPath($requestPath);
		$requestPath = $this->removeContextFromPath($requestPath);
		return $requestPath;
	}

	/**
	 * @param string $workspaceName
	 * @param array $locales
	 * @return ContentContext
	 */
	protected function buildContextFromWorkspaceNameAndLanguageLocales($workspaceName, array $locales) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE,
			'dimensions' => array('languages' => $locales)
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();

		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findOnline()->getFirst();
		}

		return $this->contextFactory->create($contextProperties);
	}

	/**
	 * @param string $requestPath
	 * @return array
	 */
	protected function parseLocalesAndNodePathFromRequestPath($requestPath) {
		preg_match('|^(?<localeIdentifier>[^/]+)?(/(?<nodePath>.*))?|', $requestPath, $matches);
		if (isset($matches['localeIdentifier'])) {
			$locales = $this->getLocalesForUriSegment($matches['localeIdentifier']);
		} else {
			$locales = $this->getLocalesForUriSegment(NULL);
		}
		if (isset($matches['nodePath'])) {
			$requestPath = $matches['nodePath'];
		} else {
			$requestPath = '';
		}

		return array($locales, $requestPath);
	}

	/**
	 * Find the languages dimension values for a URI segment
	 *
	 * If the given URI segment is NULL, the default preset of the "languages" dimension will be used.
	 *
	 * @param string $uriSegment A URI segment of a content dimension preset or NULL if none was given in the route path
	 * @return array A list of locales or NoSuchLanguageException if none could be matched by the given identifier
	 * @throws NoSuchLanguageException
	 */
	protected function getLocalesForUriSegment($uriSegment) {
		if ($uriSegment === NULL) {
			$preset = $this->contentDimensionPresetSource->getDefaultPreset('languages');
		} else {
			$preset = $this->contentDimensionPresetSource->findPresetByUriSegment('languages', $uriSegment);
		}

		if ($preset === NULL) {
			throw new NoSuchLanguageException(sprintf('No content dimension preset for dimension "languages" and uriSegment "%s" found', $uriSegment), 1395827628);
		}
		return $preset['values'];
	}

	/**
	 * Find a URI segment in the content dimension presets for the given "languages" dimension values
	 *
	 * This will do a reverse lookup from actual dimension values to a preset and fall back to the default preset if none
	 * can be found.
	 *
	 * @param array $dimensionValues
	 * @return string
	 * @throws Exception
	 */
	protected function getUriSegmentForLanguages(array $dimensionValues) {
		if (isset($dimensionValues['languages'])) {
			$preset = $this->contentDimensionPresetSource->findPresetByDimensionValues('languages', $dimensionValues['languages']);
			if ($preset === NULL) {
				$preset = $this->contentDimensionPresetSource->getDefaultPreset('languages');
			}
			if (!isset($preset['uriSegment'])) {
				throw new Exception(sprintf('No "uriSegment" configured for content dimension preset "%s" for dimension "languages".', $dimensionValues['identifier']), 1395824520);
			}
			return $preset['uriSegment'];
		}
		throw new Exception('No "languages" dimension found, but it is needed by the LocalizedFrontendNodeRoutePartHandler. Please configure it in Settings.yaml in path "TYPO3.TYPO3CR.contentDimensions".', 1395672860);
	}

}
