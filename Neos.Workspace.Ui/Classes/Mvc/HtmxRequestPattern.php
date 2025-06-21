<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Mvc;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPatternInterface;

/**
 * @internal for communication within configured backend modules only
 */
final class HtmxRequestPattern implements RequestPatternInterface
{
    private array $matchingPackageKeys = [];

    public function __construct(array $options) {
        $this->matchingPackageKeys = array_keys(
            array_filter(
                $options['matchingPackageKeys'] ?? [],
                static fn($value) => (bool)$value,
            )
        );
    }

    public function matchRequest(ActionRequest $request): bool
    {
        return $request->getFormat() === 'htmx'
            && in_array($request->getControllerPackageKey(), $this->matchingPackageKeys, true);
    }
}
