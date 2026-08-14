<?php

namespace Butler\Graphql\Concerns;

use Amp\Loop;
use Amp\Promise;
use Closure;
use Exception;
use React\Promise\PromiseInterface;

use function Amp\call;
use function Amp\Promise\all;

trait AssertsPromises
{
    /**
     * @param  Promise|PromiseInterface|mixed  $promise
     * @param  mixed|Closure  $expectedValue
     */
    public function assertPromiseFulfills($promise, $expectedValue = null): void
    {
        $this->addToAssertionCount(1);

        if (is_array($promise)) {
            $promise = all($promise);
        }

        try {
            $result = null;
            Loop::run(function () use (&$result, $promise) {
                $result = yield call(function () use ($promise) {
                    return $promise;
                });
            });
        } catch (Exception $e) {
            $this->fail('Failed asserting that promise fulfills. Promise was rejected: ' . $e->getMessage());
        }

        if ($expectedValue instanceof Closure) {
            $this->assertTrue($expectedValue($result));

            return;
        }

        if (! is_null($expectedValue)) {
            $this->assertSame(
                $expectedValue,
                $result,
                'Failed asserting that promise fulfills with a specified value.'
            );
        }
    }
}
