<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3_Fallback
	->setUriPattern('')
	->setControllerObjectNamePattern('F3\@package\Frontend\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Frontend\View\@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index',
		)
	);

$c->TYPO3_Frontend
	->setUriPattern('[page].[@format]')
	->setControllerObjectNamePattern('F3\@package\Frontend\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Frontend\View\@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index',
			'page' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3_Backend
	->setUriPattern('typo3')
	->setControllerObjectNamePattern('F3\@package\Backend\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Backend\View\@controller@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'index'
		)
	);

$c->TYPO3_BackendLogin
	->setUriPattern('login')
	->setControllerObjectNamePattern('F3\@package\Backend\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Backend\View\@controller@action@format')
	->setDefaults(
	array(
		'@package' => 'TYPO3',
		'@controller' => 'Login',
		'@action' => 'index',
		'@format' => 'html'
	)
);

/*************************************************************************
 * Routes definitions for the services
 */


$c->TYPO3_ServiceWithControllerOnly
	->setUriPattern('typo3/service/v1/[@controller]')
	->setControllerObjectNamePattern('F3\@package\Service\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Service\View\@controller\@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@format' => 'json'
		)
	);

$c->TYPO3_ServiceWithControllerAndFormat
	->setUriPattern('typo3/service/v1/[@controller].[@format]')
	->setControllerObjectNamePattern('F3\@package\Service\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Service\View\@controller\@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3'
		)
	);

$c->TYPO3_ServiceWithControllerAndId
	->setUriPattern('typo3/service/v1/[@controller]/[id]')
	->setControllerObjectNamePattern('F3\@package\Service\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Service\View\@controller\@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@format' => 'json'
		)
	);

$c->TYPO3_ServiceWithControllerAndIdAndFormat
	->setUriPattern('typo3/service/v1/[@controller]/[id].[@format]')
	->setControllerObjectNamePattern('F3\@package\Service\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Service\View\@controller\@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
		)
	);

/*************************************************************************
 * Other routes (for experimentation)
 */

$c->TYPO3_Route5
	->setUriPattern('typo3/setup')
	->setControllerObjectNamePattern('F3\TYPO3\Backend\Controller\DefaultController')
	->setDefaults(
		array(
			'@action' => 'setup',
		)
	);

$c->TYPO3_Route7
	->setUriPattern('typo3/login')
	->setControllerObjectNamePattern('F3\@package\Backend\Controller\@controllerController')
	->setViewObjectNamePattern('F3\@package\Backend\View\@controller\@action@format')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Login',
			'@action' => 'index',
		)
	);
?>