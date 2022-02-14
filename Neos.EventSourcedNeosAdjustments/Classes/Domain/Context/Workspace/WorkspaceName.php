<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Workspace;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain as ContentRepository;

/**
 * The workspace name value for Neos contexts
 * Directly translatable to CR workspace names
 */
final class WorkspaceName implements \JsonSerializable
{
    const PREFIX = 'user-';
    const SUFFIX_DELIMITER = '_';

    /**
     * @var string
     */
    protected $name;


    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $accountIdentifier
     * @return WorkspaceName
     */
    public static function fromAccountIdentifier(string $accountIdentifier): WorkspaceName
    {
        return new WorkspaceName(preg_replace('/[^A-Za-z0-9\-]/', '-', self::PREFIX . $accountIdentifier));
    }

    /**
     * @param array $takenWorkspaceNames
     * @return WorkspaceName
     */
    public function increment(array $takenWorkspaceNames): WorkspaceName
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

    public function toContentRepositoryWorkspaceName(): ContentRepository\ValueObject\WorkspaceName
    {
        return ContentRepository\ValueObject\WorkspaceName::instance($this->name);
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
