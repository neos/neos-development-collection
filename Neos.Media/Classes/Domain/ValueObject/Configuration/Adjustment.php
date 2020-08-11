<?php
declare(strict_types=1);

namespace Neos\Media\Domain\ValueObject\Configuration;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class Adjustment
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @param string $identifier
     * @param string $type
     */
    public function __construct(string $identifier, string $type)
    {
        $this->setIdentifier($identifier);
        $this->type = $type;
    }

    /**
     * @param string $identifier
     * @param array $configuration
     * @return Adjustment
     */
    public static function fromConfiguration(string $identifier, array $configuration): Adjustment
    {
        if (!isset($configuration['type'])) {
            throw new \InvalidArgumentException(sprintf('Missing type in configuration for adjustment "%s".', $identifier), 1549276551);
        }

        $adjustment = new static(
            $identifier,
            $configuration['type']
        );

        $adjustment->options = $configuration['options'] ?? [];
        return $adjustment;
    }

    /**
     * @param string $identifier
     */
    private function setIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid adjustment identifier "%s".', $identifier), 1548066064);
        }
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function identifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
