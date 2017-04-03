<?php
namespace Neos\Neos\ViewHelpers\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
     */
    protected $authenticationProviderSettings;

    /**
     * Outputs a human friendly label for the authentication provider specified by $identifier
     *
     * @param string $identifier
     * @return string
     * @throws \Exception
     */
    public function render($identifier)
    {
        return (isset($this->authenticationProviderSettings[$identifier]['label']) ? $this->authenticationProviderSettings[$identifier]['label'] : $identifier);
    }
}
