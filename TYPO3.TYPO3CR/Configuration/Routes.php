<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3CRAdmin
	->setUrlPattern('typo3cr/[@controller]/[@action]')
	->setControllerComponentNamePattern('F3::@package::Admin::Controller::@controllerController')
	->setDefaults(
		array(
			'@package' => 'TYPO3CR',
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

?>