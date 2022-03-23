<?php
namespace Neos\Fusion\FusionObjects;

use GuzzleHttp\Psr7\Message;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 *
 */
class HttpResponseImplementation extends AbstractArrayFusionObject
{
    /**
     * @Flow\Inject
     * @var StreamFactoryInterface
     */
    protected $contentStreamFactory;

    /**
     * Get the HTTP Header values for this response
     *
     * @return ResponseInterface
     */
    public function getResponseHead()
    {
        return $this->fusionValue($this->getResponseHeadName()) ?? null;
    }

    /**
     * @return string
     */
    public function getResponseHeadName(): string
    {
        return $this->fusionValue('_getHttpResponseHead') ?? 'httpResponseHead';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function evaluate()
    {
        if (!in_array($this->getResponseHeadName(), $this->ignoreProperties, true)) {
            $this->ignoreProperties[] = $this->getResponseHeadName();
        }

        $response = $this->getResponseHead();
        if (!$response instanceof ResponseInterface) {
            throw new \InvalidArgumentException('Could not render HTTP response because the response head was not a valid HTTP response object.', 1557932997);
        }

        $resultParts = $this->evaluateNestedProperties();
        if ($resultParts !== []) {
            $contentStream = $this->contentStreamFactory->createStream(implode('', $resultParts));
            $response = $response->withBody($contentStream);
        }

        // FIXME: It would be neat to transfer the actual response object directly,
        // but the content cache currently cannot handle it, so we put it all into a string for now.
        return Message::toString($response);
    }
}
