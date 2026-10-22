<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\Feature\AttendanceIntegrationTest;

class MariaDbAttendanceIntegrationTest extends AttendanceIntegrationTest
{
    public function refreshDatabase()
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('farras_bappeda_presensi', DB::connection()->getDatabaseName());
        $this->assertContains(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost']);

        // Use the existing local schema; roll back every scenario without migrations.
        $this->beginDatabaseTransaction();
    }
}
