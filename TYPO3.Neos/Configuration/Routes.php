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
->setUrlPattern('typo3/[module]/[submodule]')
->setDefaults(
	array(
		'@package' => 'TYPO3',
		'@controller' => 'Backend',
		'@action' => 'ViewModule',
		'module' => 'Default',
		'submodule' => 'Default'
	)
);
?>