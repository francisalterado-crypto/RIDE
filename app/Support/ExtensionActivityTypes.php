<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Field definitions for entries that feed the quarterly reports: Conducted Activities (ESO) and, merged in
 * from ResearchAccomplishmentTypes, Research Accomplishments (RDO). Each type mirrors the columns of one
 * quarterly report form, so entries can be dropped straight into that report's rows. The 'office' key says
 * which page a type belongs to.
 */
final class ExtensionActivityTypes
{
    /** Checklist group whose items are attached only when they apply; they don't count as missing. */
    public const OPTIONAL_GROUP = 'Optional';

    private const RATING_LEVELS = ['poor' => 'Poor (1)', 'fair' => 'Fair (2)', 'good' => 'Good (3)', 'better' => 'Better (4)', 'best' => 'Best (5)'];

    /**
     * @return array<string, array{
     *     label: string,
     *     slug: string,
     *     description: string,
     *     title_field: string,
     *     sections: array<string, array{label: string, path: list<string>, date_field: string}>,
     *     fields: array<string, array{label: string, type: string, required?: bool, placeholder?: string, options?: array<string, string>, group?: string, only?: list<string>}>
     * }>
     */
    public static function all(): array
    {
        return self::extensionTypes() + ResearchAccomplishmentTypes::all();
    }

    /** @return array<string, array<string, mixed>> types of one office ('eso' or 'rdo') */
    public static function forOffice(string $office): array
    {
        return array_filter(self::all(), static fn (array $definition): bool => $definition['office'] === $office);
    }

    public static function officeOf(string $type): string
    {
        return (string) (self::get($type)['office'] ?? 'eso');
    }

    /** URL prefix of an office's entry pages. */
    public static function basePath(string $office): string
    {
        return $office === 'rdo' ? 'research-accomplishments' : 'activities';
    }

    /** @return array{page: string, entry: string, entries: string, verb: string} wording per office */
    public static function officeTerms(string $office): array
    {
        return $office === 'rdo'
            ? ['page' => 'Research Accomplishments', 'entry' => 'accomplishment', 'entries' => 'accomplishments', 'verb' => 'Reported by']
            : ['page' => 'Conducted Activities', 'entry' => 'activity', 'entries' => 'activities', 'verb' => 'Conducted by'];
    }

