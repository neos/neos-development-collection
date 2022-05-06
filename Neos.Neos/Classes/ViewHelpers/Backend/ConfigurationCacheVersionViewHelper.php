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
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering the current version identifier for the
 * configuration cache.
 */
class ConfigurationCacheVersionViewHelper extends AbstractViewHelper
{
    /**
     * @var StringFrontend
     */
    protected $configurationCache;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @return string The current cache version identifier
     */
    public function render(): string
    {
        /** @var ?Account $account */
        $account = $this->securityContext->getAccount();

        // Get all roles and sort them by identifier
        $roles = $account ? array_map(static fn ($role) => $role->getIdentifier(), $account->getRoles()) : [];
        sort($roles);

        // Use the roles combination as cache key to allow multiple users sharing the same configuration version
        $configurationIdentifier = md5(implode('_', $roles));
        $cacheKey = 'ConfigurationVersion_' . $configurationIdentifier;
        /** @var string|false $version */
        $version = $this->configurationCache->get($cacheKey);

        if ($version === false) {
            $version = (string)time();
            $this->configurationCache->set($cacheKey, $version);
        }
        return  $configurationIdentifier . '_' . $version;
    }
}
