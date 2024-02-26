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

namespace Neos\ContentRepositoryRegistry\Configuration;

/**
 * Implementation detail of {@see NodeTypeEnrichmentService}
 *
 * @internal
 */
class SuperTypeConfigResolver
{
    /**
     * @var array<string, list<string>>
     */
    private array $superTypeCache;

    /**
     * @param array<string, mixed> $fullConfiguration
     */
    public function __construct(private readonly array $fullConfiguration)
    {
    }

    /**
     * @param string $nodeTypeName
     * @return list<string>
     */
    public function getSuperTypesFor(string $nodeTypeName): array
    {
        if (isset($this->superTypeCache[$nodeTypeName])) {
            return $this->superTypeCache[$nodeTypeName];
        }

        $superTypesConfiguration = $this->fullConfiguration[$nodeTypeName]['superTypes'] ?? [];
        $enabledSuperTypesRecursively = [$nodeTypeName];
        foreach ($superTypesConfiguration as $superTypeName => $isEnabled) {
            if ($isEnabled === true) {
                $parentSuperTypes = $this->getSuperTypesFor($superTypeName);
                $enabledSuperTypesRecursively = array_merge($enabledSuperTypesRecursively, $parentSuperTypes);
            }
        }

        $this->superTypeCache[$nodeTypeName] = $enabledSuperTypesRecursively;
        return $enabledSuperTypesRecursively;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLocalConfiguration(string $nodeTypeName): array
    {
        return $this->fullConfiguration[$nodeTypeName] ?? [];
    }
}
