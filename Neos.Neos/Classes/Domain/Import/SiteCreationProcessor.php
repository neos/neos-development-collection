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

namespace Neos\Neos\Domain\Import;

use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Export\SiteExportProcessor;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Import processor that creates and persists a Neos {@see Site} instance
 *
 * @phpstan-import-type DomainShape from SiteExportProcessor
 * @phpstan-import-type SiteShape from SiteExportProcessor
 */
final readonly class SiteCreationProcessor implements ProcessorInterface
{
    public function __construct(
        private SiteRepository $siteRepository,
        private DomainRepository $domainRepository,
        private PersistenceManagerInterface $persistenceManager
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        if ($context->files->has('sites.json')) {
            $sitesJson = $context->files->read('sites.json');
            try {
                $sites = json_decode($sitesJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException("Failed to decode sites.json: {$e->getMessage()}", 1729506117, $e);
            }
        } else {
            $context->dispatch(Severity::WARNING, 'Deprecated legacy handling: No "sites.json" in export found, attempting to extract neos sites from the events. Please update the export soonish.');
            $sites = self::extractSitesFromEventStream($context);
        }

        /** @var SiteShape $site */
        foreach ($sites as $site) {
            $context->dispatch(Severity::NOTICE, sprintf('Creating site "%s"', $site['name']));

            $siteNodeName = NodeName::fromString($site['nodeName']);
            if ($this->siteRepository->findOneByNodeName($siteNodeName->value)) {
                $context->dispatch(Severity::NOTICE, sprintf('Site for node name "%s" already exists, skipping', $siteNodeName->value));
                continue;
            }
            $siteInstance = new Site($siteNodeName->value);
            $siteInstance->setSiteResourcesPackageKey($site['siteResourcesPackageKey']);
            $siteInstance->setState($site['online'] ? Site::STATE_ONLINE : Site::STATE_OFFLINE);
            $siteInstance->setName($site['name']);
            $this->siteRepository->add($siteInstance);
            $this->persistenceManager->persistAll();
            foreach ($site['domains'] as $domain) {
                $domainInstance = $this->domainRepository->findByHostname($domain['hostname'])->getFirst();
                if ($domainInstance instanceof Domain) {
                    $context->dispatch(Severity::NOTICE, sprintf('Domain "%s" already exists. Adding it to site "%s".', $domain['hostname'], $site['name']));
                } else {
                    $domainInstance = new Domain();
                    $domainInstance->setSite($siteInstance);
                    $domainInstance->setHostname($domain['hostname']);
                    $domainInstance->setPort($domain['port'] ?? null);
                    $domainInstance->setScheme($domain['scheme'] ?? null);
                    $domainInstance->setActive($domain['active'] ?? false);
                    $this->domainRepository->add($domainInstance);
                }
                if ($domain['primary'] ?? false) {
                    $siteInstance->setPrimaryDomain($domainInstance);
                    $this->siteRepository->update($siteInstance);
                }
                $this->persistenceManager->persistAll();
            }
        }
    }

    /**
     * @deprecated with Neos 9 Beta 15 please make sure that exports contain `sites.json`
     * @return array<SiteShape>
     */
    private static function extractSitesFromEventStream(ProcessingContext $context): array
    {
        $eventFileResource = $context->files->readStream('events.jsonl');
        $siteRooNodeAggregateId = null;
        $sites = [];
        while (($line = fgets($eventFileResource)) !== false) {
            $event = ExportedEvent::fromJson($line);
            if ($event->type === 'RootNodeAggregateWithNodeWasCreated' && $event->payload['nodeTypeName'] === NodeTypeNameFactory::NAME_SITES) {
                $siteRooNodeAggregateId = $event->payload['nodeAggregateId'];
                continue;
            }
            if ($event->type === 'NodeAggregateWithNodeWasCreated' && $event->payload['parentNodeAggregateId'] === $siteRooNodeAggregateId) {
                if (!isset($event->payload['nodeName'])) {
                    throw new \RuntimeException(sprintf('The nodeName of the site node "%s" must not be empty', $event->payload['nodeAggregateId']), 1731236316);
                }
                $sites[] = [
                    'siteResourcesPackageKey' => self::extractPackageKeyFromNodeTypeName($event->payload['nodeTypeName']),
                    'name' => $event->payload['initialPropertyValues']['title']['value'] ?? $event->payload['nodeTypeName'],
                    'nodeTypeName' => $event->payload['nodeTypeName'],
                    'nodeName' => $event->payload['nodeName'],
                    'domains' => [],
                    'online' => true
                ];
            }
        };
        return $sites;
    }

    private static function extractPackageKeyFromNodeTypeName(string $nodeTypeName): string
    {
        if (preg_match('/^([^:])+/', $nodeTypeName, $matches) !== 1) {
            throw new \RuntimeException("Failed to extract package key from '$nodeTypeName'.", 1729505701);
        }
        return $matches[0];
    }
}
