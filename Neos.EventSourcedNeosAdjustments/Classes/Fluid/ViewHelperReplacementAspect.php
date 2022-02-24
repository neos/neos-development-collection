<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Fluid;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ViewHelperReplacementAspect
{
    /**
     * @var array<string,string>
     */
    protected array $viewHelperClassMapping = [
        \Neos\Neos\ViewHelpers\Link\NodeViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Link\NodeViewHelper::class,
        \Neos\Neos\ViewHelpers\Uri\NodeViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Uri\NodeViewHelper::class,
        \Neos\Neos\ViewHelpers\ContentElement\EditableViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\ContentElement\EditableViewHelper::class,
        \Neos\Neos\ViewHelpers\ContentElement\WrapViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\ContentElement\WrapViewHelper::class,
        \Neos\Neos\ViewHelpers\Rendering\InBackendViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Rendering\InBackendViewHelper::class,
        \Neos\Neos\ViewHelpers\Rendering\InEditModeViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Rendering\InEditModeViewHelper::class,
        \Neos\Neos\ViewHelpers\Rendering\InPreviewModeViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Rendering\InPreviewModeViewHelper::class,
        \Neos\Neos\ViewHelpers\Rendering\LiveViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Rendering\LiveViewHelper::class,
        \Neos\Neos\ViewHelpers\Backend\DocumentBreadcrumbPathViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Backend\DocumentBreadcrumbPathViewHelper::class,
        \Neos\Neos\ViewHelpers\Node\ClosestDocumentViewHelper::class
        => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Node\ClosestDocumentViewHelper::class
    ];

    /**
     * @Flow\Around("method(Neos\FluidAdaptor\Core\ViewHelper\ViewHelperResolver->createViewHelperInstanceFromClassName())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function createViewHelperInstanceFromClassName(JoinPointInterface $joinPoint)
    {
        $viewHelperClassName = $joinPoint->getMethodArgument('viewHelperClassName');

        if (isset($this->viewHelperClassMapping[$viewHelperClassName])) {
            $joinPoint->setMethodArgument('viewHelperClassName', $this->viewHelperClassMapping[$viewHelperClassName]);
        }
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
