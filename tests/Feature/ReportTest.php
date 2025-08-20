<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportTest extends TestCase
{
    public function test_diagnostic_report_runs_successfully()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'reportType' => 'diagnostic'])
            ->assertExitCode(0);
    }

    public function test_progress_report_runs_successfully()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'reportType' => 'progress'])
            ->assertExitCode(0);
    }

    public function test_feedback_report_runs_successfully()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'reportType' => 'feedback'])
            ->assertExitCode(0);
    }
}
