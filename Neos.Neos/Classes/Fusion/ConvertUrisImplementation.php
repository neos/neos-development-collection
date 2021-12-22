<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Service\LinkingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

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
    public function evaluate()
    {
        $text = $this->fusionValue('value');

        if ($text === '' || $text === null) {
            return '';
        }

        if (!is_string($text)) {
            throw new Exception(sprintf('Only strings can be processed by this Fusion object, given: "%s".', gettype($text)), 1382624080);
        }

        $node = $this->fusionValue('node');

        if (!$node instanceof NodeInterface) {
            throw new Exception(sprintf('The current node must be an instance of NodeInterface, given: "%s".', gettype($text)), 1382624087);
        }

        if (!($this->fusionValue('forceConversion')) && $node->getContext()->getWorkspace()->getName() !== 'live') {
            return $text;
        }

        $unresolvedUris = [];
        $linkingService = $this->linkingService;
        $controllerContext = $this->runtime->getControllerContext();

        $absolute = $this->fusionValue('absolute');

        $processedContent = preg_replace_callback(LinkingService::PATTERN_SUPPORTED_URIS, function (array $matches) use ($node, $linkingService, $controllerContext, &$unresolvedUris, $absolute) {
            switch ($matches[1]) {
                case 'node':
                    $resolvedUri = $linkingService->resolveNodeUri($matches[0], $node, $controllerContext, $absolute);
                    $this->runtime->addCacheTag('node', $matches[2]);
                    break;
                case 'asset':
                    $resolvedUri = $linkingService->resolveAssetUri($matches[0]);
                    $this->runtime->addCacheTag('asset', $matches[2]);
                    break;
                default:
                    $resolvedUri = null;
            }

            if ($resolvedUri === null) {
                $unresolvedUris[] = $matches[0];
                return $matches[0];
            }

            return $resolvedUri;
        }, $text);

        if ($unresolvedUris !== []) {
            $processedContent = preg_replace('/<a(?:\s+[^>]*)?\s+href="(node|asset):\/\/[^"]+"[^>]*>(.*?)<\/a>/', '$2', $processedContent);
            $processedContent = preg_replace(LinkingService::PATTERN_SUPPORTED_URIS, '', $processedContent);
        }

        $processedContent = $this->replaceLinkTargets($processedContent);

        return $processedContent;
    }

    /**
     * Replace the target attribute of link tags in processedContent with the target
     * specified by externalLinkTarget and resourceLinkTarget options.
     * Additionally set rel="noopener" for external links.
     *
     * @param string $processedContent
     * @return string
     */
    protected function replaceLinkTargets($processedContent)
    {
        $setNoOpener = $this->fusionValue('setNoOpener');
        $setExternal = $this->fusionValue('setExternal');
        $externalLinkTarget = \trim($this->fusionValue('externalLinkTarget'));
        $resourceLinkTarget = \trim($this->fusionValue('resourceLinkTarget'));
        $controllerContext = $this->runtime->getControllerContext();
        $host = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();
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
                if ($resourceLinkTarget && $resourceLinkTarget !== '' && \strpos($linkHref, '_Resources') !== false) {
                    $target = $resourceLinkTarget;
                }
                if ($isExternalLink && $setNoOpener) {
                    $linkText = self::setAttribute('rel', 'noopener', $linkText);
                }
                if ($isExternalLink && $setExternal) {
                    $linkText = self::setAttribute('rel', 'external', $linkText);
                }
                if (is_string($target) && strlen($target) !== 0) {
                    return self::setAttribute('target', $target, $linkText);
                }
                return $linkText;
            },
            $processedContent
        );
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
            return \preg_replace('/' . $attribute . '="(.*?)"/', sprintf('%s="$1 %s"', $attribute, $value), $content);
        }

        // Add the missing attribute with the value
        return \str_replace('<a', sprintf('<a %s="%s"', $attribute, $value), $content);
    }
}
