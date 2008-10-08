<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3CRAdmin
	->setUriPattern('typo3cr')
	->setControllerComponentNamePattern('F3::@package::Admin::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Admin::View::@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3CR',
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3CRGeneral
	->setUriPattern('typo3cr/[@controller]/[@action]')
	->setControllerComponentNamePattern('F3::@package::Admin::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Admin::View::@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3CR',
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

?>