<?php
namespace TYPO3\Neos\Tests\Unit\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Neos\Routing\BackendModuleRoutePartHandler;

/**
 * Testcase for the Backend Module Route Part Handler
 *
 */
class BackendModuleRoutePartHandlerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider for ... see below
	 */
	public function requestPaths() {
		return array(
			'empty' => array('', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, NULL),
			'unknown root module' => array('unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, NULL),
			'unknown submodule' => array('unknown/module', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, NULL),
			'unknown submodule with root module' => array('administration/unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, NULL),
			'root module' =>  array('administration', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration', 'controller' => 'TYPO3\Neos\Controller\Module\AdministrationController', 'action' => 'index')),
			'submodule' => array('administration/users', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => 'TYPO3\Neos\Controller\Module\Administration\UsersController', 'action' => 'index')),
			'submodule with action' => array('administration/users/new', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => 'TYPO3\Neos\Controller\Module\Administration\UsersController', 'action' => 'new')),
			'module without controller' => array('nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, NULL),
			'submodule without controller' => array('administration/nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, NULL),

			// Json requests
			'root module in json' =>  array('administration.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration', 'controller' => 'TYPO3\Neos\Controller\Module\AdministrationController', 'action' => 'index', 'format' => 'json')),
			'submodule in json' => array('administration/users.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => 'TYPO3\Neos\Controller\Module\Administration\UsersController', 'action' => 'index', 'format' => 'json')),
			'submodule with action in json' => array('administration/users/new.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => 'TYPO3\Neos\Controller\Module\Administration\UsersController', 'action' => 'new', 'format' => 'json')),
		);
	}

	/**
	 * @test
	 * @dataProvider requestPaths
	 */
	public function matchFindsCorrectValues($requestPath, $matchResult, $expectedValue) {
		$routePartHandler = new BackendModuleRoutePartHandler();
		$routePartHandler->setName('module');

		$routePartHandler->injectSettings(array(
			'modules' => array(
				'administration' => array(
					'controller' => 'TYPO3\Neos\Controller\Module\AdministrationController',
					'submodules' => array(
						'users' => array(
							'controller' => 'TYPO3\Neos\Controller\Module\Administration\UsersController'
						),
						'nocontroller' => array()
					),
				),
				'nocontroller' => array()
			)
		));

		$matches = $routePartHandler->match($requestPath);
		$value = $routePartHandler->getValue();

		$this->assertSame($matchResult, $matches);
		$this->assertEquals($expectedValue, $value);
	}

}
