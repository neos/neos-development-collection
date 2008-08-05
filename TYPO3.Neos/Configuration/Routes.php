<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3Route1
	->setUrlPattern('[page]/[@format]')
	->setControllerComponentNamePattern('F3_@package_Frontend_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'Default',
			'page' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3Route2
	->setUrlPattern('TYPO3/[section]/[module]')
	->setControllerComponentNamePattern('F3_@package_Backend_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'Default',
			'section' => 'System',
			'module' => 'Welcome'
		)
	);

$c->TYPO3Route3
	->setUrlPattern('TYPO3/Service/[@controller]')
	->setControllerComponentNamePattern('F3_@package_Service_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3_Service',
		)
	);

$c->TYPO3RouteTesting->
	setUrlPattern('Testing')->
	setDefaults(
		array(
			'@package' => 'Testing',
			'@controller' => 'Default',
			'@action' => 'Default',
			'@format' => 'html'
		)
	);

?>