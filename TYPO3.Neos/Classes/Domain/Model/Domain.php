<?php
namespace TYPO3\TYPO3\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Domain Model of a Domain
 *
 * @FLOW3\Entity
 * @FLOW3\Scope("prototype")
 */
class Domain  {

	/**
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $hostPattern = '*';

	/**
	 * @var \TYPO3\TYPO3\Domain\Model\Site
	 * @ORM\ManyToOne
	 * @FLOW3\Validate(type="NotEmpty")
	 */
	protected $site;

	/**
	 * Sets the pattern for the host of the domain
	 *
	 * @param string $hostPattern Pattern for the host
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setHostPattern($hostPattern) {
		$this->hostPattern = $hostPattern;
	}

	/**
	 * Returns the host pattern for this domain
	 *
	 * @return string The host pattern
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHostPattern() {
		return $this->hostPattern;
	}

	/**
	 * Sets the site this domain is pointing to
	 *
	 * @param \TYPO3\TYPO3\Domain\Model\Site $site The site
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setSite(\TYPO3\TYPO3\Domain\Model\Site $site) {
		$this->site = $site;
	}

	/**
	 * Returns the site this domain is pointing to
	 *
	 * @return \TYPO3\TYPO3\Domain\Model\Site
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getSite() {
		return $this->site;
	}
}
?>