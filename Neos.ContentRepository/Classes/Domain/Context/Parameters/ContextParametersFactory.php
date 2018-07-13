<?php
namespace Neos\ContentRepository\Domain\Context\Parameters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Utility\Now;

/**
 * @Flow\Scope("singleton")
 * The context parameters factory
 */
final class ContextParametersFactory
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;


    /**
     * @return ContextParameters
     */
    public function createDefaultParameters(): ContextParameters
    {
        return new ContextParameters($this->now, $this->securityContext->getRoles(), false, false);
    }
}
