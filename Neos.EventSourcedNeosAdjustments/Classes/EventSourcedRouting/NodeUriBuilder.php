<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Http\Message\UriInterface;

final class NodeUriBuilder
{

    /**
     * @var UriBuilder
     */
    private $uriBuilder;

    protected function __construct(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    public static function fromRequest(ActionRequest $request): self
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        return new static($uriBuilder);
    }

    public static function fromUriBuilder(UriBuilder $uriBuilder): self
    {
        return new static($uriBuilder);
    }

    public function uriFor(NodeAddress $nodeAddress): UriInterface
    {
        if (!$nodeAddress->isInLiveWorkspace()) {
            return $this->previewUriFor($nodeAddress);
        }
        return new Uri($this->uriBuilder->uriFor('show', ['node' => $nodeAddress], 'Frontend\Node', 'Neos.Neos'));
    }

    public function previewUriFor(NodeAddress $nodeAddress): UriInterface
    {
        return new Uri($this->uriBuilder->uriFor('preview', ['node' => $nodeAddress->serializeForUri()], 'Frontend\Node', 'Neos.Neos'));
    }
}
