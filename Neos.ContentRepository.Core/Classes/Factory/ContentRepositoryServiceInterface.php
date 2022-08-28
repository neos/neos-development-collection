<?php

namespace Neos\ContentRepository\Factory;

use Neos\ContentRepository\ContentRepository;

/**
 * A ContentRepositoryService is an object which is layered on top of a {@see ContentRepository},
 * but which interacts with the CR internals in an intricate way.
 *
 * In an ideal world, we would not need these services, but you would always have a well-defined method
 * on the {@see ContentRepository} object.
 *
 * You very likely won't need this yourself, except if you write "core-near" functionality in an extra
 * package. Examples are: Structure Adjustments or Node Migrations, or directly interacting with the
 * Event Stream (for reading or writing).
 *
 * ## Instanciation
 *
 * Create a {@see ContentRepositoryServiceFactoryInterface} for your {@see ContentRepositoryServiceInterface}.
 *
 * @api
 */
interface ContentRepositoryServiceInterface
{
}
