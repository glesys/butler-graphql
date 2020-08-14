<?php

namespace Butler\Graphql\Concerns;

use Closure;
use Exception;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

use function React\Promise\all;

trait AssertsPromises
{
    private $loop;

    /**
     * @param  \React\Promise\PromiseInterface|array  $promise
     * @param  mixed|Closure  $expectedValue
     */
    public function assertPromiseFulfills($promise, $expectedValue = null): void
    {
        $this->addToAssertionCount(1);

        if (! $promise instanceof PromiseInterface) {
            $promise = all($promise);
        }

        try {
            $result = $this->waitForPromise($promise);
        } catch (Exception $_) {
            $this->fail('Failed asserting that promise fulfills. Promise was rejected.');
        }

        if ($expectedValue instanceof Closure) {
            $this->assertTrue($expectedValue($result));
            return;
        }

        if (! is_null($expectedValue)) {
            $this->assertEquals(
                $expectedValue,
                $result,
                'Failed asserting that promise fulfills with a specified value.'
            );
        }
    }

    protected function getLoop(): LoopInterface
    {
        if (! $this->loop) {
            $this->loop = LoopFactory::create();
        }

        return $this->loop;
    }

    private function waitForPromise(PromiseInterface $promise)
    {
        $wait = true;
        $resolved = null;
        $exception = null;
        $rejected = false;

        $promise->then(
            function ($c) use (&$resolved, &$wait) {
                $resolved = $c;
                $wait = false;
                $this->getLoop()->stop();
            },
            function ($error) use (&$exception, &$rejected, &$wait) {
                $exception = $error;
                $rejected = true;
                $wait = false;
                $this->getLoop()->stop();
            }
        );

        // Explicitly overwrite argument with null value. This ensure that this
        // argument does not show up in the stack trace in PHP 7+ only.
        $promise = null;

        while ($wait) {
            $this->getLoop()->run();
        }

        if ($rejected) {
            if (! $exception instanceof Exception) {
                $type = is_object($exception) ? get_class($exception) : gettype($exception);
                $exception = new \UnexpectedValueException(
                    'Promise rejected with unexpected value of type ' . $type,
                    0,
                    $exception instanceof \Throwable ? $exception : null
                );
            }

            throw $exception;
        }

        return $resolved;
    }
}
