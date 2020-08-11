<?php
namespace Neos\Fusion\FusionObjects\Helpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Parser\SyntaxTree\TemplateObjectAccessInterface;
use Neos\Fusion\Exception\UnsupportedProxyMethodException;
use Neos\Fusion\FusionObjects\TemplateImplementation;
use Neos\Fusion\Exception as FusionException;

/**
 * A proxy object representing a Fusion path inside a Fluid Template. It allows
 * to render arbitrary Fusion objects or Eel expressions using the already-known
 * property path syntax.
 *
 * It wraps a part of the Fusion tree which does not contain Fusion objects or Eel expressions.
 *
 * This class is instantiated inside TemplateImplementation and is never used outside.
 */
class FusionPathProxy implements TemplateObjectAccessInterface, \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Reference to the Fusion Runtime which controls the whole rendering
     *
     * @var \Neos\Fusion\Core\Runtime
     */
    protected $fusionRuntime;

    /**
     * Reference to the "parent" Fusion object
     *
     * @var TemplateImplementation
     */
    protected $templateImplementation;

    /**
     * The Fusion path this object proxies
     *
     * @var string
     */
    protected $path;

    /**
     * This is a part of the Fusion tree built when evaluating $this->path.
     *
     * @var array
     */
    protected $partialFusionTree;

    /**
     * Constructor.
     *
     * @param TemplateImplementation $templateImplementation
     * @param string $path
     * @param array $partialFusionTree
     */
    public function __construct(TemplateImplementation $templateImplementation, $path, array $partialFusionTree)
    {
        $this->templateImplementation = $templateImplementation;
        $this->fusionRuntime = $templateImplementation->getRuntime();
        $this->path = $path;
        $this->partialFusionTree = $partialFusionTree;
    }

    /**
     * true if a given subpath exists, false otherwise.
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->partialFusionTree[$offset]);
    }

    /**
     * Return the object at $offset; evaluating simple types right away, and
     * wrapping arrays into ourselves again.
     *
     * @param string $offset
     * @return mixed|FusionPathProxy
     */
    public function offsetGet($offset)
    {
        if (!isset($this->partialFusionTree[$offset])) {
            return null;
        }
        if (!is_array($this->partialFusionTree[$offset])) {
            // Simple type; we call "evaluate" nevertheless to make sure processors are applied.
            return $this->fusionRuntime->evaluate($this->path . '/' . $offset);
        } else {
            // arbitrary array (could be Eel expression, Fusion object, nested sub-array) again, so we wrap it with ourselves.
            return new FusionPathProxy($this->templateImplementation, $this->path . '/' . $offset, $this->partialFusionTree[$offset]);
        }
    }

    /**
     * Stub to implement the ArrayAccess interface cleanly
     *
     * @param string $offset
     * @param mixed $value
     * @throws UnsupportedProxyMethodException
     */
    public function offsetSet($offset, $value)
    {
        throw new UnsupportedProxyMethodException('Setting a property of a path proxy not supported. (tried to set: ' . $this->path . ' -- ' . $offset . ')', 1372667221);
    }

    /**
     * Stub to implement the ArrayAccess interface cleanly
     *
     * @param string $offset
     * @throws UnsupportedProxyMethodException
     */
    public function offsetUnset($offset)
    {
        throw new UnsupportedProxyMethodException('Unsetting a property of a path proxy not supported. (tried to unset: ' . $this->path . ' -- ' . $offset . ')', 1372667331);
    }

    /**
     * Post-Processor which is called whenever this object is encountered in a Fluid
     * object access.
     *
     * Evaluates Fusion objects and eel expressions.
     *
     * @return FusionPathProxy|mixed
     * @throws FusionException
     */
    public function objectAccess()
    {
        if (!$this->fusionRuntime->canRender($this->path)) {
            return $this;
        }

        try {
            return $this->fusionRuntime->evaluate($this->path, $this->templateImplementation);
        } catch (\Exception $exception) {
            return $this->fusionRuntime->handleRenderingException($this->path, $exception);
        }
    }

    /**
     * Iterates through all subelements.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $evaluatedArray = [];
        foreach ($this->partialFusionTree as $key => $value) {
            if (!is_array($value)) {
                $evaluatedArray[$key] = $value;
            } elseif (isset($value['__objectType'])) {
                $evaluatedArray[$key] = $this->fusionRuntime->evaluate($this->path . '/' . $key);
            } elseif (isset($value['__eelExpression'])) {
                $evaluatedArray[$key] = $this->fusionRuntime->evaluate($this->path . '/' . $key, $this->templateImplementation);
            } else {
                $evaluatedArray[$key] = new FusionPathProxy($this->templateImplementation, $this->path . '/' . $key, $this->partialFusionTree[$key]);
            }
        }
        return new \ArrayIterator($evaluatedArray);
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->partialFusionTree);
    }
}
