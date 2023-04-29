<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\FusionObjects\Helpers\LazyProps;

/**
 * A Fusion Component-Object
 *
 * All properties except ``renderer`` are pushed into a context variable ``props``
 * afterwards the ``renderer`` is evaluated
 *
 * //fusionPath renderer The variable to display a dump of.
 * //fusionPath * generic Fusion values that will be added to the ``props`` object in the context
 * @api
 */
class ComponentImplementation extends JoinImplementation
{
    /**
     * Properties that are ignored and not included into the ``props`` context
     *
     * @var array
     */
    protected $ignoreProperties = ['__meta', 'renderer'];

    /**
     * Evaluate the fusion-keys and transfer the result into the context as ``props``
     * afterwards evaluate the ``renderer`` with this context
     *
     * @return mixed
     */
    public function evaluate()
    {
        $context = $this->runtime->getCurrentContext();
        $renderContext = $this->prepare($context);
        $result = $this->render($renderContext);
        return $result;
    }

    /**
     * Prepare the context for the renderer
     *
     * @param array $context
     * @return array
     */
    protected function prepare(array $context): array
    {
        $context['props'] = $this->getProps($context);
        return $context;
    }

    /**
     * Calculate the component props
     *
     * @param array $context
     * @return \ArrayAccess
     */
    protected function getProps(array $context): \ArrayAccess
    {
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();
        $props = new LazyProps($this, $this->path, $this->runtime, $sortedChildFusionKeys, $context);
        return $props;
    }

    /**
     * Evaluate the renderer with the give context and return
     *
     * @param array $context
     * @return mixed
     */
    protected function render(array $context)
    {
        $this->runtime->pushContextArray($context);
        $result = $this->runtime->render($this->path . '/renderer');
        $this->runtime->popContext();
        return $result;
    }
}
