<?php

namespace Tests;

use App\Support\Content;
use App\Support\SafeStorage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Settings, Content and SafeStorage each hold a static in-process memo
        // so a request costs at most one query. Tests share a process, so the
        // memo has to be cleared between them or one test's seeded values leak
        // into the next one's assertions.
        Settings::flush();
        Content::flush();
        SafeStorage::fakeSymlinkState(null);
    }

    protected function tearDown(): void
    {
        Settings::flush();
        Content::flush();
        SafeStorage::fakeSymlinkState(null);

        parent::tearDown();
    }
}
