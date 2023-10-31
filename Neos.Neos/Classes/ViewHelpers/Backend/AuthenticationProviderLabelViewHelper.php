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

namespace Neos\Neos\ViewHelpers\Backend;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a label for the given authentication provider identifier
 */
class AuthenticationProviderLabelViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="security.authentication.providers")
     * @var array
     * @phpstan-var array<string,mixed>
     */
    protected $authenticationProviderSettings;

    /**
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('identifier', 'string', 'The identifier to render the label for', true);
    }

    /**
     * Outputs a human friendly label for the authentication provider specified by $identifier
     *
     * @return string
     * @throws \Exception
     */
    public function render(): string
    {
        $identifier = $this->arguments['identifier'];
        return ($this->authenticationProviderSettings[$identifier]['label'] ?? $identifier);
    }
}
