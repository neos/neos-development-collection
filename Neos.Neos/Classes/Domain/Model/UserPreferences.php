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

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * A preferences container for a user.
 *
 * This is a very naïve, rough and temporary implementation of a User Preferences container.
 * We'll need a better one which understands which options are available and contains some
 * information about possible help texts etc.
 *
 * @Flow\Entity
 * @todo Provide a more capable implementation
 */
class UserPreferences
{
    /**
     * The actual settings
     *
     * @var array
     * @phpstan-var array<string,mixed>
     */
    protected array $preferences = [];

    /**
     * Get preferences
     *
     * @return array<string,mixed>
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    /**
     * @param array<string,mixed> $preferences
     */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
    }

    public function set(string $key, mixed $value): void
    {
        $this->preferences[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->preferences[$key] ?? null;
    }

    public function setInterfaceLanguage(string $localeIdentifier): void
    {
        $this->set('interfaceLanguage', $localeIdentifier);
    }

    /**
     * @return string the locale identifier
     */
    public function getInterfaceLanguage(): ?string
    {
        return $this->get('interfaceLanguage');
    }
}
