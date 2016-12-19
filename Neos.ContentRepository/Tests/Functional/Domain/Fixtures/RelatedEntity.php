<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Simple entity related to a node for testing.
 *
 * @Flow\Entity
 */
class RelatedEntity
{
    /**
     * @var string
     */
    protected $favoritePlace;

    /**
     * @return string
     */
    public function getFavoritePlace()
    {
        return $this->favoritePlace;
    }

    /**
     * @param string $favoritePlace
     * @return void
     */
    public function setFavoritePlace($favoritePlace)
    {
        $this->favoritePlace = $favoritePlace;
    }
}
