<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Concerns\AssertsPromises;
use Butler\Graphql\DataLoader;
use Exception;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

class AssertsPromisesTest extends TestCase
{
    private $testObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testObject = $this->createTestObject();
    }

    public function test_assertPromiseFulfills_with_one_promise()
    {
        $promise = $this->testObject->square(2);

        $this->testObject->assertPromiseFulfills($promise, 4);

        $this->assertEquals(1, $this->getCount());
    }

    public function test_assertPromiseFulfills_with_array_of_promises()
    {
        $promises = [
            $this->testObject->square(1),
            $this->testObject->square(2),
        ];

        $this->testObject->assertPromiseFulfills($promises, [1, 4]);

        $this->assertEquals(1, $this->getCount());
    }

    public function test_assertPromiseFulfills_with_callback_as_expectedValue()
    {
        $promises = [
            $this->testObject->square(1),
            $this->testObject->square(2),
        ];

        $this->testObject->assertPromiseFulfills($promises, function ($result) {
            return $result === [1, 4];
        });

        $this->assertEquals(1, $this->getCount());
    }

    public function test_assertPromiseFulfills_fails_on_unexpected_result()
    {
        $promise = $this->testObject->square(2);

        try {
            $this->testObject->assertPromiseFulfills($promise, 5);
        } catch (Exception $exception) {
            $this->assertEquals(
                "Failed asserting that promise fulfills with a specified value.\n" .
                "Failed asserting that 4 matches expected 5.",
                $exception->getMessage()
            );
            return;
        }

        $this->fail('An Exception should have been thrown.');
    }

    public function test_assertPromiseFulfills_handles_exceptions()
    {
        $promise = $this->testObject->square('not a number');

        try {
            $this->testObject->assertPromiseFulfills($promise);
        } catch (Exception $exception) {
            $this->assertEquals(
                'Failed asserting that promise fulfills. Promise was rejected.',
                $exception->getMessage()
            );
            return;
        }

        $this->fail('An Exception should have been thrown.');
    }

    public function test_assertPromiseFulfills_handles_rejected_promise()
    {
        $promise = reject($this->testObject->square(1));

        try {
            $this->testObject->assertPromiseFulfills($promise);
        } catch (Exception $exception) {
            $this->assertEquals(
                'Failed asserting that promise fulfills. Promise was rejected.',
                $exception->getMessage()
            );
            return;
        }

        $this->fail('An Exception should have been thrown.');
    }

    private function createTestObject(): TestCase
    {
        return new class extends TestCase {
            use AssertsPromises;

            private $context;

            public function __construct()
            {
                $this->context = ['loader' => new DataLoader($this->getLoop())];
            }

            public function square($base): PromiseInterface
            {
                return $this->context['loader'](function (array $numbers) {
                    return collect($numbers)->map(function ($base) {
                        throw_unless(is_int($base), Exception::class);
                        return pow($base, 2);
                    });
                })->load($base);
            }
        };
    }
}
