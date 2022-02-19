<?php


namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\SiteNodeUtility;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Neos\Domain\Repository\SiteRepository;

class EmulatedLegacySite
{

    /**
     * @Flow\Inject
     * @var LegacyLoggerInterface
     */
    protected $legacyLogger;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var SiteNodeUtility
     */
    protected $siteNodeUtility;


    public function __construct(NodeInterface $traversableNode)
    {
        $this->contextNode = $traversableNode;
    }

    public function getSiteResourcesPackageKey()
    {
        $this->legacyLogger->info(
            'context.currentSite.siteResourcesPackageKey called',
            LogEnvironment::fromMethodName(__METHOD__)
        );

        $siteNode = $this->siteNodeUtility->findSiteNode($this->contextNode);

        /* @var $site \Neos\Neos\Domain\Model\Site */
        $site = $this->siteRepository->findOneByNodeName($siteNode->getNodeName()->jsonSerialize());
        return ($site ? $site->getSiteResourcesPackageKey() : null);
    }

    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning(
            'context.currentSite.* method not implemented',
            LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName)
        );
        return null;
    }
}