    /** @return array<string, array<string, mixed>> */
    private static function extensionTypes(): array
    {
        return [
            'trainings_conducted' => [
                'label' => 'Trainings Conducted',
                'office' => 'eso',
                'slug' => 'trainings-conducted',
                'description' => 'A training your office conducted (WPU-QSF-RIDE-ESO-06).',
                'title_field' => 'training_title',
                'sections' => [
                    'need_based' => ['label' => 'Need-based training', 'path' => ['entries', 'need_based'], 'date_field' => 'training_date'],
                    'other' => ['label' => 'Other training', 'path' => ['entries', 'other'], 'date_field' => 'training_date'],
                ],
                'fields' => [
                    'extension_program' => ['label' => 'Extension Program', 'type' => 'textarea'],
                    'training_title' => ['label' => 'Title of Training', 'type' => 'textarea', 'required' => true],
                    'venue' => ['label' => 'Venue', 'type' => 'text'],
                    'training_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
                    'participant_type' => ['label' => 'Type of Participants', 'type' => 'text', 'placeholder' => 'Farmers'],
                    'persons_trained' => ['label' => 'No. of Persons Trained', 'type' => 'number'],
                    'training_length' => ['label' => 'Length of Training', 'type' => 'select', 'options' => [
                        'lt_8h' => '< 8 hours (0.5)',
                        '8h_1d' => '8 hours or 1 day (1)',
                        '2d' => '2 days (1.25)',
                        '3_4d' => '3-4 days (1.5)',
                        '5d_plus' => '5 days or more (2)',
                    ]],
                    'personnel_name' => ['label' => 'Name', 'type' => 'text', 'group' => 'Personnel Involved'],
                    'personnel_role' => ['label' => 'Role', 'type' => 'text', 'placeholder' => 'e.g. Facilitator', 'group' => 'Personnel Involved'],
                    'budget_source' => ['label' => 'Source', 'type' => 'text', 'group' => 'Budget'],
                    'budget_amount' => ['label' => 'Amount', 'type' => 'text', 'group' => 'Budget'],
                    'trainees_surveyed' => ['label' => 'No. of Trainees Surveyed', 'type' => 'number'],
                ] + self::ratingFields('quality', 'Trainees Rating Quality')
                  + self::ratingFields('timeliness', 'Trainees Rating Timeliness'),
            ],
            'technical_advisory' => [
                'label' => 'Technical Advisory',
                'office' => 'eso',
                'slug' => 'technical-advisory',
                'description' => 'A technical advisory service your office extended.',
                'title_field' => 'technical_advisory_conducted',
                'sections' => [
                    '' => ['label' => '', 'path' => ['entries'], 'date_field' => 'advisory_date'],
                ],
                'fields' => [
                    'extension_program' => ['label' => 'Extension Program', 'type' => 'textarea'],
                    'technical_advisory_conducted' => ['label' => 'Technical Advisory Conducted', 'type' => 'textarea', 'required' => true],
                    'venue' => ['label' => 'Venue', 'type' => 'text'],
                    'advisory_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
                    'client_type' => ['label' => 'Type of Clients', 'type' => 'text'],
                    'clients_served' => ['label' => 'No. of Clients Served', 'type' => 'number'],
                    'personnel_name' => ['label' => 'Resource Person / Name of Personnel', 'type' => 'text'],
                    'trainees_surveyed' => ['label' => 'No. of Trainees Surveyed', 'type' => 'number'],
                ] + self::ratingFields('quality', 'No. of Trainees who Rate the Quality as')
                  + self::ratingFields('timeliness', 'No. of Trainees who Rate the Timeliness as'),
            ],
            'extension_linkages' => [
                'label' => 'Extension Linkages',
                'office' => 'eso',
                'slug' => 'extension-linkages',
                'description' => 'A linkage or partnership and the extension activities done under it.',
                'title_field' => 'partner',
                'sections' => [
                    '' => ['label' => '', 'path' => ['entries'], 'date_field' => 'conducted_from'],
                ],
                'fields' => [
                    'partner' => ['label' => 'Partner LGU / Community / Industry / SMEs / Private or Public Agencies / NGOs', 'type' => 'textarea', 'required' => true],
                    'linkage_forged' => ['label' => 'Linkage Forged (MOA/MOU)', 'type' => 'text'],
                    'description' => ['label' => 'Description of Linkage/Partnership', 'type' => 'textarea'],
                    'effectivity_from' => ['label' => 'From', 'type' => 'date', 'group' => 'Effectivity of MOA/MOU'],
                    'effectivity_to' => ['label' => 'To', 'type' => 'date', 'group' => 'Effectivity of MOA/MOU'],
                    'extension_activities' => ['label' => 'Extension Activities', 'type' => 'textarea'],
                    'conducted_from' => ['label' => 'From', 'type' => 'date', 'required' => true, 'group' => 'Date Conducted'],
                    'conducted_to' => ['label' => 'To', 'type' => 'date', 'group' => 'Date Conducted'],
                ],
            ],
            'outreach_activities' => [
                'label' => 'Outreach Activities',
                'office' => 'eso',
                'slug' => 'outreach-activities',
                'description' => 'An outreach activity your office held.',
                'title_field' => 'title',
                'sections' => [
                    '' => ['label' => '', 'path' => ['entries'], 'date_field' => 'start_date'],
                ],
                'fields' => [
                    'title' => ['label' => 'Title of Outreach Activity', 'type' => 'textarea', 'required' => true],
                    'venue' => ['label' => 'Venue', 'type' => 'text'],
                    'start_date' => ['label' => 'Start', 'type' => 'date', 'required' => true, 'group' => 'Inclusive Dates'],
                    'end_date' => ['label' => 'End', 'type' => 'date', 'group' => 'Inclusive Dates'],
                    'beneficiaries' => ['label' => 'No. of Beneficiaries', 'type' => 'number'],
                ],
            ],
            'technology_adoption' => [
                'label' => 'Technology Adoption',
                'office' => 'eso',
                'slug' => 'technology-adoption',
                'description' => 'A technology adopted through a demonstration farm or a training extended.',
                'title_field' => 'technology_demonstrated',
                'sections' => [
                    'demonstration_farm' => ['label' => 'Through the Demonstration Farm', 'path' => ['demonstration_farm_entries'], 'date_field' => 'date_adopted'],
                    'training' => ['label' => 'Through Trainings Extended', 'path' => ['training_entries'], 'date_field' => 'date_conducted'],
                ],
                'fields' => [
                    'extension_personnel' => ['label' => 'Name of Extension Personnel', 'type' => 'text'],
                    'demonstration_farm' => ['label' => 'Name and Location of Demonstration Farm', 'type' => 'textarea', 'only' => ['demonstration_farm']],
                    'year_established' => ['label' => 'Year Established', 'type' => 'text', 'placeholder' => '2024', 'only' => ['demonstration_farm']],
                    'extension_service_title' => ['label' => 'Title of Extension Service Provided', 'type' => 'textarea', 'only' => ['training']],
                    'date_conducted' => ['label' => 'Date Conducted', 'type' => 'date', 'required' => true, 'only' => ['training']],
                    'technology_demonstrated' => ['label' => 'Technology Demonstrated / Product / Enterprise', 'type' => 'textarea', 'required' => true],
                    'adopter_name' => ['label' => 'Name of Adopter', 'type' => 'text'],
                    'adopter_location' => ['label' => 'Location of Adopter', 'type' => 'text'],
                    'date_adopted' => ['label' => 'Adopted', 'type' => 'date', 'group' => 'Date Adopted'],
                    'date_commercialized' => ['label' => 'Commercialized', 'type' => 'date', 'group' => 'Date Adopted'],
                    'net_income' => ['label' => 'Net Income', 'type' => 'text'],
                ],
            ],
        ];
    }

