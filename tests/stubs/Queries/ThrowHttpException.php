<?php

namespace Butler\Graphql\Tests\Queries;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThrowHttpException
{
    public function __invoke($root, $args, $context)
    {
        throw new HttpException(
            $args['code'],
            Response::$statusTexts[$args['code']] ?? 'unknown status'
        );
    }
}
