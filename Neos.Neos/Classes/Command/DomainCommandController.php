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

namespace Neos\Neos\Command;

use Neos\Error\Messages\Error;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Domain command controller for the Neos.Neos package
 *
 * @Flow\Scope("singleton")
 */
class DomainCommandController extends CommandController
{
    /**
     * @var DomainRepository
     * @Flow\Inject
     */
    protected $domainRepository;

    /**
     * @var SiteRepository
     * @Flow\Inject
     */
    protected $siteRepository;

    /**
     * @var ValidatorResolver
     * @Flow\Inject
     */
    protected $validatorResolver;

    /**
     * Add a domain record
     *
     * @param string $siteNodeName The nodeName of the site rootNode, e.g. "flowneosio"
     * @param string $hostname The hostname to match on, e.g. "flow.neos.io"
     * @param string $scheme The scheme for linking (http/https)
     * @param integer $port The port for linking (0-49151)
     * @return void
     */
    public function addCommand($siteNodeName, $hostname, $scheme = null, $port = null)
    {
        $site = $this->siteRepository->findOneByNodeName($siteNodeName);
        if (!$site instanceof Site) {
            $this->outputLine('<error>No site found with nodeName "%s".</error>', [$siteNodeName]);
            $this->quit(1);
        }
        /** @var Site $site */

        $domains = $this->domainRepository->findByHostname($hostname);
        if ($domains->count() > 0) {
            $this->outputLine('<error>The host name "%s" is not unique.</error>', [$hostname]);
            $this->quit(1);
        }

        $domain = new Domain();
        if ($scheme !== null) {
            $domain->setScheme($scheme);
        }
        if ($port !== null) {
            $domain->setPort($port);
        }
        $domain->setSite($site);
        $domain->setHostname($hostname);

        $domainValidator = $this->validatorResolver->getBaseValidatorConjunction(Domain::class);
        $result = $domainValidator->validate($domain);
        if ($result->hasErrors()) {
            foreach ($result->getFlattenedErrors() as $propertyName => $errors) {
                /** @var array<Error> $errors */
                $firstError = array_pop($errors);
                $this->outputLine('<error>Validation failed for "' . $propertyName . '": ' . $firstError . '</error>');
                $this->quit(1);
            }
        }

        $this->domainRepository->add($domain);

        $this->outputLine('Domain entry created.');
    }

    /**
     * Display a list of available domain records
     *
     * @param string $hostname An optional hostname to search for
     * @return void
     */
    public function listCommand($hostname = null)
    {
        if ($hostname === null) {
            $domains = $this->domainRepository->findAll();
        } else {
            $domains = $this->domainRepository->findByHostname($hostname);
        }

        if (count($domains) === 0) {
            $this->outputLine('No domain entries available.');
            $this->quit(0);
        }

        $availableDomains = [];
        foreach ($domains as $domain) {
            /** @var \Neos\Neos\Domain\Model\Domain $domain */
            $availableDomains[] = [
                'nodeName' => $domain->getSite()->getNodeName(),
                'hostname' => (string)$domain,
                'active' => $domain->getActive() ? 'active' : 'inactive'
            ];
        }

        $this->output->outputTable($availableDomains, ['Node name', 'Domain (Scheme/Host/Port)', 'State']);
    }

    /**
     * Delete a domain record by hostname (with globbing)
     *
     * @param string $hostname The hostname to remove (globbing is supported)
     * @return void
     */
    public function deleteCommand($hostname)
    {
        $domains = $this->findDomainsByHostnamePattern($hostname);
        if (empty($domains)) {
            $this->outputLine('<error>No domain found for hostname-pattern "%s".</error>', [$hostname]);
            $this->quit(1);
        }
        foreach ($domains as $domain) {
            $site = $domain->getSite();
            if ($site->getPrimaryDomain() === $domain) {
                $site->setPrimaryDomain(null);
                $this->siteRepository->update($site);
            }
            $this->domainRepository->remove($domain);
            $this->outputLine('Domain entry "%s" deleted.', [$domain->getHostname()]);
        }
    }

    /**
     * Activate a domain record by hostname (with globbing)
     *
     * @param string $hostname The hostname to activate (globbing is supported)
     * @return void
     */
    public function activateCommand($hostname)
    {
        $domains = $this->findDomainsByHostnamePattern($hostname);
        if (empty($domains)) {
            $this->outputLine('<error>No domain found for hostname-pattern "%s".</error>', [$hostname]);
            $this->quit(1);
        }
        foreach ($domains as $domain) {
            $domain->setActive(true);
            $this->domainRepository->update($domain);
            $this->outputLine('Domain entry "%s" was activated.', [$domain->getHostname()]);
        }
    }

    /**
     * Deactivate a domain record by hostname (with globbing)
     *
     * @param string $hostname The hostname to deactivate (globbing is supported)
     * @return void
     */
    public function deactivateCommand($hostname)
    {
        $domains = $this->findDomainsByHostnamePattern($hostname);
        if (empty($domains)) {
            $this->outputLine('<error>No domain found for hostname-pattern "%s".</error>', [$hostname]);
            $this->quit(1);
        }
        foreach ($domains as $domain) {
            $domain->setActive(false);
            $this->domainRepository->update($domain);
            $this->outputLine('Domain entry "%s" was deactivated.', [$domain->getHostname()]);
        }
    }

    /**
     * Find domains that match the given hostname with globbing support
     *
     * @param string $hostnamePattern pattern for the hostname of the domains
     * @return array<Domain>
     */
    protected function findDomainsByHostnamePattern($hostnamePattern)
    {
        return array_filter(
            $this->domainRepository->findAll()->toArray(),
            function ($domain) use ($hostnamePattern) {
                return fnmatch($hostnamePattern, $domain->getHostname());
            }
        );
    }
}