    /**
     * Documents required with each activity, grouped as on the paper checklist: a training's ETR,
     * a linkage's or technology adoption's MOA, an outreach or technical advisory Accomplishment Report.
     *
     * @return array<string, array<string, string>> group label => [category key => document label]
     */
    public static function documentChecklist(string $type): array
    {
        $research = ResearchAccomplishmentTypes::documentChecklist($type);
        if ($research !== null) {
            return $research;
        }

        return match ($type) {
            'trainings_conducted' => self::etrChecklist(),
            'extension_linkages' => ['MOA/MOU' => ['linkage_moa_mou' => 'Notarized MOA/MOU']],
            'outreach_activities' => [
                'Accomplishment Report' => [
                    'outreach_accomplishment_report' => 'Outreach Activities Report (WPU-QSF-RIDE-ESO-15 template) with narrative report, documentation photos and signature of resource person',
                    'outreach_attendance_sheet' => 'Attendance sheet',
                ],
                self::OPTIONAL_GROUP => ['outreach_moa' => 'MOA (for active linkages)'],
            ],
            'technical_advisory' => [
                'Accomplishment Report' => [
                    'advisory_accomplishment_report' => 'Technical Advisory Report (WPU-QSF-RIDE-ESO-14 template) with report, documentation photos and signature of resource person',
                ],
                self::OPTIONAL_GROUP => ['advisory_moa' => 'MOA (for active linkages)'],
            ],
            'technology_adoption' => ['MOA/MOU' => ['adoption_moa_mou' => 'Notarized MOA/MOU or its equivalent as proof of adoption']],
            default => [],
        };
    }

    /**
     * Short and long names of a type's required documents, e.g. ['ETR', 'ETR (Extension Terminal Report)'].
     *
     * @return array{0: string, 1: string}
     */
    public static function documentsName(string $type): array
    {
        return match ($type) {
            'trainings_conducted' => ['ETR', 'ETR (Extension Terminal Report)'],
            'extension_linkages', 'technology_adoption' => ['MOA', 'MOA/MOU'],
            'outreach_activities', 'technical_advisory' => ['AR', 'Accomplishment Report'],
            default => self::officeOf($type) === 'rdo' ? ['Evidence', 'Documentary Evidence'] : ['Documents', 'Required documents'],
        };
    }

