<?php


namespace Neos\EventSourcedContentRepository\LegacyApi\ContextInNodeBasedReadModel;

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\SiteNodeUtility;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\LegacyApi\Logging\LegacyLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Neos\Domain\Model\Site;
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

    private NodeInterface $contextNode;

    public function __construct(NodeInterface $traversableNode)
    {
        $this->contextNode = $traversableNode;
    }

    public function getSiteResourcesPackageKey(): ?string
    {
        $this->legacyLogger->info(
            'context.currentSite.siteResourcesPackageKey called',
            LogEnvironment::fromMethodName(__METHOD__)
        );

        $siteNode = $this->siteNodeUtility->findSiteNode($this->contextNode);
        $siteNodeName = $siteNode->getNodeName();

        /* @var ?Site $site */
        $site = $siteNodeName ? $this->siteRepository->findOneByNodeName((string)$siteNodeName) : null;

        return $site?->getSiteResourcesPackageKey();
    }

    /**
     * @param string $methodName
     * @param array<int|string,mixed> $args
     * @return null
     */
    public function __call($methodName, $args)
    {
        $this->legacyLogger->warning(
            'context.currentSite.* method not implemented',
            LogEnvironment::fromMethodName(EmulatedLegacyContext::class . '::' . $methodName)
        );
        return null;
    }
}
