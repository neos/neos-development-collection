<?php
namespace TYPO3\Media\Domain\Repository;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A repository for Assets
 *
 * @Flow\Scope("singleton")
 */
class AssetRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
     * interface ...
     *
     * @Flow\Inject
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $defaultOrderings = array('title' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);

    /**
     * Find assets by title or given tags
     *
     * @param string $searchTerm
     * @param array $tags
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findBySearchTermOrTags($searchTerm, array $tags = array())
    {
        $query = $this->createQuery();

        $constraints = array(
            $query->like('title', '%' . $searchTerm . '%'),
            $query->like('resource.filename', '%' . $searchTerm . '%')
        );
        foreach ($tags as $tag) {
            $constraints[] = $query->contains('tags', $tag);
        }

        return $query->matching($query->logicalOr($constraints))->execute();
    }

    /**
     * Find Assets with the given Tag assigned
     *
     * @param \TYPO3\Media\Domain\Model\Tag $tag
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findByTag(\TYPO3\Media\Domain\Model\Tag $tag)
    {
        $query = $this->createQuery();

        return $query->matching($query->contains('tags', $tag))->execute();
    }

    /**
     * Counts Assets with the given Tag assigned
     *
     * @param \TYPO3\Media\Domain\Model\Tag $tag
     * @return integer
     */
    public function countByTag(\TYPO3\Media\Domain\Model\Tag $tag)
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('c', 'c');
        $query = $this->entityManager->createNativeQuery('SELECT count(a.persistence_object_identifier) c FROM typo3_media_domain_model_asset a LEFT JOIN typo3_media_domain_model_asset_tags_join mm ON a.persistence_object_identifier = mm.media_asset WHERE mm.media_tag = ?', $rsm);
        $query->setParameter(1, $tag);
        return $query->getSingleScalarResult();
    }

    /**
     * Find Assets without any tag
     *
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findUntagged()
    {
        $query = $this->createQuery();

        return $query->matching($query->isEmpty('tags'))->execute();
    }

    /**
     * Counts Assets without any tag
     *
     * @return integer
     */
    public function countUntagged()
    {
        $query = $this->createQuery();

        return $query->matching($query->isEmpty('tags'))->count();
    }
}
