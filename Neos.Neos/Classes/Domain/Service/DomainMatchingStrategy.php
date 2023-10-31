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

namespace Neos\Neos\Domain\Service;

use Neos\Flow\Annotations as Flow;

/**
 * Strategy for matching domains
 *
 * @Flow\Scope("singleton")
 */
class DomainMatchingStrategy
{
    public const EXACTMATCH = 300;
    public const NOMATCH = -300;

    /**
     * Returns those of the given domains which match the specified hostname.
     *
     * The domains are sorted by their match exactness.
     * If none really matches an empty array is returned.
     *
     * @param string $hostnameToMatch The hostname to match against (eg. "localhost" or "www.neos.io")
     * @param array<\Neos\Neos\Domain\Model\Domain> $domains The domains to check
     * @return array<\Neos\Neos\Domain\Model\Domain> The matching domains
     */
    public function getSortedMatches($hostnameToMatch, array $domains)
    {
        $matchingDomains = [];
        $matchQualities = [];
        $hostnameToMatchPartsReverse = array_reverse(explode('.', $hostnameToMatch));

        foreach ($domains as $domain) {
            $domainHostname = $domain->getHostname();

            if ($hostnameToMatch === $domainHostname) {
                $matchQuality = self::EXACTMATCH;
            } else {
                $matchQuality = 0;
                $domainHostnamePartsReverse = array_reverse(explode('.', $domainHostname));
                foreach ($domainHostnamePartsReverse as $index => $domainHostnamePart) {
                    if (
                        isset($hostnameToMatchPartsReverse[$index])
                        && $domainHostnamePart === $hostnameToMatchPartsReverse[$index]
                    ) {
                        $matchQuality++;
                    } else {
                        $matchQuality = self::NOMATCH;
                        break;
                    }
                }
            }

            if ($matchQuality > 0) {
                $matchingDomains[] = $domain;
                $matchQualities[] = $matchQuality;
            }
        }

        array_multisort($matchQualities, SORT_DESC, $matchingDomains);
        return $matchingDomains;
    }
}
