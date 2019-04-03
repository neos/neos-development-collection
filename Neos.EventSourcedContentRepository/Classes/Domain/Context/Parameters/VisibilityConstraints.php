<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Parameters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The context parameters value object
 *
 * Maybe future: "Node Filter" tree or so as replacement of ReadNodePrivilege?
 * @Flow\Proxy(false)
 */
final class VisibilityConstraints
{
    /**
     * @var \DateTimeImmutable
     */
    protected $currentDateTime;

    /**
     * @var boolean
     */
    protected $invisibleContentShown = false;

    protected static $currentDateTimeOnInitialization;


    private function __construct(\DateTimeImmutable $currentDateTime, bool $invisibleContentShown)
    {
        $this->currentDateTime = $currentDateTime;
        $this->invisibleContentShown = $invisibleContentShown;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCurrentDateTime(): \DateTimeImmutable
    {
        return $this->currentDateTime;
    }

    /**
     * @return bool
     */
    public function isInvisibleContentShown(): bool
    {
        return $this->invisibleContentShown;
    }

    public function getHash(): string
    {
        return md5($this->currentDateTime->format(\DateTime::W3C) . '-invisible' . $this->invisibleContentShown);
    }

    public static function withoutRestrictions(): VisibilityConstraints
    {
        if (!self::$currentDateTimeOnInitialization) {
            self::$currentDateTimeOnInitialization = new \DateTimeImmutable();
        }
        return new VisibilityConstraints(self::$currentDateTimeOnInitialization, true);
    }

    public static function frontend(): VisibilityConstraints
    {
        if (!self::$currentDateTimeOnInitialization) {
            self::$currentDateTimeOnInitialization = new \DateTimeImmutable();
        }

        return new VisibilityConstraints(self::$currentDateTimeOnInitialization, false);
    }
}
