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

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Neos\EventSourcedRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Exception as NeosException;

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
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

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
                gettype($text)
            ), 1382624080);
        }

        $node = $this->fusionValue('node');

        if (!$node instanceof NodeInterface) {
            throw new NeosException(sprintf(
                'The current node must be an instance of NodeInterface, given: "%s".',
                gettype($text)
            ), 1382624087);
        }

        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);

        if (!$nodeAddress->isInLiveWorkspace() && !($this->fusionValue('forceConversion'))) {
            return $text;
        }

        $unresolvedUris = [];
        $absolute = $this->fusionValue('absolute');

        $processedContent = preg_replace_callback(
            self::PATTERN_SUPPORTED_URIS,
            function (array $matches) use (&$unresolvedUris, $absolute, $nodeAddress) {
                $resolvedUri = null;
                switch ($matches[1]) {
                    case 'node':
                        $nodeAddress = $nodeAddress->withNodeAggregateIdentifier(
                            NodeAggregateIdentifier::fromString($matches[2])
                        );
                        $uriBuilder = new UriBuilder();
                        $uriBuilder->setRequest($this->runtime->getControllerContext()->getRequest());
                        $uriBuilder->setCreateAbsoluteUri($absolute);

                        // TODO: multi-site .... -> different object than NodeAddress which also contains the CR Identifier.
                        $resolvedUri = (string)NodeUriBuilder::fromUriBuilder($uriBuilder)->uriFor($nodeAddress);
                        $this->runtime->addCacheTag('node', $matches[2]);
                        break;
                    case 'asset':
                        $asset = $this->assetRepository->findByIdentifier($matches[2]);
                        if ($asset instanceof AssetInterface) {
                            $resolvedUri = $this->resourceManager->getPublicPersistentResourceUri(
                                $asset->getResource()
                            );
                            $this->runtime->addCacheTag('asset', $matches[2]);
                        }
                        break;
                }

                if ($resolvedUri === null) {
                    $unresolvedUris[] = $matches[0];
                    return $matches[0];
                }

                return $resolvedUri;
            },
            $text
        ) ?: '';

        if ($unresolvedUris !== []) {
            $processedContent = preg_replace(
                '/<a[^>]* href="(node|asset):\/\/[^"]+"[^>]*>(.*?)<\/a>/',
                '$2',
                $processedContent
            ) ?: '';
            $processedContent = preg_replace(self::PATTERN_SUPPORTED_URIS, '', $processedContent) ?: '';
        }

        $processedContent = $this->replaceLinkTargets($processedContent);

        return $processedContent;
    }

    /**
     * Replace the target attribute of link tags in processedContent with the target
     * specified by externalLinkTarget and resourceLinkTarget options.
     * Additionally set rel="noopener" for links with target="_blank".
     */
    protected function replaceLinkTargets(string $processedContent): string
    {
        $noOpenerString = $this->fusionValue('setNoOpener') ? ' rel="noopener"' : '';
        $externalLinkTarget = trim($this->fusionValue('externalLinkTarget'));
        $resourceLinkTarget = trim($this->fusionValue('resourceLinkTarget'));
        if ($externalLinkTarget === '' && $resourceLinkTarget === '') {
            return $processedContent;
        }
        $controllerContext = $this->runtime->getControllerContext();
        $host = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();
        $processedContent = preg_replace_callback(
            '~<a.*?href="(.*?)".*?>~i',
            function ($matches) use ($externalLinkTarget, $resourceLinkTarget, $host, $noOpenerString) {
                list($linkText, $linkHref) = $matches;
                $uriHost = parse_url($linkHref, PHP_URL_HOST);
                $target = null;
                if ($externalLinkTarget !== '' && is_string($uriHost) && $uriHost !== $host) {
                    $target = $externalLinkTarget;
                }
                if ($resourceLinkTarget !== '' && strpos($linkHref, '_Resources') !== false) {
                    $target = $resourceLinkTarget;
                }
                if ($target === null) {
                    return $linkText;
                }
                if (preg_match_all('~target="(.*?)~i', $linkText, $targetMatches)) {
                    return preg_replace(
                        '/target=".*?"/',
                        sprintf('target="%s"%s', $target, $target === '_blank' ? $noOpenerString : ''),
                        $linkText
                    );
                }
                return str_replace(
                    '<a',
                    sprintf('<a target="%s"%s', $target, $target === '_blank' ? $noOpenerString : ''),
                    $linkText
                );
            },
            $processedContent
        ) ?: '';
        return $processedContent;
    }
}
