<?php

declare(strict_types=1);

namespace App\Support;

/**
 * What must be filled before the office form (.docx) of an RDO-20 application or RDO request form can be
 * previewed / printed. The same rules drive the form page (Print Preview stays disabled until they pass) and
 * the server (preview and download refuse an incomplete form).
 *
 * A rule: 'name' is the form input it watches ("[*]" matches any row index, "[]" a checkbox group), 'key'
 * the stored value (a proposals column when 'column' is true, else a summary key), 'label' what to tell the
 * user.
 */
final class FormPreviewRequirements
{
    /** Request-form fields the forms themselves mark optional ("if already decided", "leave blank", ...). */
    private const OPTIONAL_REQUEST_FIELDS = [
        'co_researchers', 'funds_downloaded', 'amount', 'target_conference', 'organizer', 'study_origin_completion_date',
    ];

    /** @return list<array{name: string, key: string, label: string, column?: bool}> */
    public static function forProposal(array $proposal): array
    {
        $type = ResearchRequestForms::typeOf($proposal);

        return $type !== null ? self::forRequest($type) : self::forResearchApplication();
    }

    /** @return list<array{name: string, key: string, label: string, column?: bool}> */
    public static function forResearchApplication(): array
    {
        $rules = [['name' => 'title', 'key' => 'title', 'label' => 'Title of Proposed Research', 'column' => true]];
        foreach ([
            'period_covered' => 'Period Covered',
            'duration_months' => 'Duration in Months',
            'funding_support[]' => 'Funding/Support',
            'abstract' => 'Abstract',
            'introduction' => 'Introduction',
            'research_gaps' => 'Research Gaps',
            'significance' => 'Significance of the Study',
            'objectives' => 'Objectives',
            'methods' => 'Materials and Methods',
            'gender_development' => 'Gender and Development',
            'ethical_considerations' => 'Ethical Considerations',
            'ched_achieve[]' => 'CHED A.C.H.I.E.V.E.',
            'sdgs[]' => 'Sustainable Development Goal/s',
            'expected_outcomes' => 'Expected Outcomes',
            'plan_year_from' => 'Implementation Plan year',
            'implementation_plan[*][activity]' => 'Implementation Plan activities',
            'sustainability_plan' => 'Sustainability Plan',
            'literature_cited' => 'Literature Cited',
        ] as $name => $label) {
            $rules[] = ['name' => $name, 'key' => self::keyOf($name), 'label' => $label];
        }

        return $rules;
    }

    /**
     * ESO-03 Extension Project Proposal: the identifying details and the core narrative sections.
     *
     * @return list<array{name: string, key: string, label: string, column?: bool}>
     */
    public static function forExtensionProposal(): array
    {
        $rules = [['name' => 'title', 'key' => 'title', 'label' => 'Title', 'column' => true]];
        foreach ([
            'implementing_college_department' => 'Implementing College/Department',
            'project_team_leader' => 'Project Team Leader',
            'location' => 'Location',
            'duration_start_date' => 'Duration start date',
            'duration_end_date' => 'Duration end date',
            'rationale' => 'Rationale',
            'general_objective' => 'General Objective',
            'specific_objectives' => 'Specific Objectives',
            'target_group_description' => 'Target Group',
            'methodology' => 'Methodology',
        ] as $name => $label) {
            $rules[] = ['name' => $name, 'key' => $name, 'label' => $label];
        }

        return $rules;
    }

    /** @return list<array{name: string, key: string, label: string, column?: bool}> */
    public static function forRequest(string $type): array
    {
        $definition = ResearchRequestForms::get($type);
        if ($definition === null) {
            return [];
        }
        $rules = [];
        if (($definition['research_link'] ?? null) === 'required') {
            $rules[] = ['name' => 'linked_proposal_id', 'key' => 'linked_proposal_id', 'label' => 'Research'];
        }
        foreach ($definition['fields'] as $name => $field) {
            if (in_array($name, self::OPTIONAL_REQUEST_FIELDS, true)) {
                continue;
            }
            $label = (isset($field['group']) ? $field['group'] . ': ' : '') . $field['label'];
            $rule = match ($field['type']) {
                'checks' => ['name' => $name . '[]', 'key' => $name, 'label' => $label],
                'rows' => ['name' => $name . '[*][' . array_key_first($field['columns']) . ']', 'key' => $name, 'label' => $label],
                'timeline' => ['name' => $name . '[*][activity]', 'key' => $name, 'label' => $label],
                default => ['name' => $name, 'key' => isset($field['column']) ? (string) $field['column'] : $name, 'label' => $label],
            };
            if (isset($field['column'])) {
                $rule['column'] = true;
            }
            if ($field['type'] === 'timeline') {
                $rules[] = ['name' => 'plan_year_from', 'key' => 'plan_year_from', 'label' => $field['label'] . ' year'];
            }
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * Names of a request form's fields that must be filled before Print Preview (for the * marks).
     *
     * @return list<string>
     */
    public static function requiredRequestFields(string $type): array
    {
        $fields = ResearchRequestForms::get($type)['fields'] ?? [];

        return array_values(array_diff(array_keys($fields), self::OPTIONAL_REQUEST_FIELDS));
    }

    /**
     * Labels of the rules a proposal does not meet yet.
     *
     * @param list<array{name: string, key: string, label: string, column?: bool}>|null $rules
     * @return list<string>
     */
    public static function missing(array $proposal, ?array $rules = null): array
    {
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $summary = is_array($summary) ? $summary : [];
        $missing = [];
        foreach ($rules ?? self::forProposal($proposal) as $rule) {
            $value = !empty($rule['column']) ? ($proposal[$rule['key']] ?? null) : ($summary[$rule['key']] ?? null);
            if (!self::filled($value)) {
                $missing[] = $rule['label'];
            }
        }

        return $missing;
    }

    /** Rules for the form page's script: input name pattern + label. */
    public static function forScript(array $rules): string
    {
        return (string) json_encode(array_map(static fn (array $r): array => ['name' => $r['name'], 'label' => $r['label']], $rules), JSON_UNESCAPED_SLASHES);
    }

    private static function filled(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::filled($item)) {
                    return true;
                }
            }
            return false;
        }

        return $value !== null && trim((string) $value) !== '' && (string) $value !== '0';
    }

    private static function keyOf(string $name): string
    {
        return (string) preg_replace('/\[.*$/', '', $name);
    }
}
