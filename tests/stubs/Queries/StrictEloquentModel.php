<?php

namespace Butler\Graphql\Tests\Queries;

use Illuminate\Database\Eloquent\Model;

class StrictEloquentModel
{
    public function __invoke($root, $args, $context)
    {
        return new class extends Model
        {
            public $exists = true;

            protected $attributes = [
                'id' => '100',
                'is_strict' => true,
            ];
        };
    }
}
