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

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Exception as NeosException;
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
                gettype($text)
            ), 1382624080);
        }

        $node = $this->fusionValue('node');

        if (!$node instanceof Node) {
            throw new NeosException(sprintf(
                'The current node must be an instance of Node, given: "%s".',
                gettype($text)
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

        $processedContent = preg_replace_callback(
            self::PATTERN_SUPPORTED_URIS,
            function (array $matches) use (&$unresolvedUris, $absolute, $nodeAddress) {
                $resolvedUri = null;
                switch ($matches[1]) {
                    case 'node':
                        $nodeAddress = $nodeAddress->withNodeAggregateId(
                            NodeAggregateId::fromString($matches[2])
                        );
                        $uriBuilder = new UriBuilder();
                        $uriBuilder->setRequest($this->runtime->getControllerContext()->getRequest());
                        $uriBuilder->setCreateAbsoluteUri($absolute);
                        try {
                            $resolvedUri = (string)NodeUriBuilder::fromUriBuilder($uriBuilder)->uriFor($nodeAddress);
                        } catch (NoMatchingRouteException) {
                            $this->systemLogger->info(sprintf('Could not resolve "%s" to a live node uri. The node was probably deleted.', $matches[0]), LogEnvironment::fromMethodName(__METHOD__));
                        }
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
     * Additionally sets rel="noopener" for links with target="_blank",
     * and rel="noopener external" for external links.
     */
    protected function replaceLinkTargets(string $processedContent): string
    {
        $setNoOpener = $this->fusionValue('setNoOpener');
        $setExternal = $this->fusionValue('setExternal');
        $externalLinkTarget = trim($this->fusionValue('externalLinkTarget'));
        $resourceLinkTarget = trim($this->fusionValue('resourceLinkTarget'));
        if ($externalLinkTarget === '' && $resourceLinkTarget === '') {
            return $processedContent;
        }
        $controllerContext = $this->runtime->getControllerContext();
        $host = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();
        // todo optimize to only use one preg_replace_callback for uri converting
        $processedContent = preg_replace_callback(
            '~<a.*?href="(.*?)".*?>~i',
            function ($matches) use ($externalLinkTarget, $resourceLinkTarget, $host, $setNoOpener, $setExternal) {
                list($linkText, $linkHref) = $matches;
                $uriHost = parse_url($linkHref, PHP_URL_HOST);
                $target = null;
                $isExternalLink = is_string($uriHost) && $uriHost !== $host;
                if ($externalLinkTarget !== '' && $isExternalLink) {
                    $target = $externalLinkTarget;
                }
                if ($resourceLinkTarget !== '' && str_contains($linkHref, '_Resources')) {
                    $target = $resourceLinkTarget;
                }
                if ($target === null) {
                    return $linkText;
                }
                // todo merge with "rel" attribute if already existent
                $relValue = $isExternalLink && $setNoOpener ? 'noopener ' : '';
                $relValue .= $isExternalLink && $setExternal ? 'external' : '';
                $relValue = ltrim($relValue);
                if (str_contains($linkText, 'target="')) {
                    // todo shouldn't we merge the current target value
                    return preg_replace(
                        '/target="[^"]*"/',
                        sprintf('target="%s"%s', $target, $relValue ? sprintf(' rel="%s"', $relValue) : $relValue),
                        $linkText
                    );
                }
                return str_replace(
                    '<a',
                    sprintf('<a target="%s"%s', $target, $relValue ? sprintf(' rel="%s"', $relValue) : $relValue),
                    $linkText
                );
            },
            $processedContent
        ) ?: '';
        return $processedContent;
    }
}
