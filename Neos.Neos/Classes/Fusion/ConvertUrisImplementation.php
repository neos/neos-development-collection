<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Neos\Neos\Fusion\Cache\CacheTag;
use Psr\Log\LoggerInterface;

/**
 * A Fusion object that converts URIs in the format "<type>://<identifier>" to URLs
 *
 * Currently, node://<identifier> and asset://<identifier> are supported URI schemes.
 *
 * Anchor tags with an unresolvable URI in their href attribute (for example because the target
 * node is disabled) are replaced by their inner content. Unresolvable URIs
 * outside of anchor tags are removed entirely.
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris
 *
 * The optional property ``forceConversion`` can be used to have the URIs converted even when
 * rendering in edit mode. This is used for properties that are not inline editable (for
 * example the link property of an image element, managed in the inspector)::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris {
 *     forceConversion = true
 *   }
 *
 * The optional property ``externalLinkTarget`` can be modified to disable or change the target attribute of the
 * anchor tag for links to external targets::
 *
 *   prototype(Neos.Neos:ConvertUris) {
 *     externalLinkTarget = '_blank'
 *     resourceLinkTarget = '_blank'
 *   }
 *
 * Anchor tags pointing to an external host additionally get ``rel="noopener external"``, which can
 * be disabled with the ``setNoOpener`` and ``setExternal`` options.
 *
 * The optional property ``absolute`` can be used to resolve URIs to absolute URLs::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris {
 *     absolute = true
 *   }
 */
class ConvertUrisImplementation extends AbstractFusionObject
{
    private const PATTERN_SUPPORTED_URIS = '/(node|asset):\/\/([a-z0-9\-]+)/';

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var NodeUriBuilderFactory
     */
    protected $nodeUriBuilderFactory;

