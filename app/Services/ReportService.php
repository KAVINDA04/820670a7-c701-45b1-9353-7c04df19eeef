<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function generateDiagnostic(string $studentId, array $students, array $assessments, array $questions, array $responses): string
    {
        [$studentName, $completed] = $this->getLatestCompleted($studentId, $students, $responses);
        if (!$completed) {
            return "{$studentName} has no completed assessments.";
        }

        $assessment = collect($assessments)->firstWhere('id', $completed['assessmentId']);
        [$details, $correctCount] = $this->strandDetails($completed, $questions);

        $completedDate = $this->formatDate($completed['completed']);
        $report  = "{$studentName} recently completed {$assessment['name']} assessment on {$completedDate}\n";
        $report .= "He got {$correctCount} questions right out of " . count($completed['responses']) . ". Details by strand given below:\n\n";

        foreach ($details as $strand => $d) {
            $correct = $d['correct'] ?? 0;
            $report .= "{$strand}: {$correct} out of {$d['total']} correct\n";
        }

        return $report;
    }

    public function generateProgress(string $studentId, array $students, array $assessments, array $questions, array $responses): string
    {
        [$studentName, $completed] = $this->getAllCompleted($studentId, $students, $responses);
        if ($completed->isEmpty()) {
            return "{$studentName} has no completed assessments.";
        }

        $lines = [];
        foreach ($completed as $resp) {
            $date = $this->formatDate($resp['completed'], 'jS F Y');
            $score = $this->score($resp, $questions);
            $lines[] = "Date: {$date}, Raw Score: {$score} out of " . count($resp['responses']);
        }

        $first = $completed->first();
        $last = $completed->last();
        $improvement = $this->score($last, $questions) - $this->score($first, $questions);

        $report  = "{$studentName} has completed {$completed->count()} assessments in total. Date and raw score given below:\n\n";
        $report .= implode("\n", $lines) . "\n\n";
        $report .= "{$studentName} got {$improvement} more correct in the recent completed assessment than the oldest";

        return $report;
    }

    public function generateFeedback(string $studentId, array $students, array $assessments, array $questions, array $responses): string
    {
        [$studentName, $completed] = $this->getLatestCompleted($studentId, $students, $responses);
        if (!$completed) {
            return "{$studentName} has no completed assessments.";
        }

        $assessment = collect($assessments)->firstWhere('id', $completed['assessmentId']);
        $correctCount = $this->score($completed, $questions);

        $completedDate = $this->formatDate($completed['completed']);
        $report  = "{$studentName} recently completed {$assessment['name']} assessment on {$completedDate}\n";
        $report .= "He got {$correctCount} questions right out of " . count($completed['responses']) . ". Feedback for wrong answers given below\n\n";

        foreach ($completed['responses'] as $resp) {
            $q = collect($questions)->firstWhere('id', $resp['questionId']);
            if ($resp['response'] !== $q['config']['key']) {
                $yourAnswer  = $this->formatAnswer($q, $resp['response']);
                $rightAnswer = $this->formatAnswer($q, $q['config']['key']);

                $report .= "Question: {$q['stem']}\n";
                $report .= "Your answer: {$yourAnswer}\n";
                $report .= "Right answer: {$rightAnswer}\n";
                $report .= "Hint: {$q['config']['hint']}";
            }
        }

        return $report;
    }

    private function getStudentName(array $students, string $studentId): string
    {
        $student = collect($students)->firstWhere('id', $studentId);

        return $student['firstName'] . ' ' . $student['lastName'];
    }

    private function getLatestCompleted(string $studentId, array $students, array $responses): array
    {
        $studentName = $this->getStudentName($students, $studentId);
        $completed = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortByDesc(fn ($r) => Carbon::createFromFormat('d/m/Y H:i:s', $r['completed']))
            ->first();

        return [$studentName, $completed];
    }

    private function getAllCompleted(string $studentId, array $students, array $responses): array
    {
        $studentName = $this->getStudentName($students, $studentId);
        $completed = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortBy(fn ($r) => Carbon::createFromFormat('d/m/Y H:i:s', $r['completed']));

        return [$studentName, $completed];
    }

    private function strandDetails(array $completed, array $questions): array
    {
        $details = [];
        $correctCount = 0;

        foreach ($completed['responses'] as $resp) {
            $q = collect($questions)->firstWhere('id', $resp['questionId']);
            $strand = $q['strand'];

            $details[$strand]['total'] = ($details[$strand]['total'] ?? 0) + 1;
            if ($resp['response'] === $q['config']['key']) {
                $details[$strand]['correct'] = ($details[$strand]['correct'] ?? 0) + 1;
                $correctCount++;
            }
        }
        return [$details, $correctCount];
    }

    private function score(array $response, array $questions): int
    {
        return collect($response['responses'])->filter(fn($r) =>
            $r['response'] === collect($questions)->firstWhere('id', $r['questionId'])['config']['key']
        )->count();
    }

    private function formatAnswer(array $q, string $optionId): string
    {
        $option = collect($q['config']['options'])->firstWhere('id', $optionId);
        return "{$option['label']} with value {$option['value']}";
    }

    private function formatDate(string $date, string $format = 'jS F Y h:i A'): string
    {
        return Carbon::createFromFormat('d/m/Y H:i:s', $date)->format($format);
    }
}
