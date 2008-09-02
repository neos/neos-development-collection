<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->fallback
	->setUrlPattern('[dummy]')
	->setControllerComponentNamePattern('F3_@package_Frontend_Controller_@controller')
	->setDefaults(
		array(
			'dummy' => 'foo',
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'default',
		)
	);

$c->TYPO3Route1
	->setUrlPattern('[page].[@format]')
	->setControllerComponentNamePattern('F3_@package_Frontend_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'default',
			'page' => 'index',
			'@format' => 'html'
		)
	);

$c->TYPO3Route2
	->setUrlPattern('typo3/[section]/[module]')
	->setControllerComponentNamePattern('F3_@package_Backend_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@controller' => 'Default',
			'@action' => 'default',
			'section' => 'System',
			'module' => 'Welcome'
		)
	);

$c->TYPO3Route3
	->setUrlPattern('typo3/service/[@controller]')
	->setControllerComponentNamePattern('F3_@package_Service_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
			'@format' => 'html'
		)
	);

$c->TYPO3Route4
	->setUrlPattern('typo3/service/[@controller].[@format]')
	->setControllerComponentNamePattern('F3_@package_Service_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3',
		)
	);
?>