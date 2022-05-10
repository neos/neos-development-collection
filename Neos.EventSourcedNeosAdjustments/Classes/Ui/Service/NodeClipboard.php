<?php

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\Flow\Annotations as Flow;

/**
 * This is a container for clipboard state that needs to be persisted server side
 *
 * @Flow\Scope("session")
 */
class NodeClipboard
{
    public const MODE_COPY = 'Copy';
    public const MODE_MOVE = 'Move';

    /**
     * @var array<int,string>
     */
    protected array $serializedNodeAddresses = [];

    /**
     * one of the NodeClipboard::MODE_*  constants
     */
    protected string $mode = '';

    /**
     * Save copied node to clipboard.
     *
     * @param array<int,NodeAddress> $nodeAddresses
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     * @Flow\Session(autoStart=true)
     */
    public function copyNodes(array $nodeAddresses): void
    {
        $this->serializedNodeAddresses = array_map(
            fn (NodeAddress $nodeAddress) => $nodeAddress->serializeForUri(),
            $nodeAddresses
        );
        $this->mode = self::MODE_COPY;
    }

    /**
     * Save cut node to clipboard.
     *
     * @param array<int,NodeAddress> $nodeAddresses
     * @throws \Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException
     * @Flow\Session(autoStart=true)
     */
    public function cutNodes(array $nodeAddresses): void
    {
        $this->serializedNodeAddresses = array_map(
            fn (NodeAddress $nodeAddress) => $nodeAddress->serializeForUri(),
            $nodeAddresses
        );
        $this->mode = self::MODE_MOVE;
    }

    /**
     * Reset clipboard.
     *
     * @Flow\Session(autoStart=true)
     */
    public function clear(): void
    {
        $this->serializedNodeAddresses = [];
        $this->mode = '';
    }

    /**
     * @return array<int,string>
     */
    public function getSerializedNodeAddresses(): array
    {
        return $this->serializedNodeAddresses;
    }

    /**
     * Get clipboard mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}
