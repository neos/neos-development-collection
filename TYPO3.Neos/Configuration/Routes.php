<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->fallback
	->setUriPattern('')
	->setControllerComponentNamePattern('F3::@package::Frontend::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Frontend::View::@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index',
		)
	);

$c->TYPO3Frontend
	->setUriPattern('[page].[@format]')
	->setControllerComponentNamePattern('F3::@package::Frontend::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Frontend::View::@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index',
			'page' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3Backend
	->setUriPattern('typo3')
	->setControllerComponentNamePattern('F3::@package::Backend::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Backend::View::@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index'
		)
	);

/*************************************************************************
 * Routes definitions for the services
 */


$c->TYPO3Route_ServiceWithControllerOnly
	->setUriPattern('typo3/service/v1/[@controller]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@format' => 'json'
		)
	);

$c->TYPO3Route_ServiceWithControllerAndFormat
	->setUriPattern('typo3/service/v1/[@controller].[@format]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3'
		)
	);
$c->TYPO3Route_ServiceWithControllerAndFormatAndDummy
	->setUriPattern('typo3/service/v1/[@controller].[@format]?[@dummy]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3'
		)
	);

$c->TYPO3Route_ServiceWithControllerAndId
	->setUriPattern('typo3/service/v1/[@controller]/[id]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@format' => 'json'
		)
	);

$c->TYPO3Route_ServiceWithControllerAndIdAndFormat
	->setUriPattern('typo3/service/v1/[@controller]/[id].[@format]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
		)
	);

$c->TYPO3Route_ServiceWithControllerAndIdAndFormatAndDummy
	->setUriPattern('typo3/service/v1/[@controller]/[id].[@format]?[@dummy]')
	->setControllerComponentNamePattern('F3::@package::Service::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Service::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
		)
	);

/*************************************************************************
 * Other routes (for experimentation)
 */

$c->TYPO3Route5
	->setUriPattern('typo3/setup')
	->setControllerComponentNamePattern('F3::TYPO3::Backend::Controller::DefaultController')
	->setDefaults(
		array(
			'@action' => 'setup',
		)
	);

$c->TYPO3Route7
	->setUriPattern('typo3/login')
	->setControllerComponentNamePattern('F3::@package::Backend::Controller::@controllerController')
	->setViewComponentNamePattern('F3::@package::Backend::View::@controller::@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Login',
			'@action' => 'index',
		)
	);
?>