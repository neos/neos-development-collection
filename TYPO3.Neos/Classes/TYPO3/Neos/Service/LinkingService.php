<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A service for creating URIs pointing to nodes and assets.
 *
 * The target node can be provided as string or as a Node object; if not specified
 * at all, the generated URI will refer to the current document node inside the TypoScript context.
 *
 * When specifying the ``node`` argument as string, the following conventions apply:
 *
 * *``node`` starts with ``/``:*
 * The given path is an absolute node path and is treated as such.
 * Example: ``/sites/acmecom/home/about/us``
 *
 * *``node`` does not start with ``/``:*
 * The given path is treated as a path relative to the current node.
 * Examples: given that the current node is ``/sites/acmecom/products/``,
 * ``stapler`` results in ``/sites/acmecom/products/stapler``,
 * ``../about`` results in ``/sites/acmecom/about/``,
 * ``./neos/info`` results in ``/sites/acmecom/products/neos/info``.
 *
 * *``node`` starts with a tilde character (``~``):*
 * The given path is treated as a path relative to the current site node.
 * Example: given that the current node is ``/sites/acmecom/products/``,
 * ``~/about/us`` results in ``/sites/acmecom/about/us``,
 * ``~`` results in ``/sites/acmecom``.
 *
 * @Flow\Scope("singleton")
 */
class LinkingService {

	/**
	 * Pattern to match supported URIs.
	 *
	 * @var string
	 */
	const PATTERN_SUPPORTED_URIS = '/(node|asset):\/\/(([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 * @Flow\Inject
	 */
	protected $resourcePublisher;

	/**
	 * @Flow\Inject
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @param string|Uri $uri
	 * @return boolean
	 */
	public function hasSupportedScheme($uri) {
		if ($uri instanceof Uri) {
			$uri = (string)$uri;
		}
		return preg_match(self::PATTERN_SUPPORTED_URIS, $uri) === 1;
	}

	/**
	 * @param string|Uri $uri
	 * @return string
	 */
	public function getScheme($uri) {
		if ($uri instanceof Uri) {
			return $uri->getScheme();
		}

		if (preg_match(self::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Resolves a given node:// URI to a "normal" HTTP(S) URI for the addressed node.
	 *
	 * @param string|Uri $uri
	 * @param NodeInterface $contextNode
	 * @param ControllerContext $controllerContext
	 * @return string
	 * @throws \TYPO3\Flow\Mvc\Routing\Exception\MissingActionNameException
	 */
	public function resolveNodeUri($uri, NodeInterface $contextNode, ControllerContext $controllerContext) {
		return $this->createNodeUri($controllerContext, $this->convertUriToObject($uri, $contextNode));
	}

	/**
	 * Resolves a given asset:// URI to a "normal" HTTP(S) URI for the addressed asset's resource.
	 *
	 * @param string|Uri $uri
	 * @return string
	 */
	public function resolveAssetUri($uri) {
		$targetObject = $this->convertUriToObject($uri);
		return $this->resourcePublisher->getPersistentResourceWebUri($targetObject->getResource());
	}

	/**
	 * Return the object the URI addresses or NULL.
	 *
	 * @param string|Uri $uri
	 * @param NodeInterface $contextNode
	 * @return object|NULL
	 */
	public function convertUriToObject($uri, NodeInterface $contextNode = NULL) {
		if ($uri instanceof Uri) {
			$uri = (string)$uri;
		}

		if (preg_match(self::PATTERN_SUPPORTED_URIS, $uri, $matches) === 1) {
			switch ($matches[1]) {
				case 'node':
					if ($contextNode === NULL) {
						throw new \RuntimeException('node:// URI conversion requires a context node to be passed', 1409734235);
					};

					return $contextNode->getContext()->getNodeByIdentifier($matches[2]);
				case 'asset':
					return $this->assetRepository->findByIdentifier($matches[2]);
			}
		}

		return NULL;
	}

	/**
	 * Renders the URI.
	 *
	 * @param ControllerContext $controllerContext
	 * @param mixed $node A node object or a string node path, if a relative path is provided the baseNode arguments is required
	 * @param NodeInterface $baseNode
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @param array $arguments Additional arguments to be passed to the UriBuilder (for example pagination parameters)
	 * @param string $section
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
	 * @return string The rendered URI or NULL if no URI could be resolved for the given node
	 * @throws \TYPO3\Neos\Exception
	 * @throws \InvalidArgumentException
	 */
	public function createNodeUri(ControllerContext $controllerContext, $node = NULL, NodeInterface $baseNode = NULL, $format = NULL, $absolute = FALSE, array $arguments = array(), $section = '', $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array()) {
		if (!($node instanceof NodeInterface || is_string($node) || $baseNode instanceof NodeInterface)) {
			throw new \InvalidArgumentException('Expected NodeInterface, string for the node argument or a NoteInterface for the baseNode argument.', 1373101025);
		}

		if (is_string($node) && $node !== '') {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $node, $matches);
			if (isset($matches['WorkspaceName']) && $matches['WorkspaceName'] !== '') {
				$node = $this->propertyMapper->convert($node, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
			} else {
				if ($baseNode === NULL) {
					throw new NeosException('The baseNode argument is required for linking to nodes with a relative path.', 1407879905);
				}
				$contentContext = $baseNode->getContext();

				if ($node === '~' || $node === '~/') {
					$node = $contentContext->getCurrentSiteNode();
				} elseif (substr($node, 0, 2) === '~/') {
					$node = $contentContext->getCurrentSiteNode()->getNode(substr($node, 2));
				} else {
					if (substr($node, 0, 1) === '/') {
						$node = $contentContext->getNode($node);
					} else {
						$node = $baseNode->getNode($node);
					}
				}
			}
		} elseif (!$node instanceof NodeInterface) {
			$node = $baseNode;
		}

		if (!$node instanceof NodeInterface) {
			return NULL;
		}

		$request = $controllerContext->getRequest()->getMainRequest();

		$uriBuilder = clone $controllerContext->getUriBuilder();
		$uriBuilder->setRequest($request);
		return $uriBuilder
			->reset()
			->setSection($section)
			->setCreateAbsoluteUri($absolute)
			->setArguments($arguments)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->setFormat($format ?: $request->getFormat())
			->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
	}
}