<?php

namespace Butler\Graphql;

use Illuminate\Support\Collection;
use leinonen\DataLoader\CacheMap;
use leinonen\DataLoader\DataLoader as LeinonenDataLoader;
use React\EventLoop\LoopInterface;
use React\Promise;

class DataLoader
{
    private $loop;
    private $loaders = [];

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function __invoke(callable $batchLoadFunction)
    {
        $backtrace = debug_backtrace();
        $identifier = $backtrace[1]['class'] . '@' . $backtrace[1]['function'];

        return $this->loaders[$identifier] = $this->loaders[$identifier]
            ?? $this->makeLoader($batchLoadFunction);
    }

    private function makeLoader(callable $batchLoadFunction)
    {
        return new LeinonenDataLoader(
            function (...$arguments) use ($batchLoadFunction) {
                $result = $batchLoadFunction(...$arguments);
                if ($result instanceof Collection) {
                    $result = $result->toArray();
                }
                return Promise\resolve($result);
            },
            $this->loop,
            new CacheMap()
        );
    }

    public function run()
    {
        return $this->loop->run();
    }
}
