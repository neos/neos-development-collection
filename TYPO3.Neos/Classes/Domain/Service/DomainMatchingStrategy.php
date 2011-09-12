<?php
namespace TYPO3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Strategy for matching domains
 *
 * @scope singleton
 */
class DomainMatchingStrategy {

	const EXACTMATCH = 300;
	const NOMATCH = -300;

	/**
	 * Returns those of the given domains which match the specified host.
	 * The domains are sorted by their match exactness.
	 * If none really matches an empty array is returned.
	 *
	 * @param string $host The host to match against (eg. "localhost" or "www.typo3.org")
	 * @param array<\TYPO3\TYPO3\Domain\Model\Domain> $domains The domains to check
	 * @return array The matching domains
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSortedMatches($host, array $domains) {
		$matchingDomains = array();
		$matchQualities = array();
		$hostPartsReverse = array_reverse(explode('.', $host));

		foreach($domains as $domain) {
			$hostPattern = $domain->getHostPattern();

			if ($host === $hostPattern) {
				$matchQuality = self::EXACTMATCH;
			} else {
				$matchQuality = 0;
				$hostPatternPartsReverse = array_reverse(explode('.', $hostPattern));
				foreach ($hostPatternPartsReverse as $index => $hostPatternPart) {
					if ($hostPatternPart === '*' || $hostPatternPart === $hostPartsReverse[$index]) {
						$matchQuality ++;
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
?>