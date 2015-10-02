<?php
namespace TYPO3\Neos;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * The TYPO3 Neos Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $flushConfigurationCache = function () use ($bootstrap) {
            $cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
            $cacheManager->getCache('TYPO3_Neos_Configuration_Version')->flush();
        };

        $dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', $flushConfigurationCache);

        $dispatcher->connect('TYPO3\Neos\Domain\Model\Site', 'siteChanged', $flushConfigurationCache);
        $dispatcher->connect('TYPO3\Neos\Domain\Model\Site', 'siteChanged', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeUpdated', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeAdded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeRemoved', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'beforeNodeMove', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');

        $uriPathSegmentGenerator = function (\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
            if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
                $q = new FlowQuery(array($node));
                $possibleUriPathSegment = $initialUriPathSegment = !$node->hasProperty('uriPathSegment') ? $node->getName() : $node->getProperty('uriPathSegment');
                $i = 1;
                while (count($q->siblings('[instanceof TYPO3.Neos:Document][uriPathSegment="' . $possibleUriPathSegment . '"]')->get())) {
                    $possibleUriPathSegment = $initialUriPathSegment . '-' . $i++;
                }
                $node->setProperty('uriPathSegment', $possibleUriPathSegment);
            }
        };
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeAdded', $uriPathSegmentGenerator);
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodePropertyChanged', function (NodeInterface $node, $propertyName) use ($uriPathSegmentGenerator,$bootstrap) {
            if ($propertyName === 'uriPathSegment') {
                $uriPathSegmentGenerator($node);
                $bootstrap->getObjectManager()->get('TYPO3\Neos\Routing\Cache\RouteCacheFlusher')->registerNodeChange($node);
            }
        });
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\NodeData', 'nodePathChanged', function (NodeData $nodeData) use ($bootstrap,$uriPathSegmentGenerator) {
            if ($nodeData->getNodeType()->isOfType('TYPO3.Neos:Document')) {
                $contextFactory = $bootstrap->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
                $nodeFactory = $bootstrap->getObjectManager()->get('TYPO3\TYPO3CR\Domain\Factory\NodeFactory');
                $context = $contextFactory->create(array('workspaceName' => $nodeData->getWorkspace()->getName()));
                $node = $nodeFactory->createFromNodeData($nodeData, $context);
                $uriPathSegmentGenerator($node);
            }
        });

        $dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodePublished', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');

        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\NodeData', 'nodePathChanged', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodePathChange');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeRemoved', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodePublished', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'repositoryObjectsPersisted', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'commit');

        $dispatcher->connect('TYPO3\Neos\Domain\Service\SiteService', 'sitePruned', 'TYPO3\TypoScript\Core\Cache\ContentCache', 'flush');
        $dispatcher->connect('TYPO3\Neos\Domain\Service\SiteService', 'sitePruned', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

        $dispatcher->connect('TYPO3\Neos\Domain\Service\SiteImportService', 'siteImported', 'TYPO3\TypoScript\Core\Cache\ContentCache', 'flush');
        $dispatcher->connect('TYPO3\Neos\Domain\Service\SiteImportService', 'siteImported', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');
    }
}
