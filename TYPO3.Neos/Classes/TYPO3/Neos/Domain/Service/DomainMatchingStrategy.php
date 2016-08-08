<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Strategy for matching domains
 *
 * @Flow\Scope("singleton")
 */
class DomainMatchingStrategy
{
    const EXACTMATCH = 300;
    const NOMATCH = -300;

    /**
     * Returns those of the given domains which match the specified host.
     * The domains are sorted by their match exactness.
     * If none really matches an empty array is returned.
     *
     * @param string $host The host to match against (eg. "localhost" or "www.neos.io")
     * @param array<\TYPO3\Neos\Domain\Model\Domain> $domains The domains to check
     * @return array The matching domains
     */
    public function getSortedMatches($host, array $domains)
    {
        $matchingDomains = array();
        $matchQualities = array();
        $hostPartsReverse = array_reverse(explode('.', $host));

        foreach ($domains as $domain) {
            $hostPattern = is_string($domain) ? $domain : $domain->getHostPattern();

            if ($host === $hostPattern) {
                $matchQuality = self::EXACTMATCH;
            } else {
                $matchQuality = 0;
                $hostPatternPartsReverse = array_reverse(explode('.', $hostPattern));
                foreach ($hostPatternPartsReverse as $index => $hostPatternPart) {
                    if ($hostPatternPart === '*' || (isset($hostPartsReverse[$index]) && $hostPatternPart === $hostPartsReverse[$index])) {
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
