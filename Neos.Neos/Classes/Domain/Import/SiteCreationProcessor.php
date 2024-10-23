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

use JsonException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Import processor that creates and persists a Neos {@see Site} instance
 *
 * @phpstan-type DomainShape array{hostname: string, scheme?: ?string, port?: ?int, active?: ?bool, primary?: ?bool }
 * @phpstan-type SiteShape array{name:string, siteResourcesPackageKey:string, nodeName?: string, online?:bool, domains?: ?DomainShape[] }
 */
final readonly class SiteCreationProcessor implements ProcessorInterface
{
    public function __construct(
        private SiteRepository $siteRepository,
        private PersistenceManagerInterface $persistenceManager
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        if ($context->files->has('sites.json')) {
            $sitesJson = $context->files->read('sites.json');
            try {
                $sites = json_decode($sitesJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException("Failed to decode sites.json: {$e->getMessage()}", 1729506117, $e);
            }
        } else {
            $sites = self::extractSitesFromEventStream($context);
        }
        $persistAllIsRequired = false;
        /** @var SiteShape $site */
        foreach ($sites as $site) {
            $context->dispatch(Severity::NOTICE, "Creating site \"{$site['name']}\"");

            $siteNodeName = !empty($site['nodeName']) ? NodeName::fromString($site['nodeName']) : NodeName::transliterateFromString($site['name']);
            if ($this->siteRepository->findOneByNodeName($siteNodeName->value)) {
                $context->dispatch(Severity::NOTICE, "Site for node name \"{$siteNodeName->value}\" already exists, skipping");
                continue;
            }
            // TODO use node aggregate identifier instead of node name
            $siteInstance = new Site($siteNodeName->value);
            $siteInstance->setSiteResourcesPackageKey($site['siteResourcesPackageKey']);
            $siteInstance->setState(($site['online'] ?? false) ? Site::STATE_OFFLINE : Site::STATE_ONLINE);
            $siteInstance->setName($site['name']);

            foreach ($site['domains'] ?? [] as $domain) {
                $domainInstance = new Domain();
                $domainInstance->setSite($siteInstance);
                $domainInstance->setHostname($domain['hostname']);
                $domainInstance->setPort($domain['port'] ?? null);
                $domainInstance->setScheme($domain['scheme'] ?? null);
                $domainInstance->setActive($domain['active'] ?? false);
                if ($domain['primary'] ?? false) {
                    $siteInstance->setPrimaryDomain($domainInstance);
                }
            }

            $this->siteRepository->add($siteInstance);
            $persistAllIsRequired = true;
        }
        if ($persistAllIsRequired) {
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * @return array<SiteShape>
     */
    private static function extractSitesFromEventStream(ProcessingContext $context): array
    {
        $eventFileResource = $context->files->readStream('events.jsonl');
        $rootNodeAggregateIds = [];
        $sites = [];
        while (($line = fgets($eventFileResource)) !== false) {
            $event = ExportedEvent::fromJson($line);
            if ($event->type === 'RootNodeAggregateWithNodeWasCreated') {
                $rootNodeAggregateIds[] = $event->payload['nodeAggregateId'];
                continue;
            }
            if ($event->type === 'NodeAggregateWithNodeWasCreated' && in_array($event->payload['parentNodeAggregateId'], $rootNodeAggregateIds, true)) {
                $sites[] = [
                    'siteResourcesPackageKey' => self::extractPackageKeyFromNodeTypeName($event->payload['nodeTypeName']),
                    'name' => $event->payload['initialPropertyValues']['title']['value'] ?? $event->payload['nodeTypeName'],
                    'nodeTypeName' => $event->payload['nodeTypeName'],
                    'nodeName' => $event->payload['nodeName'] ?? null,
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
