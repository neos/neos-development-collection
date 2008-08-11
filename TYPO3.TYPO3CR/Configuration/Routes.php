<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration for the TYPO3 package                             *
 *                                                                        *
 *                                                                        */

$c->TYPO3CRAdmin
	->setUrlPattern('TYPO3CR/[@controller]/[@action]')
	->setControllerComponentNamePattern('F3_@package_Admin_Controller_@controller')
	->setDefaults(
		array(
			'@package' => 'TYPO3CR',
			'@controller' => 'Default',
			'@action' => 'Default',
			'@format' => 'html'
		)
	);

?>