<?php

declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Processors;

use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;

/**
 * @phpstan-type DomainShape array{hostname: string, scheme?: ?string, port?: ?int, active?: ?bool, primary?: ?bool }
 * @phpstan-type SiteShape array{name:string, siteResourcesPackageKey:string, nodeName?: string, online?:bool, domains?: ?DomainShape[] }
 */
final class SitesExportProcessor implements ProcessorInterface
{
    /**
     * @param iterable<int, array<string, mixed>> $siteRows
     * @param iterable<int, array<string, mixed>> $domainRows
     */
    public function __construct(
        private readonly iterable $siteRows,
        private readonly iterable $domainRows
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $sitesData = $this->getSiteData();
        $context->files->write('sites.json', json_encode($sitesData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @return SiteShape[]
     */
    private function getSiteData(): array
    {
        $siteData = [];
        foreach ($this->siteRows as $siteRow) {
            $siteData[] = [
                "name" => $siteRow['name'],
                "nodeName" => $siteRow['nodename'],
                "siteResourcesPackageKey" =>  $siteRow['siteresourcespackagekey'],
                "online" => $siteRow['state'] === 1,
                "domains" => array_values(
                    array_filter(
                        array_map(
                            function(array $domainRow) use ($siteRow) {
                                if ($siteRow['persistence_object_identifier'] !== $domainRow['site']) {
                                    return null;
                                }
                                return [
                                    'hostname' => $domainRow['hostname'],
                                    'scheme' => $domainRow['scheme'],
                                    'port' => $domainRow['port'],
                                    'active' => (bool)$domainRow['active'],
                                    'primary' => $domainRow['persistence_object_identifier'] === $siteRow['primarydomain'],
                                ];
                            },
                            iterator_to_array($this->domainRows)
                        )
                    )
                )
            ];
        }

        return $siteData;
    }
}
