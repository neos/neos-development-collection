<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3CRAdmin
	->setUrlPattern('typo3cr/[@controller]/[@action]')
	->setControllerComponentNamePattern('F3_@package_Admin_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3CR',
			'@controller' => 'Default',
			'@action' => 'default',
			'@format' => 'html'
		)
	);

?>