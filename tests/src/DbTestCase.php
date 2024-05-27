<?php

namespace GlpiPlugin\Carbon\Tests;

class DbTestCase extends CommonTestCase
{
    public function setUp(): void
    {
        global $DB;
        $DB->beginTransaction();
        parent::setUp();
    }

    public function tearDown(): void
    {
        global $DB;
        $DB->rollback();
        parent::tearDown();
    }
}