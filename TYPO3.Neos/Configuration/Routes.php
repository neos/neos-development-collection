<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

#$c->default->urlPattern = '[pagetitle]';
#$c->default->defaults =
#		array(
#			'package' => 'TYPO3',
#			'controller' => 'Page',
#			'action' => 'Default',
#		);

$c->TYPO3Backend
	->setUrlPattern('typo3')
	->setDefaults(
		array(
			'package' => 'TYPO3',
			'controller' => 'Backend',
			'action' => 'Default',
		)
	);
?>