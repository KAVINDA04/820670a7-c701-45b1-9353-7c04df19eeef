<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{DataLoader, ReportService};

class GenerateReport extends Command
{
    protected $signature = 'report:generate {studentId? : The student ID} {reportType? : diagnostic|progress|feedback}';

    protected $description = 'Generate a student report (interactive or non-interactive)';

    public function handle(): int
    {
        $students    = DataLoader::load('students.json');
        $assessments = DataLoader::load('assessments.json');
        $questions   = DataLoader::load('questions.json');
        $responses   = DataLoader::load('student-responses.json');

        $studentIds = collect($students)->pluck('id')->all();

        $studentId = $this->argument('studentId');
        while (!$studentId || !in_array($studentId, $studentIds)) {
            if ($studentId) {
                $this->error("No student found with ID: {$studentId}. Try again.");
            }
            $studentId = $this->ask('Please enter the Student ID');
        }

        $reportType = $this->argument('reportType');
        $reportMap = [
            1 => 'diagnostic',
            2 => 'progress',
            3 => 'feedback',
        ];

        if (!$reportType) {
            $choice = (int) $this->choice(
                'Report to generate',
                [
                    1 => '1: Diagnostic',
                    2 => '2: Progress',
                    3 => '3: Feedback',
                ],
                1
            );
            $reportType = $reportMap[$choice] ?? null;

        } elseif (!in_array($reportType, $reportMap)) {
            $this->error("Invalid report type: {$reportType}");
            return 1;
        }

        $service = new ReportService();

        try {
            $report = match ($reportType) {
                'diagnostic' => $service->generateDiagnostic($studentId, $students, $assessments, $questions, $responses),
                'progress'   => $service->generateProgress($studentId, $students, $assessments, $questions, $responses),
                'feedback'   => $service->generateFeedback($studentId, $students, $assessments, $questions, $responses),
            };

            $this->line($report);

        } catch (\Throwable $e) {
            $this->error("Something went wrong: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
