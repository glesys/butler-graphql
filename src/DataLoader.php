<?php

namespace Butler\Graphql;

use Closure;
use ReflectionFunction;
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

    /**
     * @param  callable|Closure  $batchLoadFunction
     */
    public function __invoke($batchLoadFunction)
    {
        if (! $batchLoadFunction instanceof Closure) {
            $batchLoadFunction = Closure::fromCallable($batchLoadFunction);
        }

        $identifier = $this->identifierForClosure($batchLoadFunction);

        return $this->loaders[$identifier] = $this->loaders[$identifier]
            ?? $this->makeLoader($batchLoadFunction);
    }

    private function identifierForClosure(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);

        return $reflection->getFileName() . '@' .
            $reflection->getStartLine() . '-' . $reflection->getEndLine();
    }

    private function makeLoader(Closure $batchLoadFunction)
    {
        return new LeinonenDataLoader(
            function (...$arguments) use ($batchLoadFunction) {
                $result = $batchLoadFunction(...$arguments);
                if ($result instanceof Collection) {
                    $result = $result->all();
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
