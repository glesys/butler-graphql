<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\ServiceProvider;
use GrahamCampbell\TestBench\AbstractPackageTestCase;

abstract class AbstractTestCase extends AbstractPackageTestCase
{
    protected static function getServiceProviderClass(): string
    {
        return ServiceProvider::class;
    }
}
