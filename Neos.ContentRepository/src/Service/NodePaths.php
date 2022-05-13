<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

/**
 * Provides basic logic concerning node paths.
 * @deprecated remove once contextPaths are extinct
 */
abstract class NodePaths
{
    /**
     * Regex pattern which matches a "context path", ie. a node path possibly containing context information such as the
     * workspace name. This pattern is used at least in the route part handler.
     */
    public const MATCH_PATTERN_CONTEXTPATH = '/^   # A Context Path consists of...
		(?>(?P<NodePath>                       # 1) a NODE PATH
			(?>
			\/ [a-z0-9\-]+ |                # Which either starts with a slash followed by a node name
			\/ |                            # OR just a slash (the root node)
			[a-z0-9\-]+                     # OR only a node name (if it is a relative path)
			)
			(?:                             #    and (optionally) more path-parts)
				\/
				[a-z0-9\-]+
			)*
		))
		(?:                                 # 2) a CONTEXT
			@                               #    which is delimited from the node path by the "@" sign
			(?>(?P<WorkspaceName>              #    followed by the workspace name (NON-EMPTY)
				[a-z0-9\-]+
			))
			(?:                             #    OPTIONALLY followed by dimension values
				;                           #    ... which always start with ";"
				(?P<Dimensions>
					(?>                     #        A Dimension Value is a key=value structure
						[a-zA-Z_]+
						=
						[^=&]+
					)
					(?>&(?-1))?             #        ... delimited by &
				)){0,1}
		){0,1}$/ix';

    /**
     * Splits the given context path into relevant information, which results in an array with keys:
     * "nodePath", "workspaceName", "dimensions"
     *
     * @param string $contextPath a context path including workspace and/or dimension information.
     * @return array<string,mixed> split information from the context path
     */
    public static function explodeContextPath(string $contextPath): array
    {
        preg_match(self::MATCH_PATTERN_CONTEXTPATH, $contextPath, $matches);
        if (!isset($matches['NodePath'])) {
            throw new \InvalidArgumentException('The given string was not a valid contextPath.', 1431281250);
        }

        $nodePath = $matches['NodePath'];
        $workspaceName = isset($matches['WorkspaceName']) && $matches['WorkspaceName'] !== ''
            ? $matches['WorkspaceName']
            : 'live';
        $dimensions = isset($matches['Dimensions'])
            ? self::parseDimensionValueStringToArray($matches['Dimensions'])
            : [];

        return [
            'nodePath' => $nodePath,
            'workspaceName' => $workspaceName,
            'dimensions' => $dimensions
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function parseDimensionValueStringToArray(string $dimensionValueString): array
    {
        parse_str($dimensionValueString, $dimensions);
        return array_map(function ($commaSeparatedValues) {
            return explode(',', $commaSeparatedValues);
        }, $dimensions);
    }
}
