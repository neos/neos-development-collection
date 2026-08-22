<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\SequenceNumber;

final readonly class IllegalContentStreamForks
{
    /**
     * @param list<array{SequenceNumber,CorrelationId,ContentStreamId}> $illegalForks
     */
    public function __construct(
        public ContentStreamId $sourceContentStreamId,
        public SequenceNumber $removalSequenceNumber,
        public array $illegalForks
    ) {
    }

    /**
     * @param array<string,string> $row
     */
    public static function fromRow(array $row): self
    {
        if (str_contains($row['sourceContentStreamId'], ',')) {
            throw new \RuntimeException(sprintf('Error: Expected content stream to be removed only once got %s', $row['sourceContentStreamId']));
        }
        $sourceContentStreamId = ContentStreamId::fromString($row['sourceContentStreamId']);
        $removalSequenceNumber = SequenceNumber::fromInteger((int)$row['removalSequenceNumber']);

        /** @var list<array{SequenceNumber,CorrelationId,ContentStreamId}> $allContentStreamForks */
        $allContentStreamForks = array_map(
            null,
            array_map(SequenceNumber::fromInteger(...), array_map(intval(...), explode(',', $row['forkSequenceNumbers']))),
            array_map(CorrelationId::fromString(...), explode(',', $row['forkCorrelationIds'])),
            array_map(ContentStreamId::fromString(...), explode(',', $row['newContentStreamIds'])),
        );

        $illegalContentStreamForks = array_filter(
            $allContentStreamForks,
            function (array $_) use ($removalSequenceNumber) {
                [$forkSequenceNumber] = $_;
                return $removalSequenceNumber < $forkSequenceNumber;
            }
        );

        return new self(
            sourceContentStreamId: $sourceContentStreamId,
            removalSequenceNumber: $removalSequenceNumber,
            illegalForks: $illegalContentStreamForks,
        );
    }
}
