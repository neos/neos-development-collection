<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

/**
 * Route which acts as a shortcut to /TYPO3/Backend/Default
 */
$c->TYPO3
	->setUrlPattern('typo3')
	->setDefaults(
		array(
			'package' => 'TYPO3',
			'controller' => 'Backend',
			'action' => 'Default',
		)
	);

?>