    /**
     * Convert URIs matching a supported scheme to their respective URLs and
     * replace anchor tags whose URI cannot be resolved by their inner content.
     * Additionally, the target and rel attributes of anchor tags are adjusted
     * for external and resource links.
     *
     * When rendering in edit mode, no replacement will be done unless forceConversion is set.
     * This is needed to show the editable links with metadata in the content module.
     *
     * @return string
     * @throws NeosException
     */
    public function evaluate()
    {
        $text = $this->fusionValue('value');

        if ($text === '' || $text === null) {
            return '';
        }

        if (!is_string($text)) {
            throw new NeosException(sprintf(
                'Only strings can be processed by this Fusion object, given: "%s".',
                get_debug_type($text)
            ), 1382624080);
        }

        $node = $this->fusionValue('node');

        if (!$node instanceof Node) {
            throw new NeosException(sprintf(
                'The current node must be an instance of Node, given: "%s".',
                get_debug_type($node)
            ), 1382624087);
        }

        $renderingMode = $this->runtime->fusionGlobals->get('renderingMode');
        assert($renderingMode instanceof RenderingMode);
        if ($renderingMode->isEdit && $this->fusionValue('forceConversion') !== true) {
            return $text;
        }

        $nodeAddress = NodeAddress::fromNode($node);

        $unresolvedUris = [];
        $frontendRoutingOptions = $this->fusionValue('absolute') ? Options::createForceAbsolute() : Options::createEmpty();

        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($possibleRequest);
            $format = $possibleRequest->getFormat();
            if ($format && $format !== 'html') {
                $frontendRoutingOptions = $frontendRoutingOptions->withCustomFormat($format);
            }
        } else {
            // unfortunately, the uri-builder always needs a request at hand and cannot build uris without it
            // this will improve with a reformed uri building:
            // https://github.com/neos/flow-development-collection/issues/3354
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest(ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()));
        }

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);

        $processedContent = preg_replace_callback(self::PATTERN_SUPPORTED_URIS, function (array $uriMatches) use ($nodeAddress, &$unresolvedUris, $nodeUriBuilder, $frontendRoutingOptions, $subgraph) {
            [$matchedUri, $matchedUriScheme, $matchedUriIdentifier] = $uriMatches;
            $resolvedUrl = null;
            switch ($matchedUriScheme) {
                case 'node':
                    $targetNodeAggregateId = NodeAggregateId::tryFromString($matchedUriIdentifier);

                    if ($targetNodeAggregateId === null) {
                        $this->systemLogger->info(sprintf('Could not resolve "%s" because the identifier is not a valid node aggregate id.', $matchedUri), LogEnvironment::fromMethodName(__METHOD__));
                        break;
                    }

                    $nodeAddress = $nodeAddress->withAggregateId($targetNodeAggregateId);

                    // Note that routing intentionally builds URLs for disabled nodes as well
                    // (see https://github.com/neos/neos-development-collection/pull/4363).
                    // So we need to check whether the target node is accessible in the subgraph of the current node.
                    if ($subgraph->findNodeById($targetNodeAggregateId) === null) {
                        $this->systemLogger->info(sprintf('Could not resolve "%s" because the target node is not accessible in the subgraph of the current node.', $matchedUri), LogEnvironment::fromMethodName(__METHOD__));
                    } else {
                        try {
                            $resolvedUrl = (string)$nodeUriBuilder->uriFor($nodeAddress, $frontendRoutingOptions);
                        } catch (NoMatchingRouteException) {
                            // todo log also arguments?
                            $this->systemLogger->warning(sprintf('Could not resolve "%s" to a URL.', $matchedUri), LogEnvironment::fromMethodName(__METHOD__));
                        }
                    }
                    $this->runtime->addCacheTag(
                        CacheTag::forDynamicNodeAggregate($nodeAddress->contentRepositoryId, $nodeAddress->workspaceName, $nodeAddress->aggregateId)->value
                    );
                    break;
                case 'asset':
                    $asset = $this->assetRepository->findByIdentifier($matchedUriIdentifier);
                    if ($asset instanceof AssetInterface) {
                        $resolvedUrl = $this->resourceManager->getPublicPersistentResourceUri(
                            $asset->getResource()
                        );
                    }
                    break;
            }

            if ($resolvedUrl === null) {
                $unresolvedUris[] = $matchedUri;
                return $matchedUri;
            }

            return (string)$resolvedUrl;
        }, $text);
        assert($processedContent !== null, 'preg_* error');

        if ($unresolvedUris !== []) {
            $processedContent = preg_replace('/<a(?:\s+[^>]*)?\s+href="(node|asset):\/\/[^"]+"[^>]*>(.*?)<\/a>/', '$2', $processedContent);
            assert($processedContent !== null, 'preg_* error');
            $processedContent = preg_replace(self::PATTERN_SUPPORTED_URIS, '', $processedContent);
            assert($processedContent !== null, 'preg_* error');
        }

        $processedContent = $this->replaceLinkTargets($processedContent);

        return $processedContent;
    }

    /**
     * Replace the target attribute of anchor tags in processedContent with the target
     * specified by externalLinkTarget and resourceLinkTarget options.
     * Additionally set rel="noopener external" for external links.
     *
     * @param string $processedContent
     * @return string
     */
    protected function replaceLinkTargets($processedContent)
    {
        $setNoOpener = $this->fusionValue('setNoOpener');
        $setExternal = $this->fusionValue('setExternal');
        $externalLinkTarget = \trim((string)$this->fusionValue('externalLinkTarget'));
        $resourceLinkTarget = \trim((string)$this->fusionValue('resourceLinkTarget'));
        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $host = $possibleRequest->getHttpRequest()->getUri()->getHost();
        } else {
            $host = null;
        }
        $processedContent = \preg_replace_callback(
            '~<a\s+.*?href="(.*?)".*?>~i',
            static function ($matches) use ($externalLinkTarget, $resourceLinkTarget, $host, $setNoOpener, $setExternal) {
                [$linkText, $linkHref] = $matches;
                $uriHost = \parse_url($linkHref, PHP_URL_HOST);
                $target = null;
                $isExternalLink = \is_string($uriHost) && $uriHost !== $host;

                if ($externalLinkTarget && $externalLinkTarget !== '' && $isExternalLink) {
                    $target = $externalLinkTarget;
                }
                if ($resourceLinkTarget && $resourceLinkTarget !== '' && str_contains($linkHref, '_Resources')) {
                    $target = $resourceLinkTarget;
                }
                if ($isExternalLink && $setNoOpener) {
                    $linkText = self::setAttribute('rel', 'noopener', $linkText);
                }
                if ($isExternalLink && $setExternal) {
                    $linkText = self::setAttribute('rel', 'external', $linkText);
                }
                if (is_string($target) && $target !== '') {
                    return self::setAttribute('target', $target, $linkText);
                }
                return $linkText;
            },
            $processedContent
        );
        assert($processedContent !== null, 'preg_* error');
        return $processedContent;
    }


    /**
     * Set or add value to the a attribute
     *
     * @param string $attribute The attribute, ('target' or 'rel')
     * @param string $value The value of the attribute to add
     * @param string $content The content to parse
     * @return string
     */
    private static function setAttribute(string $attribute, string $value, string $content): string
    {
        // The attribute is already set
        if (\preg_match_all('~\s+' . $attribute . '="(.*?)~i', $content, $matches)) {
            // If the attribute is target or the value is already set, leave the attribute as it is
            if ($attribute === 'target' || \preg_match('~' . $attribute . '=".*?' . $value . '.*?"~i', $content)) {
                return $content;
            }
            // Add the attribute to the list
            $result = \preg_replace('/' . $attribute . '="(.*?)"/', sprintf('%s="$1 %s"', $attribute, $value), $content);
            assert($result !== null, 'preg_* error');
            return $result;
        }

        // Add the missing attribute with the value
        return \str_replace('<a', sprintf('<a %s="%s"', $attribute, $value), $content);
    }
}
