<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Research Accomplishments: the RDO counterpart of Conducted Activities. Each type mirrors the columns of
 * one RDO quarterly report form (WPU-QSF-RIDE-RDO-05 to -14, Rev.01 10.12.26), so the R&D Coordinator's
 * quarterly report can be generated from the accomplishments researchers enter. Documentary evidence
 * follows Part II of the Quarterly Research Accomplishment Reporting Checklist (WPU-QSF-RIDE-RDO-28).
 *
 * Definitions use the same shape as ExtensionActivityTypes::all() and are merged into it.
 */
final class ResearchAccomplishmentTypes
{
    public const FUNDING_SECTIONS = [
        'external' => 'A. Externally Funded',
        'inhouse' => 'B. In-house Funded',
        'personal' => 'C. Official Time',
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $category = ['program' => 'Program', 'project' => 'Project', 'study' => 'Study'];
        $budget = [
            'budget_source' => ['label' => 'Source', 'type' => 'text', 'group' => 'Budget', 'only' => ['external', 'inhouse']],
            'budget_amount' => ['label' => 'Amount (PhP)', 'type' => 'text', 'group' => 'Budget', 'only' => ['external', 'inhouse']],
        ];

        return [
            'completed_researches' => [
                'office' => 'rdo',
                'label' => 'Completed Research',
                'slug' => 'completed-researches',
                'description' => 'A research you completed (WPU-QSF-RIDE-RDO-05).',
                'title_field' => 'research_title',
                'sections' => self::fundingSections('date_completed'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'required' => true],
                    'researcher_names' => ['label' => 'Researcher(s) Name', 'type' => 'textarea'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Period Covered'],
                    'date_completed' => ['label' => 'Date Completed', 'type' => 'date', 'required' => true, 'group' => 'Period Covered'],
                    'duration_months' => ['label' => 'Duration in Months', 'type' => 'number'],
                ] + $budget + [
                    'category' => ['label' => 'Category', 'type' => 'select', 'options' => $category, 'report_label' => true],
                    'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'ongoing_researches' => [
                'office' => 'rdo',
                'label' => 'Ongoing Research',
                'slug' => 'ongoing-researches',
                'description' => 'A research still in progress, reported every quarter with its progress report (WPU-QSF-RIDE-RDO-06).',
                'title_field' => 'research_title',
                'sections' => self::fundingSections('progress_report_date'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'required' => true],
                    'researcher_names' => ['label' => 'Researcher(s) Name', 'type' => 'textarea'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Period Covered'],
                    'date_completed' => ['label' => 'Target Date of Completion', 'type' => 'date', 'group' => 'Period Covered'],
                    'duration_months' => ['label' => 'Duration in Months', 'type' => 'number'],
                ] + $budget + [
                    'category' => ['label' => 'Category', 'type' => 'select', 'options' => $category, 'report_label' => true],
                    'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
                    'progress_report_date' => [
                        'label' => 'Date of Progress Report', 'type' => 'date', 'required' => true,
                        'hint' => 'Places this update in a reporting quarter. Add a new entry each quarter the research is ongoing.',
                    ],
                ],
            ],
            'research_output_published' => [
                'office' => 'rdo',
                'label' => 'Research Output Published',
                'slug' => 'research-output-published',
                'description' => 'A research article or book published (WPU-QSF-RIDE-RDO-07).',
                'title_field' => 'article_title',
                'sections' => self::fundingSections('date_published'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'group' => 'Program/Project/Study'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Program/Project/Study'],
                    'date_completed' => ['label' => 'Date Completed', 'type' => 'date', 'group' => 'Program/Project/Study'],
                    'duration_months' => ['label' => 'Duration in Months', 'type' => 'number', 'group' => 'Program/Project/Study'],
                    'authors_researchers' => ['label' => 'Author(s)/Researcher(s)', 'type' => 'textarea'],
                    'article_title' => ['label' => 'Title of Article', 'type' => 'textarea', 'required' => true],
                    'journal_book_title' => ['label' => 'Title of Journal/Book', 'type' => 'text'],
                    'editors' => ['label' => 'Editor(s)', 'type' => 'text'],
                    'volume_issue' => ['label' => 'Vol. No./Issue No.', 'type' => 'text'],
                    'pages' => ['label' => 'Pages/Article Number', 'type' => 'text'],
                    'publication_year' => ['label' => 'Year of Publication', 'type' => 'text', 'placeholder' => '2026'],
                    'indexing' => ['label' => 'Indexing', 'type' => 'text', 'placeholder' => 'Scopus, Web of Science'],
                    'date_published' => ['label' => 'Date Published', 'type' => 'date', 'required' => true, 'hint' => 'Places this output in a reporting quarter.'],
                ],
            ],
            'research_output_presented' => [
                'office' => 'rdo',
                'label' => 'Research Output Presented',
                'slug' => 'research-output-presented',
                'description' => 'A research paper presented in a conference or forum (WPU-QSF-RIDE-RDO-08).',
                'title_field' => 'paper_title',
                'sections' => self::fundingSections('presentation_date'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'group' => 'Program/Project/Study'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Program/Project/Study'],
                    'date_completed' => ['label' => 'Date Completed', 'type' => 'date', 'group' => 'Program/Project/Study'],
                    'duration_months' => ['label' => 'Duration in Months', 'type' => 'number', 'group' => 'Program/Project/Study'],
                    'researchers' => ['label' => 'Researcher(s)', 'type' => 'textarea'],
                    'paper_title' => ['label' => 'Title of Research Paper', 'type' => 'textarea', 'required' => true],
                    'presenter_name' => ['label' => 'Name of Presenter', 'type' => 'text'],
                    'conference_title' => ['label' => 'Title of Conference/Forum', 'type' => 'text'],
                    'venue' => ['label' => 'Venue', 'type' => 'text'],
                    'presentation_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
                    'organizer' => ['label' => 'Organizer', 'type' => 'text'],
                    'conference_type' => ['label' => 'Type of Conference', 'type' => 'select', 'options' => [
                        'international' => 'International', 'national' => 'National', 'regional' => 'Regional', 'local' => 'Local',
                    ]],
                    'presentation_type' => ['label' => 'Type of Presentation', 'type' => 'select', 'options' => ['oral' => 'Oral', 'poster' => 'Poster']],
                    'award_received' => ['label' => 'Award Received', 'type' => 'text'],
                ],
            ],
            'commercialized' => [
                'office' => 'rdo',
                'label' => 'Technologies for Commercialization',
                'slug' => 'commercialized',
                'description' => 'A research work that produced a technology for commercialization or livelihood improvement (WPU-QSF-RIDE-RDO-09).',
                'title_field' => 'product_name',
                'sections' => self::fundingSections('date_adopted'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'required' => true],
                    'researchers' => ['label' => 'Researcher(s)', 'type' => 'textarea'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Period Covered'],
                    'date_completed' => ['label' => 'Date Completed', 'type' => 'date', 'group' => 'Period Covered'],
                    'product_name' => ['label' => 'Product Name/Methods/Process/Technology', 'type' => 'textarea', 'required' => true],
                    'adopter' => ['label' => 'Adopter (Name of Industry/Beneficiary)', 'type' => 'text'],
                    'date_adopted' => ['label' => 'Date Adopted', 'type' => 'date', 'required' => true],
                ],
            ],
            'resulted_in_extension' => [
                'office' => 'rdo',
                'label' => 'Resulted in Extension',
                'slug' => 'resulted-in-extension',
                'description' => 'A research work that resulted in an extension program or activity (WPU-QSF-RIDE-RDO-10).',
                'title_field' => 'extension_program_activity',
                'sections' => self::fundingSections('date'),
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'required' => true],
                    'researchers' => ['label' => 'Researcher(s)', 'type' => 'textarea'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'group' => 'Period Covered'],
                    'date_completed' => ['label' => 'Date Completed', 'type' => 'date', 'group' => 'Period Covered'],
                    'extension_program_activity' => ['label' => 'Title of Extension Program/Activity', 'type' => 'textarea', 'required' => true],
                    'faculty_staff_involved' => ['label' => 'Faculty/Staff Involved', 'type' => 'textarea'],
                ] + $budget + [
                    'venue' => ['label' => 'Venue', 'type' => 'text'],
                    'date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
                ],
            ],
            'journal_citation' => [
                'office' => 'rdo',
                'label' => 'Journal Citation',
                'slug' => 'journal-citation',
                'description' => 'Your research article cited by other researchers in a refereed journal article (WPU-QSF-RIDE-RDO-11).',
                'title_field' => 'title_original_article_cited',
                'sections' => ['' => ['label' => '', 'path' => ['entries'], 'date_field' => 'date_cited']],
                'fields' => [
                    'title_original_article_cited' => ['label' => 'Title of Research Article Cited', 'type' => 'textarea', 'required' => true, 'group' => 'Original Research Article Published'],
                    'authors_original_article' => ['label' => 'Author(s)', 'type' => 'text', 'group' => 'Original Research Article Published'],
                    'title_refereed_journal_original' => ['label' => 'Title of Refereed Journal', 'type' => 'text', 'group' => 'Original Research Article Published'],
                    'title_new_research_article' => ['label' => 'Title of Research Article', 'type' => 'textarea', 'required' => true, 'group' => 'Research Article Citation'],
                    'authors_new_article' => ['label' => 'Author(s)', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'title_refereed_journal_new' => ['label' => 'Title of Refereed Journal', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'volume_issue' => ['label' => 'Vol. No./Issue No.', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'pages' => ['label' => 'Pages/Article Number', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'date_cited' => ['label' => 'Date Cited', 'type' => 'date', 'required' => true, 'group' => 'Research Article Citation'],
                    'year_publication' => ['label' => 'Year of Publication', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'issn' => ['label' => 'ISSN', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'publisher' => ['label' => 'Publisher', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'indexing' => ['label' => 'Indexing', 'type' => 'text', 'group' => 'Research Article Citation'],
                ],
            ],
            'book_citation' => [
                'office' => 'rdo',
                'label' => 'Book Citation',
                'slug' => 'book-citation',
                'description' => 'Your research article cited by other researchers in a book (WPU-QSF-RIDE-RDO-12).',
                'title_field' => 'title_original_article_cited',
                'sections' => ['' => ['label' => '', 'path' => ['entries'], 'date_field' => 'date_cited']],
                'fields' => [
                    'title_original_article_cited' => ['label' => 'Title of Research Article Cited', 'type' => 'textarea', 'required' => true, 'group' => 'Original Research Article Published'],
                    'authors_original_article' => ['label' => 'Author(s)', 'type' => 'text', 'group' => 'Original Research Article Published'],
                    'title_publication_original' => ['label' => 'Title of Refereed Journal', 'type' => 'text', 'group' => 'Original Research Article Published'],
                    'title_new_book_chapter' => ['label' => 'Title of Book/Chapter', 'type' => 'textarea', 'required' => true, 'group' => 'Research Article Citation'],
                    'authors_book_chapter' => ['label' => 'Author(s)', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'title_book_chapter_published' => ['label' => 'Title of Book', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'volume_issue' => ['label' => 'Vol. No./Issue No.', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'pages' => ['label' => 'Pages/Article Number', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'date_cited' => ['label' => 'Date Cited', 'type' => 'date', 'required' => true, 'group' => 'Research Article Citation'],
                    'year_publication' => ['label' => 'Year of Publication', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'isbn' => ['label' => 'ISBN', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'publisher' => ['label' => 'Publisher', 'type' => 'text', 'group' => 'Research Article Citation'],
                    'indexing' => ['label' => 'Indexing', 'type' => 'text', 'group' => 'Research Article Citation'],
                ],
            ],
            'inventions_um_copyrights' => [
                'office' => 'rdo',
                'label' => 'Inventions, Utility Models, Copyrights',
                'slug' => 'inventions-um-copyrights',
                'description' => 'An invention, utility model or copyright from your research (WPU-QSF-RIDE-RDO-13).',
                'title_field' => 'research_title',
                'sections' => [
                    'inventions_patented' => ['label' => 'Invention - Patented', 'path' => ['entries', 'inventions_patented'], 'date_field' => 'date_of_issue_application'],
                    'inventions_applied_for_patenting' => ['label' => 'Invention - Applied for Patenting', 'path' => ['entries', 'inventions_applied_for_patenting'], 'date_field' => 'date_of_issue_application'],
                    'inventions_not_patented_but_utilized' => ['label' => 'Invention - Not Patented but Utilized by the Community', 'path' => ['entries', 'inventions_not_patented_but_utilized'], 'date_field' => 'date_developed_completed'],
                    'utility_models_registered' => ['label' => 'Utility Model - Registered', 'path' => ['entries', 'utility_models_registered'], 'date_field' => 'date_of_issue_application'],
                    'utility_models_applied_for_registration' => ['label' => 'Utility Model - Applied for Registration', 'path' => ['entries', 'utility_models_applied_for_registration'], 'date_field' => 'date_of_issue_application'],
                    'copyrights' => ['label' => 'Copyright', 'path' => ['entries', 'copyrights'], 'date_field' => 'date_of_issue_application'],
                ],
                'fields' => [
                    'research_title' => ['label' => 'Research Title', 'type' => 'textarea', 'required' => true],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date'],
                    'date_developed_completed' => ['label' => 'Date Developed/Completed', 'type' => 'date'],
                    'inventors_researchers' => ['label' => 'Inventor(s)/Researcher(s)', 'type' => 'textarea'],
                    'patent_registration_copyright_number' => ['label' => 'Patent Registration/Copyright Number', 'type' => 'text'],
                    'date_of_issue_application' => [
                        'label' => 'Date of Issue/Application', 'type' => 'date',
                        'hint' => 'Required, except for inventions not patented (those use the Date Developed/Completed).',
                    ],
                    'adopter' => ['label' => 'Adopters of Inventions/UM/Copyrights', 'type' => 'text'],
                    'commercial_product_name' => ['label' => 'Name of Commercial Product', 'type' => 'text'],
                ],
            ],
            'linkages' => [
                'office' => 'rdo',
                'label' => 'Research Linkages',
                'slug' => 'linkages',
                'description' => 'A research linkage, partnership or collaboration (WPU-QSF-RIDE-RDO-14).',
                'title_field' => 'partner',
                'sections' => ['' => ['label' => '', 'path' => ['entries'], 'date_field' => 'date_started']],
                'fields' => [
                    'program' => ['label' => 'Linkage/Partnership/Collaboration Program', 'type' => 'textarea'],
                    'partner' => ['label' => 'Partner/Collaborating Institution', 'type' => 'textarea', 'required' => true],
                    'linkage_forged' => ['label' => 'Linkage Forged (MOA/MOU)', 'type' => 'text'],
                    'institution_type' => ['label' => 'Institutional Type of Partner Institution', 'type' => 'text'],
                    'deliverables' => ['label' => 'Deliverables or Desired Output/s', 'type' => 'textarea'],
                    'date_started' => ['label' => 'Date Started', 'type' => 'date', 'required' => true, 'group' => 'Validity of Implementation'],
                    'date_completed' => ['label' => 'Date of Completion', 'type' => 'date', 'group' => 'Validity of Implementation'],
                    'personnel' => ['label' => 'Personnel Involved', 'type' => 'textarea'],
                    'beneficiaries' => ['label' => 'Target Beneficiaries', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    /**
     * Documentary evidence per accomplishment, from the Accomplishment Validation Matrix of the
     * reporting checklist (Part II, C).
     *
     * @return array<string, array<string, string>>|null null when the type is not a research type
     */
    public static function documentChecklist(string $type): ?array
    {
        $optional = ExtensionActivityTypes::OPTIONAL_GROUP;

        return match ($type) {
            'completed_researches' => ['Documentary Evidence' => [
                'rdo_terminal_report' => 'Terminal Report',
                'rdo_completed_photos' => 'Supporting photographs',
            ]],
            'ongoing_researches' => ['Documentary Evidence' => [
                'rdo_progress_report' => 'Progress Report',
                'rdo_ongoing_photos' => 'Supporting photographs',
            ]],
            'research_output_published' => ['Documentary Evidence' => [
                'rdo_published_article' => 'E-copy of published article',
            ]],
            'research_output_presented' => [
                'Documentary Evidence' => [
                    'rdo_presentation_certificate' => 'Certificate of presentation/participation',
                    'rdo_presentation_program' => 'Program',
                ],
                $optional => [
                    'rdo_presentation_acceptance' => 'Letter of acceptance',
                    'rdo_presentation_award' => 'Certificate or equivalent document for award received',
                ],
            ],
            'commercialized' => [
                'Documentary Evidence' => ['rdo_commercialized_photos' => 'Supporting photographs'],
                $optional => [
                    'rdo_commercialized_moa' => 'MOA/MOU',
                    'rdo_commercialized_other' => 'Other documents required for validation',
                ],
            ],
            'resulted_in_extension' => [
                'Documentary Evidence' => ['rdo_extension_etr' => 'End of Training Report (ETR)'],
                $optional => ['rdo_extension_other' => 'Other supporting documents'],
            ],
            'journal_citation' => ['Documentary Evidence' => [
                'rdo_journal_citation_confirmation' => 'E-copy of citation confirmation (thru email or other means)',
            ]],
            'book_citation' => ['Documentary Evidence' => [
                'rdo_book_citation_confirmation' => 'E-copy of citation confirmation (thru email or other means)',
            ]],
            'inventions_um_copyrights' => [
                'Documentary Evidence' => ['rdo_ip_application' => 'Copy of Application Form or Certificate of Registration'],
                $optional => ['rdo_ip_other' => 'Other supporting documents'],
            ],
            'linkages' => [
                'Documentary Evidence' => ['rdo_linkage_moa_mou' => 'MOA or MOU'],
                $optional => ['rdo_linkage_other' => 'Other supporting documents'],
            ],
            default => null,
        };
    }

    /** @return array<string, array{label: string, path: list<string>, date_field: string}> */
    private static function fundingSections(string $dateField): array
    {
        $sections = [];
        foreach (self::FUNDING_SECTIONS as $key => $label) {
            $sections[$key] = ['label' => $label, 'path' => ['entries', $key], 'date_field' => $dateField];
        }

        return $sections;
    }
}
