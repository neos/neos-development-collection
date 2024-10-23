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

namespace Neos\Neos\Domain\Export;

use JsonException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Export\Event\ValueObject\ExportedEvent;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Export processor exports Neos {@see Site} instances as json
 *
 * @phpstan-type SiteShape array{name:string, packageKey:string, nodeName?: string, inactive?:bool}
 */
final readonly class SiteExportProcessor implements ProcessorInterface
{
    public function __construct(
        private SiteRepository $siteRepository,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $sites = array_map(
            fn(Site $site) => [
                "name" => $site->getName(),
                "nodeName" => $site->getNodeName()->value,
                "siteResourcesPackageKey" => $site->getSiteResourcesPackageKey(),
                "online" => $site->isOnline(),
                "domains" => array_map(
                    fn(Domain $domain) => [
                        'hostname' => $domain->getHostname(),
                        'scheme' => $domain->getScheme(),
                        'port' => $domain->getPort(),
                        'active' => $domain->getActive(),
                        'primary' => $domain === $site->getPrimaryDomain(),
                    ],
                    $site->getDomains()->toArray()
                )
            ],
            $this->siteRepository->findAll()->toArray()
        );

        $context->files->write(
            'sites.json',
            json_encode($sites, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
