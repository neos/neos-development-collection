<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi;

interface LegacyNodeInterfaceApi
{
    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getContextPath();

    /**
     * @return int
     */
    public function getDepth();

    /**
     * @return ?\DateTimeInterface
     */
    public function getHiddenBeforeDateTime();

    /**
     * @return ?\DateTimeInterface
     */
    public function getHiddenAfterDateTime();
}
