<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphSiblingQueryMode
{
    const MODE_ALL = 'all';
    const MODE_ONLY_PRECEDING = 'onlyPreceding';
    const MODE_ONLY_SUCCEEDING = 'onlySucceeding';

    private string $value;

    private function __construct(
        string $value
    ){
        $this->value = $value;
    }

    public static function all(): self
    {
        return new self(self::MODE_ALL);
    }

    public static function onlyPreceding(): self
    {
        return new self(self::MODE_ONLY_PRECEDING);
    }

    public static function onlySucceeding(): self
    {
        return new self(self::MODE_ONLY_SUCCEEDING);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function renderCondition(): string
    {
        switch ($this->value) {
            case self::MODE_ALL:
                return '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors)';
            case self::MODE_ONLY_PRECEDING:
                return '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors[:(array_position(sh.childnodeanchors, sh.relationanchorpoint))])';
            case self::MODE_ONLY_SUCCEEDING:
                return '
    AND sn.relationanchorpoint = ANY(sh.childnodeanchors[(array_position(sh.childnodeanchors, sh.relationanchorpoint)):])';
            default:
                return '';
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
