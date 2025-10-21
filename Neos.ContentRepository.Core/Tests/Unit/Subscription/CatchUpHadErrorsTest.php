<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Subscription;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFailed;
use Neos\ContentRepository\Core\Subscription\Engine\Error;
use Neos\ContentRepository\Core\Subscription\Engine\Errors;
use Neos\ContentRepository\Core\Subscription\Exception\CatchUpHadErrors;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\EventStore\Model\Event\SequenceNumber;
use PHPUnit\Framework\TestCase;

class CatchUpHadErrorsTest extends TestCase
{
    public function testSimple()
    {
        $exception = new \RuntimeException('This catchup hook is kaputt.');

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onBeforeCatchup" failed: "SomeHook": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        $errors = Errors::fromArray([
            Error::create(
                SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                $expectedWrappedException->getMessage(),
                $expectedWrappedException,
                null
            ),
        ]);

        // assert shape if the error had been thrown by the cr:
        $catchupHadErrors = CatchUpHadErrors::createFromErrors($errors);
        self::assertEquals($expectedWrappedException, $catchupHadErrors->getPrevious());
        self::assertEquals('Error while catching up: "Vendor.Package:SecondFakeProjection": Hook "onBeforeCatchup" failed: "SomeHook": This catchup hook is kaputt.', $catchupHadErrors->getMessage());
    }

    public function testWithTwoHookErrors()
    {
        $exception = new \RuntimeException('This catchup hook is kaputt.');

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onBeforeEvent" failed: "SomeHook": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        $errors = Errors::fromArray([
            Error::create(
                SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                $expectedWrappedException->getMessage(),
                $expectedWrappedException,
                null
            ),
            Error::create(
                SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                $expectedWrappedException->getMessage(),
                null,
                null
            ),
        ]);

        // assert shape if the error had been thrown by the cr:
        $catchupHadErrors = CatchUpHadErrors::createFromErrors($errors);
        self::assertEquals($expectedWrappedException, $catchupHadErrors->getPrevious());
        self::assertEquals('Errors while catching up: "Vendor.Package:SecondFakeProjection": Hook "onBeforeEvent" failed: "SomeHook": This catchup hook is kaputt.;
"Vendor.Package:SecondFakeProjection": Hook "onBeforeEvent" failed: "SomeHook": This catchup hook is kaputt.', $catchupHadErrors->getMessage());
    }

    public function testWith10Errors()
    {
        $exception = new \RuntimeException('Message why A failed');
        $errors = Errors::fromArray([
            Error::create(SubscriptionId::fromString('Vendor.Package:A'), $exception->getMessage(), $exception, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:B'), 'Message why B failed', null, SequenceNumber::fromInteger(1)),
            Error::create(SubscriptionId::fromString('Vendor.Package:C'), 'Message why C failed', null, SequenceNumber::fromInteger(1)),
            Error::create(SubscriptionId::fromString('Vendor.Package:D'), 'Message why D failed', null, SequenceNumber::fromInteger(3)),
            Error::create(SubscriptionId::fromString('Vendor.Package:E'), 'Message why E failed', null, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:F'), 'Message why F failed', null, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:G'), 'Message why G failed', null, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:H'), 'Message why H failed', null, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:I'), 'Message why I failed', null, null),
            Error::create(SubscriptionId::fromString('Vendor.Package:J'), 'Message why J failed', null, null),
        ]);

        // assert shape if the error had been thrown by the cr:
        $catchupHadErrors = CatchUpHadErrors::createFromErrors($errors);
        self::assertEquals($exception, $catchupHadErrors->getPrevious());
        self::assertEquals(<<<MSG
        Errors while catching up: "Vendor.Package:A": Message why A failed;
        Event 1 in "Vendor.Package:B": Message why B failed;
        Event 1 in "Vendor.Package:C": Message why C failed;
        Event 3 in "Vendor.Package:D": Message why D failed;
        "Vendor.Package:E": Message why E failed;
        And 5 other exceptions, see log.
        MSG, $catchupHadErrors->getMessage());
    }
}
