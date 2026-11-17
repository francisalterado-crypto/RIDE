<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Terminal Report Assessment (WPU-QSF-RDE-VPRDE-22): the evaluation of a submitted terminal report.
 *
 * It is entered by the RDO Research Coordinator at their review step of the terminal report and saved with their
 * approval in summary.reviews[rdo_coordinator], the same way as the incentive request reviews. Researchers do not
 * fill it in. Records filed with the former standalone assessment form stay viewable, read-only.
 */
final class TerminalReportAssessment
{
    public const STEP = AccessControl::RIDE_COORDINATOR_RESEARCH;

    public const PARAMETERS = [
        1 => 'The abstract presents an accurate synopsis of the research.',
        2 => 'The approved methodology in the proposal was followed (i.e. sample size, sampling procedure, data collection, data analysis, etc).',
        3 => 'The approved research ethics protocol was followed.',
        4 => 'Analysis of limitations and errors of the methods were presented.',
        5 => 'Stakeholders validated the data.',
        6 => 'Scientific literature supported the findings.',
        7 => 'The conclusion(s) captures the result of hypotheses test.',
        8 => 'The conclusions validated the theory used.',
        9 => 'The study generated new knowledge in the discipline.',
        10 => 'There are leads to future studies emanating from the current results.',
        11 => 'The paper complied with the standard of technical writing.',
        12 => "There is evidence that scientific literature and stakeholder's view support the recommendations.",
        13 => 'The over-all presentation of report captures the substance of the study.',
    ];

    public const SCORES = ['yes' => 'Yes', 'no' => 'No', 'na' => 'N/A'];

    public const JUDGMENTS = [
        'accepted_without_revisions' => 'Accepted without revisions',
        'accepted_after_minor_revisions' => 'Accepted after minor revisions suggested in this review',
        'accepted_after_major_revisions' => 'Accepted after major revisions suggested in this review',
        'failed_to_pass' => 'Failed to pass',
    ];

    /** Judgments that return the terminal report for revision instead of approving it. */
    public const REVISION_JUDGMENTS = ['accepted_after_major_revisions', 'failed_to_pass'];

    /** Whether a saved or submitted assessment sends the report back for revision. */
    public static function requiresRevision(array $review): bool
    {
        return in_array((string) ($review['judgment'] ?? ''), self::REVISION_JUDGMENTS, true);
    }

    /** The comment recorded with the return: the judgment and the coordinator's comments. */
    public static function returnComment(array $review): string
    {
        $judgment = self::JUDGMENTS[(string) ($review['judgment'] ?? '')] ?? '';

        return trim('Terminal Report Assessment: ' . $judgment . '. ' . trim((string) ($review['comments'] ?? '')));
    }

    /**
     * Review fields in the format of ResearchRequestForms::reviewFields(), so the same inputs, validation and
     * storage are used.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        $fields = [];
        foreach (self::PARAMETERS as $number => $text) {
            $fields['score_' . $number] = [
                'label' => $number . '. ' . $text,
                'type' => 'radio',
                'required' => true,
                'options' => self::SCORES,
            ];
        }
        $fields['judgment'] = [
            'label' => 'Judgment',
            'type' => 'radio',
            'required' => true,
            'options' => self::JUDGMENTS,
        ];
        $fields['comments'] = [
            'label' => 'Comments and suggested revisions',
            'type' => 'textarea',
            'hint' => 'Required when the judgment is major revisions or failed to pass: the report is then returned to the researcher with these comments.',
        ];

        return $fields;
    }

    /** @return array<string, array<string, mixed>> the assessment inputs when this record and step call for them */
    public static function reviewFieldsFor(array $proposal, string $step): array
    {
        return proposal_is_terminal_report($proposal) && $step === self::STEP ? self::fields() : [];
    }

    /**
     * The saved assessment of a terminal report, with who entered it and when, or null when none yet.
     *
     * @return array{values: array<string, mixed>, assessor: string, date: string}|null
     */
    public static function saved(array $proposal): ?array
    {
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $values = is_array($summary) ? ($summary['reviews'][self::STEP] ?? null) : null;
        if (!is_array($values) || $values === []) {
            return null;
        }
        $endorsement = proposal_step_endorsement((int) ($proposal['id'] ?? 0), self::STEP, 'Research Coordinator (RIDE-RDO)');

        return [
            'values' => $values,
            'assessor' => (string) ($endorsement['name'] ?? ''),
            'date' => (string) ($endorsement['date'] ?? ''),
        ];
    }
}
