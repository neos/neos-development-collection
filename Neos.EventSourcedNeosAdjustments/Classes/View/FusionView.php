<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\View;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\SiteNodeUtility;
use Neos\Flow\I18n\Locale;
use Neos\ContentRepository\Domain\Model\NodeInterface as LegacyNodeInterface;
use Neos\Fusion\Exception\RuntimeException;

/**
 * A Fusion view for Neos
 */
class FusionView extends \Neos\Neos\View\FusionView
{
    /**
     * @Flow\Inject
     * @var SiteNodeUtility
     */
    protected $siteNodeUtility;

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @throws \Exception if no node is given
     * @api
     */
    public function render()
    {
        $currentNode = $this->getCurrentNode();
        $currentSiteNode = $this->siteNodeUtility->findSiteNode($currentNode);
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        if ($currentNode instanceof LegacyNodeInterface) {
            $dimensions = $currentNode->getContext()->getDimensions();
            if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
                $currentLocale = new Locale($dimensions['language'][0]);
                $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
                $this->i18nService->getConfiguration()->setFallbackRule(['strict' => false, 'order' => array_reverse($dimensions['language'])]);
            }
        } else {
            // TODO: special case for Language DimensionSpacePoint!
        }

        $fusionRuntime->pushContextArray([
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode,
            'subgraph' => $this->getCurrentSubgraph(),
            'editPreviewMode' => isset($this->variables['editPreviewMode']) ? $this->variables['editPreviewMode'] : null
        ]);
        try {
            $output = $fusionRuntime->render($this->fusionPath);
            $output = $this->parsePotentialRawHttpResponse($output);
        } catch (RuntimeException $exception) {
            throw $exception->getPrevious();
        }
        $fusionRuntime->popContext();

        return $output;
    }

    protected function getCurrentSubgraph(): ContentSubgraphInterface
    {
        return $this->variables['subgraph'];
    }
}
