<?php
namespace Neos\Neos\Tests\Unit\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Controller\Module\Administration\UsersController;
use Neos\Neos\Controller\Module\AdministrationController;
use Neos\Neos\Routing\BackendModuleRoutePartHandler;

/**
 * Testcase for the Backend Module Route Part Handler
 *
 */
class BackendModuleRoutePartHandlerTest extends UnitTestCase
{
    /**
     * Data provider for ... see below
     */
    public function requestPaths()
    {
        return array(
            'empty' => array('', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null),
            'unknown root module' => array('unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null),
            'unknown submodule' => array('unknown/module', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null),
            'unknown submodule with root module' => array('administration/unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null),
            'root module' =>  array('administration', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration', 'controller' => AdministrationController::class, 'action' => 'index')),
            'submodule' => array('administration/users', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'index')),
            'submodule with action' => array('administration/users/new', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'new')),
            'module without controller' => array('nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, null),
            'submodule without controller' => array('administration/nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, null),

            // Json requests
            'root module in json' =>  array('administration.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration', 'controller' => AdministrationController::class, 'action' => 'index', 'format' => 'json')),
            'submodule in json' => array('administration/users.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'index', 'format' => 'json')),
            'submodule with action in json' => array('administration/users/new.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, array('module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'new', 'format' => 'json')),
        );
    }

    /**
     * @test
     * @dataProvider requestPaths
     */
    public function matchFindsCorrectValues($requestPath, $matchResult, $expectedValue)
    {
        $routePartHandler = new BackendModuleRoutePartHandler();
        $routePartHandler->setName('module');

        $routePartHandler->injectSettings(array(
            'modules' => array(
                'administration' => array(
                    'controller' => AdministrationController::class,
                    'submodules' => array(
                        'users' => array(
                            'controller' => UsersController::class
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
