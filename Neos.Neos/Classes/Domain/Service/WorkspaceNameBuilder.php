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

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final class WorkspaceNameBuilder
{
    private const PREFIX = 'user-';

    public static function fromAccountIdentifier(string $accountIdentifier): WorkspaceName
    {
        $name = preg_replace('/[^A-Za-z0-9\-]/', '-', self::PREFIX . $accountIdentifier);
        if (is_null($name)) {
            throw new \InvalidArgumentException(
                'Cannot convert account identifier ' . $accountIdentifier . ' to workspace name.',
                1645656253
            );
        }
        return WorkspaceName::transliterateFromString($name);
    }
}
