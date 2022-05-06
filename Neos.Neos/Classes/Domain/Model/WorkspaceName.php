<?php
declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName as ContentRepositoryWorkspaceName;

/**
 * The workspace name value for Neos contexts
 * Directly translatable to CR workspace names
 */
final class WorkspaceName implements \JsonSerializable
{
    const PREFIX = 'user-';
    const SUFFIX_DELIMITER = '_';

    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function fromAccountIdentifier(string $accountIdentifier): self
    {
        $name = preg_replace('/[^A-Za-z0-9\-]/', '-', self::PREFIX . $accountIdentifier);
        if (is_null($name)) {
            throw new \InvalidArgumentException(
                'Cannot convert account identifier ' . $accountIdentifier . ' to workspace name.',
                1645656253
            );
        }

        return new self($name);
    }

    /**
     * @param array<string,mixed> $takenWorkspaceNames
     */
    public function increment(array $takenWorkspaceNames): self
    {
        $name = $this->name;
        $i = 1;
        while (array_key_exists($name, $takenWorkspaceNames)) {
            $name = $this->name . self::SUFFIX_DELIMITER . $i;
            $i++;
        }

        if ($i > 1) {
            return new WorkspaceName($name);
        } else {
            return $this;
        }
    }

    public function toContentRepositoryWorkspaceName(): ContentRepositoryWorkspaceName
    {
        return ContentRepositoryWorkspaceName::fromString($this->name);
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
