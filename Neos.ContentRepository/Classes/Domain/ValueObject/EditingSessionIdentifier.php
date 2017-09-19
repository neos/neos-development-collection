<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class EditingSessionIdentifier implements \JsonSerializable
{

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $workspaceName;

    /**
     * @var string
     */
    private $userIdentifier;

    public function __construct(string $identifier, string $workspaceName, string $userIdentifier)
    {
        $this->identifier = $identifier;
        $this->workspaceName = $workspaceName;
        $this->userIdentifier = $userIdentifier;
    }

    function jsonSerialize()
    {
        return [
            'identifier' => $this->identifier,
            'workspaceName' => $this->workspaceName,
            'userIdentifier' => $this->userIdentifier
        ];
    }

    public function __toString()
    {
        return $this->identifier . ':' . $this->userIdentifier . '@' . $this->workspaceName;
    }

}
