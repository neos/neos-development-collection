<?php
namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Fusion\Exception as Exceptions;
use Neos\Fusion;

/**
 * Fusion Object Namespace Management
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ObjectNamespace
{
    /**
     * @var array
     */
    protected $mapping = [
        'default' => 'Neos.Fusion'
    ];

    /**
     * Sets the given alias to the specified namespace.
     *
     * The namespaces defined through this setter or through a "namespace" declaration
     * in one of the Fusions are used to resolve a fully qualified Fusion
     * object name while parsing Fusion code.
     *
     * The alias is the handle by wich the namespace can be referred to.
     * The namespace is, by convention, a package key which must correspond to a
     * namespace used in the prototype definitions for Fusion object types.
     *
     * The special alias "default" is used as a fallback for resolution of unqualified
     * Fusion object types.
     *
     * @param string $alias An alias for the given namespace, for example "neos"
     * @param string $namespace The namespace, for example "Neos.Neos"
     * @return void
     * @throws Fusion\Exception
     * @api
     */
    public function register(string $alias, string $namespace)
    {
        if (!is_string($alias)) {
            throw new Fusion\Exception('The alias of a namespace must be valid string!', 1180600696);
        }
        if (!is_string($namespace)) {
            throw new Fusion\Exception('The namespace must be of type string!', 1180600697);
        }
        $this->mapping[$alias] = $namespace;
    }

    /**
     * Get the fully qualified object type
     *
     * @param string $objectType
     * @return string
     */
    public function resolveFullyQualifiedObjectType(string $objectType): string
    {
        $objectTypeParts = explode(':', $objectType);
        if (!isset($objectTypeParts[1])) {
            $fullyQualifiedObjectType = $this->mapping['default'] . ':' . $objectTypeParts[0];
        } elseif (isset($this->mapping[$objectTypeParts[0]])) {
            $fullyQualifiedObjectType = $this->mapping[$objectTypeParts[0]] . ':' . $objectTypeParts[1];
        } else {
            $fullyQualifiedObjectType = $objectType;
        }

        return $fullyQualifiedObjectType;
    }

    /**
     * @param string $alias
     * @return string
     */
    public function resolveNamespace(string $alias): string
    {
        return (isset($this->mapping[$alias])) ? $this->mapping[$alias] : $alias;
    }

    /**
     * @return string
     */
    public function resolveDefaultNamespace(): string
    {
        return $this->mapping['default'];
    }
}
