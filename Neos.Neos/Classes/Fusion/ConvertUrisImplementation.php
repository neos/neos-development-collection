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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
use Neos\Neos\Fusion\Cache\CacheTag;
use Psr\Log\LoggerInterface;

/**
 * A Fusion Object that converts link references in the format "<type>://<UUID>" to proper URIs
 *
 * Right now node://<UUID> and asset://<UUID> are supported URI schemes.
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris
 *
 * The optional property ``forceConversion`` can be used to have the links converted even when not
 * rendering the live workspace. This is used for links that are not inline editable (for
 * example links on images)::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris {
 *     forceConversion = true
 *   }
 *
 * The optional property ``externalLinkTarget`` can be modified to disable or change the target attribute of the
 * link tag for links to external targets::
 *
 *   prototype(Neos.Neos:ConvertUris) {
 *     externalLinkTarget = '_blank'
 *     resourceLinkTarget = '_blank'
 *   }
 *
 * The optional property ``absolute`` can be used to convert node uris to absolute links::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertUris {
 *     absolute = true
 *   }
 */
class ConvertUrisImplementation extends AbstractFusionObject
{
    public const PATTERN_SUPPORTED_URIS
        = '/(node|asset):\/\/([a-z0-9\-]+|([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

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
     * Convert URIs matching a supported scheme with generated URIs
     *
     * If the workspace of the current node context is not live, no replacement will be done unless forceConversion is
     * set. This is needed to show the editable links with metadata in the content module.
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
                get_debug_type($text)
            ), 1382624087);
        }

        $renderingMode = $this->runtime->fusionGlobals->get('renderingMode');
        assert($renderingMode instanceof RenderingMode);
        if ($renderingMode->isEdit && $this->fusionValue('forceConversion') !== true) {
            return $text;
        }

        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->subgraphIdentity->contentRepositoryId
        );

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromNode($node);

        $unresolvedUris = [];
        $absolute = $this->fusionValue('absolute');

        $processedContent = preg_replace_callback(self::PATTERN_SUPPORTED_URIS, function (array $matches) use ($contentRepository, $nodeAddress, &$unresolvedUris, $absolute) {
            $resolvedUri = null;
            switch ($matches[1]) {
                case 'node':
                    $nodeAddress = $nodeAddress->withNodeAggregateId(
                        NodeAggregateId::fromString($matches[2])
                    );
                    $uriBuilder = new UriBuilder();
                    $possibleRequest = $this->runtime->fusionGlobals->get('request');
                    if ($possibleRequest instanceof ActionRequest) {
                        $uriBuilder->setRequest($possibleRequest);
                    } else {
                        // unfortunately, the uri-builder always needs a request at hand and cannot build uris without
                        // even, if the default param merging would not be required
                        // this will improve with a reformed uri building:
                        // https://github.com/neos/flow-development-collection/pull/2744
                        $uriBuilder->setRequest(
                            ActionRequest::fromHttpRequest(ServerRequest::fromGlobals())
                        );
                    }
                    $uriBuilder->setCreateAbsoluteUri($absolute);
                    try {
                        $resolvedUri = (string)NodeUriBuilder::fromUriBuilder($uriBuilder)->uriFor($nodeAddress);
                    } catch (NoMatchingRouteException) {
                        $this->systemLogger->info(sprintf('Could not resolve "%s" to a live node uri. Arguments: %s', $matches[0], json_encode($uriBuilder->getLastArguments())), LogEnvironment::fromMethodName(__METHOD__));
                    }
                    $this->runtime->addCacheTag(
                        CacheTag::forDynamicNodeAggregate($contentRepository->id, $nodeAddress->contentStreamId, NodeAggregateId::fromString($matches[2]))->value
                    );
                    break;
                case 'asset':
                    $asset = $this->assetRepository->findByIdentifier($matches[2]);
                    if ($asset instanceof AssetInterface) {
                        $resolvedUri = $this->resourceManager->getPublicPersistentResourceUri(
                            $asset->getResource()
                        );
                    }
                    break;
            }

            if ($resolvedUri === null) {
                $unresolvedUris[] = $matches[0];
                return $matches[0];
            }

            return $resolvedUri;
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
     * Replace the target attribute of link tags in processedContent with the target
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
