<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepositoryRegistry\Command\SubprocessProjectionCatchUpCommandController;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Core\Booting\Scripts;

/**
 * See {@see SubprocessProjectionCatchUpCommandController} for the inner part
 */
class SubprocessProjectionCatchUpTrigger implements ProjectionCatchUpTriggerInterface
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

    public function __construct(
        private readonly ContentRepositoryIdentifier $contentRepositoryIdentifier
    )
    {
    }

    public function triggerCatchUp(Projections $projections): void
    {
        // modelled after https://github.com/neos/Neos.EventSourcing/blob/master/Classes/EventPublisher/JobQueueEventPublisher.php#L103
        // and https://github.com/Flowpack/jobqueue-common/blob/master/Classes/Queue/FakeQueue.php
        foreach ($projections as $projection) {
            Scripts::executeCommandAsync(
                'neos.contentrepositoryregistry:subprocessprojectioncatchup:catchup',
                $this->flowSettings,
                [
                    'contentRepositoryIdentifier' => $this->contentRepositoryIdentifier->value,
                    'projectionClassName' => get_class($projection)
                ]
            );
        }
    }
}
