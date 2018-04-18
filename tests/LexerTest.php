<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\Lexer;
use Butler\Graphql\Tests\Examples\ExampleType;
use Exception;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    /** @var Lexer */
    protected $lexer;

    protected function setUp()
    {
        $this->lexer = new Lexer(['Butler\Graphql\Tests\Examples']);
    }

    public function test_evaluate()
    {

        $this->assertEquals(
            [StringType::class],
            $this->lexer->evaluate('string')
        );

        $this->assertEquals(
            [NonNull::class, StringType::class],
            $this->lexer->evaluate('required|string')
        );

        $this->assertEquals(
            [ExampleType::class],
            $this->lexer->evaluate('ExampleType')
        );

        $this->assertEquals(
            [ListOfType::class, ExampleType::class],
            $this->lexer->evaluate('ExampleType[]')
        );

        $this->assertEquals(
            [NonNull::class, ExampleType::class],
            $this->lexer->evaluate('required|ExampleType')
        );

        $this->assertEquals(
            [NonNull::class, ListOfType::class, ExampleType::class],
            $this->lexer->evaluate('required|ExampleType[]')
        );
    }

    public function test_Evaluate_Throws_Exception_On_Parse_Error()
    {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not parse the GraphQL type definition: NonExistingClass');
        $this->lexer->evaluate('required|NonExistingClass[]');

    }

    public function test_Evaluates_Correctly_With_Spaced_Input() {
        $this->assertEquals(
            [NonNull::class, ListOfType::class, ExampleType::class],
            $this->lexer->evaluate('   required   | ExampleType[] | |  ')
        );
    }

    public function test_Evaluates_Correctly_With_Malformed_Input() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not parse the GraphQL type definition: string[ ]');
        $this->lexer->evaluate('required|string[ ]');
    }
}