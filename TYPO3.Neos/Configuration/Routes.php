<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

/**
 * Example route which acts as a shortcut to /TYPO3/Page/Default
 */
$c->typo3
	->setUrlPattern('typo3')
	->setDefaults(
		array(
			'package' => 'TYPO3',
			'controller' => 'Page',
			'action' => 'Default',
		)
	);

?>