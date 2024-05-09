<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion;
use Neos\Utility\Arrays;

/**
 * @internal
 */
class MergedArrayTree
{
    public function __construct(
        protected array $tree
    ) {
    }

    public static function pathIsPrototype(array $path): bool
    {
        return ($path[count($path) - 2] ?? null) === '__prototypes';
    }

    public static function getParentPath(array $path): array
    {
        if (self::pathIsPrototype($path)) {
            return array_slice($path, 0, -2);
        }
        return array_slice($path, 0, -1);
    }

    public function getTree(): array
    {
        return $this->tree;
    }

    public function removeValueInTree(array $path): void
    {
        $this->tree = Arrays::unsetValueByPath($this->tree, $path);
        $this->setValueInTree($path, ['__stopInheritanceChain' => true]);
    }

    public function copyValueInTree(array $targetPath, array $sourcePath): void
    {
        $originalValue = Arrays::getValueByPath($this->tree, $sourcePath);
        $this->setValueInTree($targetPath, $originalValue);
    }

    /**
     * @param scalar|null|array $value The value to assign, either a scalar type or an array with __eelExpression etc.
     */
    public function setValueInTree(array $path, $value): void
    {
        self::arraySetOrMergeValueByPathWithCallback($this->tree, $path, $value, static function ($simpleType) {
            return [
                '__value' => $simpleType,
                '__eelExpression' => null,
                '__objectType' => null
            ];
        });
    }

    protected static function arraySetOrMergeValueByPathWithCallback(array &$subject, array $path, mixed $value, callable $toArray): void
    {
        // points to the current path element, but inside the tree.
        $pointer = &$subject;
        foreach ($path as $pathSegment) {
            // can be null because `&$foo['undefined'] === null`
            if ($pointer === null) {
                $pointer = [];
            }
            if (is_array($pointer) === false) {
                $pointer = $toArray($pointer);
            }
            // set pointer to current path (we can access undefined indexes due to &)
            $pointer = &$pointer[$pathSegment];
        }
        // we got a reference &$pointer of the $path in the $subject array, setting the final value:
        if (is_array($pointer)) {
            $arrayValue = is_array($value) ? $value : $toArray($value);
            $pointer = Arrays::arrayMergeRecursiveOverrule($pointer, $arrayValue);
            return;
        }
        $pointer = $value;
    }

    /**
     * Precalculate merged configuration for inherited prototypes.
     *
     * @throws Fusion\Exception
     */
    public function buildPrototypeHierarchy(): void
    {
        if (isset($this->tree['__prototypes']) === false) {
            return;
        }

        foreach (array_keys($this->tree['__prototypes']) as $prototypeName) {
            $prototypeInheritanceHierarchy = [];
            $currentPrototypeName = $prototypeName;
            while (isset($this->tree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'])) {
                $currentPrototypeName = $this->tree['__prototypes'][$currentPrototypeName]['__prototypeObjectName'];
                array_unshift($prototypeInheritanceHierarchy, $currentPrototypeName);
                if ($prototypeName === $currentPrototypeName) {
                    throw new Fusion\Exception(sprintf('Recursive inheritance found for prototype "%s". Prototype chain: %s', $prototypeName, implode(' < ', array_reverse($prototypeInheritanceHierarchy))), 1492801503);
                }
            }

            if (count($prototypeInheritanceHierarchy)) {
                // prototype chain from most *general* to most *specific* WITHOUT the current node type!
                $this->tree['__prototypes'][$prototypeName]['__prototypeChain'] = $prototypeInheritanceHierarchy;
            }
        }
    }
}
