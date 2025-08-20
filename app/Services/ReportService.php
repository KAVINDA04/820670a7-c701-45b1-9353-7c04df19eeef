<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function generateDiagnostic(string $studentId, array $students, array $assessments, array $questions, array $responses): string
    {
        [$studentName, $completed] = $this->getCompleted($studentId, $students, $responses);
        $latest = $completed->last();

        if (!$latest) {
            return "{$studentName} has no completed assessments.";
        }

        $assessment = collect($assessments)->firstWhere('id', $latest['assessmentId']);
        [$details, $correctCount] = $this->strandDetails($latest, $questions);

        $completedDate = $this->formatDate($latest['completed']);
        $report  = "{$studentName} recently completed {$assessment['name']} assessment on {$completedDate}\n";
        $report .= "He got {$correctCount} questions right out of " . count($latest['responses']) . ". Details by strand given below:\n\n";

        foreach ($details as $strand => $data) {
            $correct = $data['correct'] ?? 0;
            $report .= "{$strand}: {$correct} out of {$data['total']} correct\n";
        }

        return $report;
    }

    public function generateProgress(string $studentId, array $students, array $assessments, array $questions, array $responses): string
    {
        [$studentName, $completed] = $this->getCompleted($studentId, $students, $responses);
        if ($completed->isEmpty()) {
            return "{$studentName} has no completed assessments.";
        }

        $lines = [];
        foreach ($completed as $response) {
            $date = $this->formatDate($response['completed'], 'jS F Y');
            $score = $this->score($response, $questions);
            $lines[] = "Date: {$date}, Raw Score: {$score} out of " . count($response['responses']);
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
        [$studentName, $completed] = $this->getCompleted($studentId, $students, $responses);
        $latest = $completed->last();

        if (!$latest) {
            return "{$studentName} has no completed assessments.";
        }

        $assessment = collect($assessments)->firstWhere('id', $latest['assessmentId']);
        $correctCount = $this->score($latest, $questions);

        $completedDate = $this->formatDate($latest['completed']);
        $report  = "{$studentName} recently completed {$assessment['name']} assessment on {$completedDate}\n";
        $report .= "He got {$correctCount} questions right out of " . count($latest['responses']) . ". Feedback for wrong answers given below\n\n";

        foreach ($latest['responses'] as $response) {
            $question = collect($questions)->firstWhere('id', $response['questionId']);
            if ($response['response'] !== $question['config']['key']) {
                $yourAnswer  = $this->formatAnswer($question, $response['response']);
                $rightAnswer = $this->formatAnswer($question, $question['config']['key']);

                $report .= "Question: {$question['stem']}\n";
                $report .= "Your answer: {$yourAnswer}\n";
                $report .= "Right answer: {$rightAnswer}\n";
                $report .= "Hint: {$question['config']['hint']}\n\n";
            }
        }

        return $report;
    }

    private function getStudentName(array $students, string $studentId): string
    {
        $student = collect($students)->firstWhere('id', $studentId);

        return $student['firstName'] . ' ' . $student['lastName'];
    }

    private function getCompleted(string $studentId, array $students, array $responses): array
    {
        $studentName = $this->getStudentName($students, $studentId);

        $completed = collect($responses)
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortBy(fn ($response) => Carbon::createFromFormat('d/m/Y H:i:s', $response['completed']));

        return [$studentName, $completed];
    }

    private function strandDetails(array $completed, array $questions): array
    {
        $details = [];
        $correctCount = 0;

        foreach ($completed['responses'] as $response) {
            $question = collect($questions)->firstWhere('id', $response['questionId']);
            $strand = $question['strand'];

            $details[$strand]['total'] = ($details[$strand]['total'] ?? 0) + 1;
            if ($response['response'] === $question['config']['key']) {
                $details[$strand]['correct'] = ($details[$strand]['correct'] ?? 0) + 1;
                $correctCount++;
            }
        }
        return [$details, $correctCount];
    }

    private function score(array $response, array $questions): int
    {
        return collect($response['responses'])->filter(fn($response) =>
            $response['response'] === collect($questions)->firstWhere('id', $response['questionId'])['config']['key']
        )->count();
    }

    private function formatAnswer(array $question, string $optionId): string
    {
        $option = collect($question['config']['options'])->firstWhere('id', $optionId);
        return "{$option['label']} with value {$option['value']}";
    }

    private function formatDate(string $date, string $format = 'jS F Y h:i A'): string
    {
        return Carbon::createFromFormat('d/m/Y H:i:s', $date)->format($format);
    }
}
