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
        return [
            'empty' => ['', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null],
            'unknown root module' => ['unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null],
            'unknown submodule' => ['unknown/module', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null],
            'unknown submodule with root module' => ['administration/unknown', BackendModuleRoutePartHandler::MATCHRESULT_NOSUCHMODULE, null],
            'root module' =>  ['administration', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration', 'controller' => AdministrationController::class, 'action' => 'index']],
            'submodule' => ['administration/users', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'index']],
            'submodule with action' => ['administration/users/new', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'new']],
            'module without controller' => ['nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, null],
            'submodule without controller' => ['administration/nocontroller', BackendModuleRoutePartHandler::MATCHRESULT_NOCONTROLLER, null],

            // Json requests
            'root module in json' =>  ['administration.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration', 'controller' => AdministrationController::class, 'action' => 'index', 'format' => 'json']],
            'submodule in json' => ['administration/users.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'index', 'format' => 'json']],
            'submodule with action in json' => ['administration/users/new.json', BackendModuleRoutePartHandler::MATCHRESULT_FOUND, ['module' => 'administration/users', 'controller' => UsersController::class, 'action' => 'new', 'format' => 'json']],
        ];
    }

    /**
     * @test
     * @dataProvider requestPaths
     */
    public function matchFindsCorrectValues($requestPath, $matchResult, $expectedValue)
    {
        $routePartHandler = new BackendModuleRoutePartHandler();
        $routePartHandler->setName('module');

        $routePartHandler->injectSettings([
            'modules' => [
                'administration' => [
                    'controller' => AdministrationController::class,
                    'submodules' => [
                        'users' => [
                            'controller' => UsersController::class
                        ],
                        'nocontroller' => []
                    ],
                ],
                'nocontroller' => []
            ]
        ]);

        $matches = $routePartHandler->match($requestPath);
        $value = $routePartHandler->getValue();

        self::assertSame($matchResult, $matches);
        self::assertEquals($expectedValue, $value);
    }
}
