<?php
namespace TYPO3\Neos\Command;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Validation\ValidatorResolver;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;

/**
 * Domain command controller for the TYPO3.Neos package
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
     * @param string $siteNodeName The nodeName of the site rootNode, e.g. "neostypo3org"
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
            /** @var \TYPO3\Neos\Domain\Model\Domain $domain */
            $availableDomains[] = [
                'nodeName' => $domain->getSite()->getNodeName(),
                'hostname' => (string)$domain,
                'active' => $domain->getActive() ? 'active' : 'inactive'
            ];
        }

        $this->output->outputTable($availableDomains, ['Node name', 'Domain (Scheme/Host/Port)', 'State']);
    }

    /**
     * Delete a domain record by hostname
     *
     * @param string $hostname The hostname to remove
     * @return void
     */
    public function deleteCommand($hostname)
    {
        $domain = $this->domainRepository->findOneByHostname($hostname);
        if (!$domain instanceof Domain) {
            $this->outputLine('<error>Domain not found.</error>');
            $this->quit(1);
        }

        $this->domainRepository->remove($domain);
        $this->outputLine('Domain entry deleted.');
    }

    /**
     * Activate a domain record by hostname
     *
     * @param string $hostname The hostname to activate
     * @return void
     */
    public function activateCommand($hostname)
    {
        $domain = $this->domainRepository->findOneByHostname($hostname);
        if (!$domain instanceof Domain) {
            $this->outputLine('<error>Domain not found.</error>');
            $this->quit(1);
        }

        $domain->setActive(true);
        $this->domainRepository->update($domain);
        $this->outputLine('Domain entry activated.');
    }

    /**
     * Deactivate a domain record by hostname
     *
     * @param string $hostname The hostname to deactivate
     * @return void
     */
    public function deactivateCommand($hostname)
    {
        $domain = $this->domainRepository->findOneByHostname($hostname);
        if (!$domain instanceof Domain) {
            $this->outputLine('<error>Domain not found.</error>');
            $this->quit(1);
        }

        $domain->setActive(false);
        $this->domainRepository->update($domain);
        $this->outputLine('Domain entry deactivated.');
    }
}
