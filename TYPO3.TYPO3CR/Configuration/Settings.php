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
 * @var F3::TYPO3CR::Storage::BackendInterface
 */
$c->TYPO3CR->storage->backend = 'F3::TYPO3CR::Storage::Backend::PDO';

/**
 * Options which are passed to the storage backend used by TYPO3CR
 *
 * @var array
 */
$c->TYPO3CR->storage->backendOptions = array(
	'dataSourceName' => 'sqlite:' . FLOW3_PATH_DATA . 'Persistent/TYPO3CR.db',
	'username' => NULL,
	'password' => NULL
);

/**
 * The indexing/search backend to use for TYPO3CR.
 *
 * @var F3::TYPO3CR::Storage::SearchInterface
 */
$c->TYPO3CR->search->backend = 'F3::TYPO3CR::Storage::Search::Lucene';

/**
 * Options which are passed to the indexing/search backend used by TYPO3CR
 *
 * @var array
 */
$c->TYPO3CR->search->backendOptions = array(
	'indexLocation' => FLOW3_PATH_DATA . 'Persistent/Index/'
);

?>