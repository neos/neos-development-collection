<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Annotations as Flow;

/**
 * This is a container for clipboard state that needs to be persisted server side
 *
 * @Flow\Scope("session")
 */
class NodeClipboard
{
    const MODE_COPY = 'Copy';
    const MODE_MOVE = 'Move';

    /**
     * @var string
     */
    protected $serializedNodeAddresses = '';

    /**
     * @var string one of the NodeClipboard::MODE_*  constants
     */
    protected $mode = '';

    /**
     * Save copied node to clipboard.
     *
     * @param NodeAddress[] $nodeAddresses
     * @return void
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException
     * @Flow\Session(autoStart=true)
     */
    public function copyNodes($nodeAddresses)
    {
        $this->serializedNodeAddresses = array_map(fn (NodeAddress $nodeAddress) => $nodeAddress->serializeForUri(), $nodeAddresses);
        $this->mode = self::MODE_COPY;
    }

    /**
     * Save cut node to clipboard.
     *
     * @param NodeAddress[] $nodeAddresses
     * @return void
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException
     * @Flow\Session(autoStart=true)
     */
    public function cutNodes($nodeAddresses)
    {
        $this->serializedNodeAddresses = array_map(fn (NodeAddress $nodeAddress) => $nodeAddress->serializeForUri(), $nodeAddresses);
        $this->mode = self::MODE_MOVE;
    }

    /**
     * Reset clipboard.
     *
     * @return void
     * @Flow\Session(autoStart=true)
     */
    public function clear()
    {
        $this->serializedNodeAddresses = [];
        $this->mode = '';
    }

    /**
     * Get clipboard node.
     *
     * @return string $nodeContextPath
     */
    public function getSerializedNodeAddresses()
    {
        return $this->serializedNodeAddresses ? $this->serializedNodeAddresses : [];
    }

    /**
     * Get clipboard mode.
     *
     * @return string $mode
     */
    public function getMode()
    {
        return $this->mode;
    }
}
