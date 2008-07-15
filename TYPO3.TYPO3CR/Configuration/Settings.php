<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Settings Configuration for the TYPO3CR Package                         *
 *                                                                        */

/**
 * @package TYPO3CR
 * @version $Id$
 */

/**
 * The storage backend to use for TYPO3CR.
 *
 * @var F3_TYPO3CR_Storage_BackendInterface
 */
$c->TYPO3CR->storage->backend = 'F3_TYPO3CR_Storage_Backend_PDO';

/**
 * Options which are passed to the storage backend used by TYPO3CR
 *
 * @var array
 */
$c->TYPO3CR->storage->backendOptions = array(
	'dataSourceName' => '',
	'username' => NULL,
	'password' => NULL
);

?>