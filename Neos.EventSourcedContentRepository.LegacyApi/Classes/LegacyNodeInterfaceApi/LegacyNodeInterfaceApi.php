<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi;

interface LegacyNodeInterfaceApi
{
    public function getIdentifier();

    public function getDepth();

    public function getHiddenBeforeDateTime();

    public function getHiddenAfterDateTime();
}
