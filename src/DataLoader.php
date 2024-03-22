<?php

namespace Butler\Graphql;

use Amp\Deferred;
use Amp\Loop;
use Closure;
use ReflectionFunction;

class DataLoader
{
    private $loaders = [];

    /**
     * @param  callable|Closure  $batchLoadFunction
     */
    public function __invoke($batchLoadFunction, $defaultResolveValue = null)
    {
        if (! $batchLoadFunction instanceof Closure) {
            $batchLoadFunction = Closure::fromCallable($batchLoadFunction);
        }

        $identifier = $this->identifierForClosure($batchLoadFunction);

        return $this->loaders[$identifier] = $this->loaders[$identifier]
            ?? $this->makeLoader($batchLoadFunction, $defaultResolveValue);
    }

    private function identifierForClosure(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        return $reflection->getFileName() . '@' .
            $reflection->getStartLine() . '-' . $reflection->getEndLine();
    }

    private function makeLoader(Closure $batchLoadFunction, $defaultResolveValue)
    {
        return new class($batchLoadFunction, $defaultResolveValue)
        {
            private $batchLoadFunction;
            private $defaultResolveValue;

            private $deferredPromises;
            private $needsResolving;

            public function __construct(Closure $batchLoadFunction, $defaultResolveValue = null)
            {
                $this->batchLoadFunction = $batchLoadFunction;
                $this->defaultResolveValue = $defaultResolveValue;

                $this->deferredPromises = [];
                $this->needsResolving = false;
            }

            public function load($key)
            {
                $serializedKey = self::serializeKey($key);

                [$deferred] = $this->deferredPromises[$serializedKey]
                    ?? $this->deferredPromises[$serializedKey] = [new Deferred(), $key];

                $this->scheduleResolveIfNeeded();

                return $deferred->promise();
            }

            private function scheduleResolveIfNeeded()
            {
                if (! $this->needsResolving) {
                    Loop::defer(Closure::fromCallable([$this, 'resolve']));
                    $this->needsResolving = true;
                }
            }

            private function resolve()
            {
                $indexedOriginalKeys = [];
                foreach ($this->deferredPromises as [$_, $originalKey]) {
                    $indexedOriginalKeys[] = $originalKey;
                }

                $result = ($this->batchLoadFunction)($indexedOriginalKeys);

                $currentIndex = 0;
                foreach ($result as $key => $value) {
                    if ($key === $currentIndex) {
                        $key = $indexedOriginalKeys[$key] ?? $key;
                    }
                    $key = self::serializeKey($key);

                    if ([$deferred] = $this->deferredPromises[$key] ?? null) {
                        $deferred->resolve($value);
                        unset($this->deferredPromises[$key]);
                    }

                    $currentIndex++;
                }

                foreach ($this->deferredPromises as $key => [$deferredPromise]) {
                    $deferredPromise->resolve($this->defaultResolveValue);
                }

                $this->deferredPromises = [];
                $this->needsResolving = false;
            }

            private static function serializeKey($key)
            {
                if (is_object($key)) {
                    return spl_object_hash($key);
                } elseif (is_array($key)) {
                    return md5(json_encode($key));
                }

                return (string) $key;
            }
        };
    }

    public function run(): void
    {
        Loop::run();
    }
}
