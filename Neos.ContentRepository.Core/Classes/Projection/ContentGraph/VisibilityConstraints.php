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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Feature\NodeAttributes\Dto\Attribute;
use Neos\ContentRepository\Core\Feature\NodeAttributes\Dto\Attributes;

/**
 * The context parameters value object
 *
 * Maybe future: "Node Filter" tree or so as replacement of ReadNodePrivilege?
 *
 * @api
 */
final class VisibilityConstraints
{
    protected bool $disabledContentShown = false;

    private function __construct(bool $disabledContentShown)
    {
        $this->disabledContentShown = $disabledContentShown;
    }

    public function isDisabledContentShown(): bool
    {
        return $this->disabledContentShown;
    }

    public function restrictedAttributes(): Attributes
    {
        return $this->disabledContentShown ? Attributes::createEmpty() : Attributes::fromStringArray(['disabled']);
    }

    public function getHash(): string
    {
        return md5('disabled' . $this->disabledContentShown);
    }

    public static function withoutRestrictions(): VisibilityConstraints
    {
        return new VisibilityConstraints(true);
    }

    public static function frontend(): VisibilityConstraints
    {
        return new VisibilityConstraints(false);
    }
}
