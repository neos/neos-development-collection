<?php

declare(strict_types=1);

use Neos\Flow\Mvc\Controller\ActionController;

/**
 * Hack to be able to instantiate a at runtime declared controller.
 *
 * This is normally not possible because there is no property injection for injections of the ActionController and parents
 * and also the class would be unknown to the object manager.
 *
 * Maybe we might get rid of this class once we also allow anonymous classes extending proxied objects as this is really similar.
 */
class BehatRuntimeActionController extends ActionController
{
    public static function registerInstance(): void
    {
        $controllerInstance = new static();

        /** 1.) take care of injecting all necessary properties of the base "ActionController"
         ** note any @Flow\Inject in the fixture will not work.
         *
         ** - validatorResolver
         ** - mvcPropertyMappingConfigurationService
         ** - viewConfigurationManager
         ** - objectManager
         **
         ** run flow property injection code of parent class ActionController not ActionController_Original manually
         ** as the extended classes is not proxied and doesnt call $this->Flow_Proxy_injectProperties();
         */
        $ref = new \ReflectionClass(get_parent_class($controllerInstance));
        $method = $ref->getMethod('Flow_Proxy_injectProperties');
        $method->invoke($controllerInstance);

        /** 2.) hack to avoid: No controller could be resolved which would match your request
         ** which will be caused by {@see \Neos\Flow\Mvc\ActionRequest::getControllerObjectName()}
         ** calling the object managers {@see \Neos\Flow\ObjectManagement\ObjectManager::getCaseSensitiveObjectName()}
         ** that's why we register the controller singleton (i) and set its lower case name (l)
         */
        $objects = \Neos\Utility\ObjectAccess::getProperty($controllerInstance->objectManager, 'objects', true);
        $objects[$controllerInstance::class]['i'] = $controllerInstance;
        $objects[$controllerInstance::class]['l'] = strtolower($controllerInstance::class);
        $controllerInstance->objectManager->setObjects($objects);
    }

    public static function getPublicActionMethods($objectManager)
    {
        /** 3.) hack, as this class is not proxied reflection doesnt work and doesnt return the desired public action methods
         ** instead the ActionController's compiled method will be called which returns an empty array */
        return array_fill_keys(get_class_methods(static::class), true);
    }
}
