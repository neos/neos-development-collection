<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3Route1->
	setUrlPattern('[pagetitle]')->
	setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Page',
			'@action' => 'Default',
		)
	);

$c->TYPO3Route2
	->setUrlPattern('typo3')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Backend',
			'@action' => 'Default',
		)
	);

$c->TYPO3Route3
	->setUrlPattern('testing/[@controller]/[@action]')
	->setDefaults(
		array(
			'@package' => 'Testing',
			'@controller' => 'Default',
			'@action' => 'Default',
		)
	);

?>