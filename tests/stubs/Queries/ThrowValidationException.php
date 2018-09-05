<?php

namespace Butler\Graphql\Tests\Queries;

use Illuminate\Contracts\Translation\Translator as TranslatorInterface;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Mockery;

class ThrowValidationException
{
    public function __invoke($root, $args, $context)
    {
        // dd('$request');
        // $translator = Mockery::mock(TranslatorInterface::class);
        // $factory = new Factory($translator);
        // $validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);

        // throw new ValidationException($validator);
    }
}
