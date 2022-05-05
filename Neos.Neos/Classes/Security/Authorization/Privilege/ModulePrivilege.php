<?php
namespace Neos\Neos\Security\Authorization\Privilege;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use Neos\Flow\Security\Exception\InvalidPolicyException;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * A privilege covering general access to Neos Backend Modules
 *
 * It matches if the matcher is equal to the (sub)module path ("<module>/<submodule>") in question
 */
class ModulePrivilege extends AbstractPrivilege implements MethodPrivilegeInterface
{
    /**
     * @var MethodPrivilegeInterface
     */
    private $methodPrivilege;

    /**
     * @var boolean
     */
    private $initialized = false;

    /**
     * @return void
     * @throws InvalidPolicyException
     */
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $moduleSettings = $this->objectManager->get(ConfigurationManager::class)
            ->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Neos.Neos.modules'
            );
        $targetModulePath = $this->getParsedMatcher();
        list($moduleName, $subModuleName) = array_pad(explode('/', $targetModulePath, 2), 2, null);
        if (!isset($moduleSettings[$moduleName])) {
            throw new InvalidPolicyException(sprintf(
                'The module "%s" specified in privilege target "%s" is not configured',
                $moduleName,
                $this->getPrivilegeTargetIdentifier()
            ), 1493206188);
        }
        if ($subModuleName !== null && !isset($moduleSettings[$moduleName]['submodules'][$subModuleName])) {
            throw new InvalidPolicyException(sprintf(
                'The module "%s" specified in privilege target "%s" is not configured',
                $targetModulePath,
                $this->getPrivilegeTargetIdentifier()
            ), 1493206192);
        }
        $targetModuleConfiguration = $subModuleName !== null
            ? $moduleSettings[$moduleName]['submodules'][$subModuleName]
            : $moduleSettings[$moduleName];
        if (!isset($targetModuleConfiguration['controller'])) {
            throw new \RuntimeException(sprintf(
                'The module "%s" specified in privilege target "%s" doesn\'t have a "controller" configured',
                $targetModulePath,
                $this->getPrivilegeTargetIdentifier()
            ), 1493206825);
        }

        $methodPrivilegeMatcher = 'method(public ' . ltrim($targetModuleConfiguration['controller'], '\\')
            . '->(?!initialize).*Action())';
        $methodPrivilegeTarget = new PrivilegeTarget(
            $this->privilegeTarget->getIdentifier() . '__methodPrivilege',
            MethodPrivilege::class,
            $methodPrivilegeMatcher
        );
        $methodPrivilegeTarget->injectObjectManager($this->objectManager);
        $this->methodPrivilege = $methodPrivilegeTarget->createPrivilege(
            $this->getPermission(),
            $this->getParameters()
        );
    }

    /**
     * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
     * related to this object.
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        $this->initialize();
        return $this->methodPrivilege->getCacheEntryIdentifier();
    }

    /**
     * Returns true, if this privilege covers the given subject
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if (!($subject instanceof ModulePrivilegeSubject) && !($subject instanceof MethodPrivilegeSubject)) {
            throw new InvalidPrivilegeTypeException(
                sprintf(
                    'Privileges of type "%s" only support subjects of type "%s" or "%s",'
                        . ' but we got a subject of type: "%s".',
                    self::class,
                    ModulePrivilegeSubject::class,
                    MethodPrivilegeSubject::class,
                    get_class($subject)
                ),
                1493130646
            );
        }
        $this->initialize();
        if ($subject instanceof MethodPrivilegeSubject) {
            return $this->methodPrivilege->matchesSubject($subject);
        }
        return $subject->getModulePath() === $this->getParsedMatcher();
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return boolean
     */
    public function matchesMethod($className, $methodName)
    {
        $this->initialize();
        return $this->methodPrivilege->matchesMethod($className, $methodName);
    }

    /**
     * @return PointcutFilterInterface
     */
    public function getPointcutFilterComposite()
    {
        $this->initialize();
        return $this->methodPrivilege->getPointcutFilterComposite();
    }
}
