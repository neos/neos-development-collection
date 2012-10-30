<?php
namespace TYPO3\Neos\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Domain Model of a Domain
 *
 * @Flow\Entity
 * @Flow\Scope("prototype")
 */
class Domain  {

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $hostPattern = '*';

	/**
	 * @var \TYPO3\Neos\Domain\Model\Site
	 * @ORM\ManyToOne
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $site;

	/**
	 * Sets the pattern for the host of the domain
	 *
	 * @param string $hostPattern Pattern for the host
	 * @return void
	 * @api
	 */
	public function setHostPattern($hostPattern) {
		$this->hostPattern = $hostPattern;
	}

	/**
	 * Returns the host pattern for this domain
	 *
	 * @return string The host pattern
	 * @api
	 */
	public function getHostPattern() {
		return $this->hostPattern;
	}

	/**
	 * Sets the site this domain is pointing to
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site The site
	 * @return void
	 * @api
	 */
	public function setSite(\TYPO3\Neos\Domain\Model\Site $site) {
		$this->site = $site;
	}

	/**
	 * Returns the site this domain is pointing to
	 *
	 * @return \TYPO3\Neos\Domain\Model\Site
	 * @api
	 */
	public function getSite() {
		return $this->site;
	}
}
?>