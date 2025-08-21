<?php

namespace Clarus\SecureChat\Tests\Integration;

class BasicIntegrationTest extends BrowserKitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function test_example(): void
    {
        $this->visit('/');
    }
}
