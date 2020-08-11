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

final class Variant
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var Label
     */
    private $label;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $adjustments = [];

    /**
     * @param string $identifier
     * @param Label $label
     * @param string $description
     */
    public function __construct(string $identifier, Label $label, string $description = null)
    {
        $this->setIdentifier($identifier);
        $this->label = $label;
        $this->description = $description;
    }

    /**
     * @param string $identifier
     * @param array $configuration
     * @return Variant
     */
    public static function fromConfiguration(string $identifier, array $configuration): Variant
    {
        $variant = new static(
            $identifier,
            new Label($configuration['label']),
            $configuration['description'] ?? null
        );

        if (isset($configuration['adjustments'])) {
            foreach ($configuration['adjustments'] as $adjustmentIdentifier => $adjustmentConfiguration) {
                $variant->adjustments[$adjustmentIdentifier] = Adjustment::fromConfiguration($adjustmentIdentifier, $adjustmentConfiguration);
            }
        }

        return $variant;
    }

    /**
     * @param string $identifier
     */
    private function setIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid variant identifier "%s".', $identifier), 1546958006);
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
     * @return Label
     */
    public function label(): Label
    {
        return $this->label;
    }

    /**
     * @return Label
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function adjustments(): array
    {
        return $this->adjustments;
    }
}
