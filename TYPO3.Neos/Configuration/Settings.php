<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Settings Configuration for the TYPO3 Package                           *
 *                                                                        */

/**
 * @package TYPO3
 * @version $Id$
 */

/**
 * Available backend modules
 *
 * @var array
 */
$c->TYPO3->backend->sections = array('System' => 'F3::TYPO3::Backend::Controller::SystemSection');

?>