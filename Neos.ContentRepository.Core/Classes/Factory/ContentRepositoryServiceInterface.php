<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * A ContentRepositoryService is an object which is layered on top of a {@see ContentRepository},
 * but which interacts with the CR internals in an intricate way.
 *
 * In an ideal world, we would not need these services, but you would always have a well-defined method
 * on the {@see ContentRepository} object.
 *
 * This extension mechanism is only needed if you write "core-near" functionality in an extra
 * package. Examples are: Structure Adjustments or Node Migrations, or directly interacting with the
 * Event Stream (for reading or writing).
 *
 * ## Instantiation
 *
 * Create a {@see ContentRepositoryServiceFactoryInterface} for your {@see ContentRepositoryServiceInterface}.
 *
 * @internal this is a low level extension mechanism and not part of the public API.
 */
interface ContentRepositoryServiceInterface
{
}
