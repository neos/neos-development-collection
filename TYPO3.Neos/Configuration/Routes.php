<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3Route1->
	setUrlPattern('[page].[@format]')->
	setDefaults(
		array(
			'@package' => 'TYPO3_Service',
			'@controller' => 'Pages',
			'@action' => 'Default',
			'page' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3Route2
->setUrlPattern('TYPO3/[module]/[submodule]')
->setDefaults(
	array(
		'@package' => 'TYPO3_Backend',
		'@controller' => 'Default',
		'@action' => 'ViewModule',
		'module' => 'Default',
		'submodule' => 'Default'
	)
);

$c->TYPO3Route3
->setUrlPattern('TYPO3/Service/[@controller]')
->setDefaults(
	array(
		'@package' => 'TYPO3_Service',
	)
);

?>