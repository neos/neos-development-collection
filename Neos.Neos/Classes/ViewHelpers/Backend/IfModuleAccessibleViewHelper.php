<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\ViewHelpers\Backend;

use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper;
use Neos\Neos\Security\Authorization\Privilege\ModulePrivilege;
use Neos\Neos\Security\Authorization\Privilege\ModulePrivilegeSubject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Condition ViewHelper that can evaluate whether the currently authenticated user can access a given Backend module
 *
 * Note: This is a quick fix for https://github.com/neos/neos-development-collection/issues/2854
 * that will be obsolete once the whole Backend module logic is rewritten
 */
class IfModuleAccessibleViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('modulePath', 'string', 'Path of the module to evaluate', true);
        $this->registerArgument('moduleConfiguration', 'array', 'Configuration of the module to evaluate', true);
    }

    /**
     * renders <f:then> child if access to the given module is accessible, otherwise renders <f:else> child.
     *
     * @return string the rendered then/else child nodes depending on the access
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments, $this->renderingContext)) {
            return $this->renderThenChild();
        }

        return $this->renderElseChild();
    }

    /**
     * @param array<string,mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        return static::renderResult(
            static::evaluateCondition($arguments, $renderingContext),
            $arguments,
            $renderingContext
        );
    }

    /**
     * @param array<string,mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    protected static function evaluateCondition($arguments, RenderingContextInterface $renderingContext)
    {
        if (!$renderingContext instanceof RenderingContext) {
            return false;
        }
        if (
            isset($arguments['moduleConfiguration']['enabled'])
            && $arguments['moduleConfiguration']['enabled'] === false
        ) {
            return false;
        }
        $objectManager = $renderingContext->getObjectManager();
        /** @var Context $securityContext */
        $securityContext = $objectManager->get(Context::class);
        if ($securityContext !== null && !$securityContext->canBeInitialized()) {
            return false;
        }
        /** @var PrivilegeManagerInterface $privilegeManager */
        $privilegeManager = $objectManager->get(PrivilegeManagerInterface::class);
        if (
            !$privilegeManager->isGranted(
                ModulePrivilege::class,
                new ModulePrivilegeSubject(
                    $arguments['modulePath']
                )
            )
        ) {
            return false;
        }
        if (isset($arguments['moduleConfiguration']['privilegeTarget'])) {
            return $privilegeManager->isPrivilegeTargetGranted($arguments['moduleConfiguration']['privilegeTarget']);
        }
        return true;
    }
}