    /** @return array<string, array<string, string>> */
    private static function etrChecklist(): array
    {
        return [
            'ETR' => [
                'etr_title_page' => 'Title Page with group pictures',
                'etr_transmittal_letter' => 'Transmittal Letter (duly signed by the training/project coordinator and Dean)',
                'etr_accomplishment_report' => 'Accomplishment/Narrative Report (WPU-QSF-RIDE-ESO-13 Accomplishment Report template)',
            ],
            'Appendices' => [
                'etr_group_picture' => 'Group picture (with participants)',
                'etr_team_picture' => 'Training team picture',
                'etr_activity_pictures' => 'Pictures during activities (6-10 pictures)',
                'etr_request_letter' => 'Approved request letter',
                'etr_travel_order' => 'Travel order (TARF & NTARF)',
                'etr_cert_participation' => 'Certificate of participation (1 sample only)',
                'etr_cert_appreciation' => 'Certificates of appreciation from client/partner',
                'etr_cert_recognition' => 'Certificates of recognition (resource person, facilitator, coordinator, secretariat, etc.)',
                'etr_attendance_sheet' => 'Attendance sheet (clear & readable)',
                'etr_program' => 'Program',
                'etr_iec_materials' => 'IEC materials / brochure',
                'etr_ebalwasyon' => 'Ebalwasyon ng Gawain (1 sample only)',
            ],
        ];
    }

    /** @return array<string, string> category key => label, across all groups */
    public static function documentCategories(string $type): array
    {
        $flat = [];
        foreach (self::documentChecklist($type) as $items) {
            $flat += $items;
        }

        return $flat;
    }

    /**
     * Checklist items that count toward completeness (everything outside the optional group).
     *
     * @return array<string, string>
     */
    public static function requiredDocumentCategories(string $type): array
    {
        $flat = [];
        foreach (self::documentChecklist($type) as $group => $items) {
            if ($group !== self::OPTIONAL_GROUP) {
                $flat += $items;
            }
        }

        return $flat;
    }

    /** @return list<string> required category keys of every type (keys are unique across types) */
    public static function allRequiredDocumentCategories(): array
    {
        $keys = [];
        foreach (array_keys(self::all()) as $type) {
            $keys = array_merge($keys, array_keys(self::requiredDocumentCategories((string) $type)));
        }

        return $keys;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public static function fromSlug(string $slug): ?string
    {
        foreach (self::all() as $type => $definition) {
            if ($definition['slug'] === $slug) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Fields that belong to one section of a type.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fieldsFor(string $type, string $section): array
    {
        $definition = self::get($type);
        if ($definition === null) {
            return [];
        }

        return array_filter(
            $definition['fields'],
            static fn (array $field): bool => !isset($field['only']) || in_array($section, $field['only'], true)
        );
    }

    /** The date that places an entry of this type and section in a reporting quarter. */
    public static function dateFieldFor(string $type, string $section): string
    {
        $definition = self::get($type);

        return (string) ($definition['sections'][$section]['date_field'] ?? '');
    }

    /**
     * First and last day of a reporting period key (e.g. 2026-Q2). A quarter starts the day after the
     * previous deadline month ends, matching how QuarterlyReporting::currentPeriodKey assigns dates.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function periodDateRange(string $periodKey): ?array
    {
        if (!preg_match('/^(\d{4})-Q(\d+)$/', $periodKey, $matches)) {
            return null;
        }

        $months = QuarterlyReporting::deadlineMonths();
        $year = (int) $matches[1];
        $index = (int) $matches[2] - 1;
        if (!isset($months[$index])) {
            return null;
        }

        $tz = QuarterlyReporting::timezone();
        $end = (new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $months[$index]), $tz))->modify('last day of this month');
        $previous = $index > 0
            ? new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $months[$index - 1]), $tz)
            : new \DateTimeImmutable(sprintf('%d-%02d-01', $year - 1, $months[count($months) - 1]), $tz);
        $start = $previous->modify('first day of next month');

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /** @return array<string, array<string, mixed>> */
    private static function ratingFields(string $prefix, string $group): array
    {
        $fields = [];
        foreach (self::RATING_LEVELS as $level => $label) {
            $fields[$prefix . '_' . $level] = ['label' => $label, 'type' => 'number', 'group' => $group];
        }

        return $fields;
    }
}
