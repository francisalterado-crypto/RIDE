<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Proposal;

/**
 * The RDO request forms filed by researchers (WPU-QSF-RIDE-RDO-23, -29 to -32, Rev.01 10.12.26). They are
 * saved as proposals (summary.form_type) so they go through the usual approval chain: Researcher > R&D
 * Coordinator > Dean > RDO Director > VPRIDE.
 *
 * Forms that refer to an existing research offer a picker of the researcher's approved Research Proposal
 * Applications; the picked one's RIDE Control No. (its project code), title and researchers fill the form.
 *
 * Field types: text, textarea, date, number, radio (options), checks (options), rows (columns), timeline.
 * A field with 'column' is stored in that proposals column instead of the summary.
 */
final class ResearchRequestForms
{
    private const CONFERENCE_LEVELS = [
        'international' => 'International Conference',
        'national' => 'National Conference',
        'regional' => 'Regional Conference',
        'local' => 'Local Conference',
    ];

    /** Font Awesome icon class of a request form in the sidebar, by its slug. */
    public static function navIcon(string $slug): string
    {
        return match ($slug) {
            'critical-activities' => 'fa-list-check',
            'program-project-study-center' => 'fa-diagram-project',
            'external-funding' => 'fa-hand-holding-dollar',
            'paper-presentation' => 'fa-microphone-lines',
            'manuscript' => 'fa-pen-nib',
            'presentation-incentive' => 'fa-award',
            'ipr-incentive' => 'fa-certificate',
            'publication-incentive' => 'fa-book-bookmark',
            default => 'fa-file-pen',
        };
    }

    /** @return array<string, array<string, mixed>> form_type => definition */
    public static function all(): array
    {
        $sdgs = ['label' => 'SDG/s', 'type' => 'checks', 'options' => ResearchFormOptions::SDGS];
        $ched = ['label' => 'CHED A.C.H.I.E.V.E.', 'type' => 'checks', 'options' => ResearchFormOptions::CHED_ACHIEVE];
        $gad = ['label' => 'Gender and Development', 'type' => 'textarea'];
        $lead = ['label' => 'Lead Researcher/s', 'type' => 'textarea', 'required' => true, 'default' => 'proponent'];
        $co = ['label' => 'Co-researchers/collaborators', 'type' => 'textarea'];
        $fundsDownloaded = ['label' => 'For externally funded, specify if funds are downloaded to WPU', 'type' => 'text'];
        // Percentage Contribution for Incentives Claim (research output with multiple authors), filed with the request.
        $contributions = ['label' => 'Percentage Contribution', 'type' => 'rows', 'prefill' => 'applicant',
            'card_title' => 'Percentage Contribution',
            'hint' => 'For outputs with multiple WPU authors: list every WPU author, the applicant included, with their contribution. The percentages must total 100. Each author signs the conforme column of the printed form.',
            'item_label' => 'Author', 'add_label' => 'Add author',
            'column_types' => ['percent' => 'percent', 'contribution' => 'long'],
            'columns' => [
                'name' => 'Name',
                'position_college' => 'Position and College/Unit/Center',
                'specialization' => 'Area of Specialization',
                'contribution' => 'Specific Contribution',
                'percent' => '% Contribution',
            ]];
        $origin = [
            'study_origin_status' => ['label' => 'Ongoing or completed', 'type' => 'radio', 'options' => ['ongoing' => 'Ongoing', 'completed' => 'Completed'], 'group' => 'Program/Project/Study origin'],
            'study_origin_completion_date' => ['label' => 'Date of completion (if completed)', 'type' => 'date', 'group' => 'Program/Project/Study origin'],
        ];

        return [
            'critical_activities_request' => [
                'label' => 'Critical Activities Request',
                'slug' => 'critical-activities',
                'code' => 'WPU-QSF-RIDE-RDO-23',
                'heading' => 'CRITICAL ACTIVITIES REQUEST FORM',
                'description' => 'Request to continue critical lab-based research activities.',
                'research_link' => 'required',
                'intro' => 'For purposes of lab-based research, critical research activities are those that would be impossible to restart; could not be restarted without a significant investment of time; would require significant financial resources to be restarted; involve seasonality; are extremely time-sensitive; or are critical graduate student experiments required to ensure graduation during this calendar year.',
                'contact' => true,
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'column' => 'title', 'from_research' => 'title'],
                    'activities' => ['label' => 'Critical Activities Requested to Continue', 'type' => 'rows', 'columns' => [
                        'research_activity' => 'Research Activity',
                        'justification' => 'Description & Justification for Why this Activity is Critical',
                        'location' => 'Location',
                        'team_members' => 'Names of Researcher/Team Member(s)',
                        'length_of_time' => 'Length of Time Activities Must Continue',
                    ]],
                ],
            ],
            'research_program_proposal' => [
                'label' => 'Program/Project/Study/Center Proposal',
                'slug' => 'program-project-study-center',
                'code' => 'WPU-QSF-RIDE-RDO-29',
                'heading' => 'RESEARCH PROGRAM, PROJECT, STUDY, CENTER PROPOSAL FORM',
                'description' => 'Proposal for a new research program, project, study or center.',
                'research_link' => null,
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'research_category' => ['label' => 'Research Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'program' => 'Program', 'project' => 'Project', 'study' => 'Study', 'center' => 'Center',
                    ]],
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'amount' => ['label' => 'Amount (PhP)', 'type' => 'text', 'hint' => 'For research conducted on official time, leave blank.'],
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission', 'type' => 'date', 'group' => 'Duration'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                    'expected_outcomes' => ['label' => 'Expected Outcomes', 'type' => 'textarea'],
                    'implementation_plan' => ['label' => 'Implementation Plan and Time Frame', 'type' => 'timeline', 'year_label' => 'FY'],
                ],
            ],
            'external_funding_proposal' => [
                'label' => 'Writing Proposal for External Funding',
                'slug' => 'external-funding',
                'code' => 'WPU-QSF-RIDE-RDO-30',
                'heading' => 'WRITING PROPOSAL FOR EXTERNAL FUNDING FORM',
                'description' => 'Request to write a research proposal for external funding.',
                'research_link' => null,
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'research_category' => ['label' => 'Research Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'program' => 'Program', 'project' => 'Project', 'study' => 'Study',
                    ]],
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Target Funding Source', 'type' => 'text', 'required' => true, 'column' => 'funding_source'],
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission', 'type' => 'date', 'group' => 'Duration'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                ],
            ],
            'paper_presentation' => [
                'label' => 'Paper Presentation',
                'slug' => 'paper-presentation',
                'code' => 'WPU-QSF-RIDE-RDO-31',
                'heading' => 'RESEARCH/INNOVATION/EXTENSION PAPER PRESENTATION FORM',
                'description' => 'Request to present a research, innovation or extension paper.',
                'research_link' => 'optional',
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'study_origin_details' => ['label' => 'Program/Project/Study', 'type' => 'textarea', 'group' => 'Program/Project/Study origin', 'from_research' => 'origin'],
                ] + $origin + [
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'presentation_category' => ['label' => 'Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'research' => 'Research', 'innovation' => 'Innovation', 'extension' => 'Extension',
                    ]],
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source', 'from_research' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'target_presentation_date' => ['label' => 'Target date of Presentation', 'type' => 'date'],
                    'target_conference' => ['label' => 'Target Symposium/Forum/Conference (if already decided)', 'type' => 'text'],
                    'organizer' => ['label' => 'Organizer', 'type' => 'text'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'highlights_of_results' => ['label' => 'Highlights of results', 'type' => 'textarea'],
                ],
            ],
            'manuscript' => [
                'label' => 'Manuscript Writing for Publication',
                'slug' => 'manuscript',
                'code' => 'WPU-QSF-RIDE-RDO-32',
                'heading' => 'MANUSCRIPT WRITING FOR PUBLICATION FORM',
                'description' => 'Request to write a manuscript for journal publication.',
                'research_link' => 'optional',
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'study_origin_details' => ['label' => 'Program/Project/Study', 'type' => 'textarea', 'group' => 'Program/Project/Study origin', 'from_research' => 'origin'],
                ] + $origin + [
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source', 'from_research' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission to journal', 'type' => 'date', 'group' => 'Duration'],
                    'target_journal' => ['label' => 'Target Journal/publisher', 'type' => 'text'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                    'highlights_of_results' => ['label' => 'Highlights of results', 'type' => 'textarea'],
                ],
            ],
            // Requests for incentive (WPU-QSF-RIDE-RDO-02 to -04, Rev.01 10.12.26), per procedure 04 "Granting of
            // Incentive for Research Output". Sections (5) to (9) of the form are filled by the reviewers: the
            // College R&D Coordinator, the Dean, the RDO M&E Officer (the Research Coordinator, RIDE-RDO), the RDO
            // Director and the VP for RIDE.
            'presentation_incentive' => [
                'label' => 'Presentation Incentive',
                'slug' => 'presentation-incentive',
                'code' => 'WPU-QSF-RIDE-RDO-02',
                'group' => 'incentive',
                'heading' => 'REQUEST FOR INCENTIVE OF RESEARCH PRESENTATION',
                'description' => 'Request for incentive of a research presentation (oral, poster or creative work).',
                'research_link' => null,
                'applicant' => true,
                'checks_card' => ['title' => 'Attachments', 'hint' => 'Tick each attachment submitted with this request. Requests with incomplete attachments are not processed.'],
                'fields' => [
                    'title' => ['label' => 'Title of Paper', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'wpu_coauthors' => ['label' => 'Name of WPU Co-author(s), if applicable', 'type' => 'textarea'],
                    'contributions' => $contributions,
                    'presentation_type' => ['label' => 'Type of Presentation', 'type' => 'radio', 'required' => true, 'options' => [
                        'oral' => 'Oral Presentation', 'poster' => 'Poster Presentation', 'creative' => 'Creative Works',
                    ]],
                    'conference_title' => ['label' => 'Title of Conference/Seminar', 'type' => 'text', 'required' => true],
                    'sponsoring_agency' => ['label' => 'Sponsoring Agency/Organizer', 'type' => 'text', 'required' => true],
                    'partner_institution' => ['label' => 'Partner College/University', 'type' => 'text'],
                    'date_presented' => ['label' => 'Date Presented', 'type' => 'date', 'required' => true],
                    'venue' => ['label' => 'Venue', 'type' => 'text', 'required' => true],
                    'attachments' => ['label' => 'Attachments', 'type' => 'checks', 'required' => true, 'options' => [
                        'abstract' => 'Copy of the abstract (oral or poster)',
                        'invitation' => 'Invitation to the presentation (oral or poster)',
                        'program' => 'Program of the conference (oral or poster)',
                        'certificate' => 'Certificate of recognition/participation (oral or poster)',
                        'travel_order' => 'Office order/travel order (oral or poster)',
                        'picture' => 'Picture while presenting (oral or poster)',
                        'cw_description' => 'Description, artist statement, or abstract of the presented work (creative works)',
                        'cw_documentation' => 'Invitation, program, or documentation of the presentation or exhibition (creative works)',
                        'cw_awards' => 'Awards or certificates recognizing the creative work (creative works)',
                    ]],
                ],
                'template_labels' => [
                    'presentation_type' => ['oral' => 'Oral Presentation', 'poster' => 'Poster Presentation', 'creative' => 'Creative Works'],
                    'attachments' => [
                        'abstract' => 'Copy of the Abstract', 'invitation' => 'Invitation to the Presentation', 'program' => 'Program of the Conference',
                        'certificate' => 'Certificate of recognition', 'travel_order' => 'Office Order', 'picture' => 'Picture while presenting',
                        'cw_description' => 'Description, artist statement', 'cw_documentation' => 'Invitation, program, or documentation', 'cw_awards' => 'Awards or certificates',
                    ],
                ],
                'reviews' => [
                    AccessControl::COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Reported in a quarterly report', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'reported_quarter' => ['label' => 'Quarter and year reported', 'type' => 'text', 'hint' => 'Required when reported. Enter the quarter and year, as in Q1 2027.'],
                        'level' => ['label' => 'Level of conference', 'type' => 'radio', 'required' => true, 'options' => self::CONFERENCE_LEVELS],
                    ],
                    AccessControl::RIDE_COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Verified as reported', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'level' => ['label' => 'Level of conference', 'type' => 'radio', 'required' => true, 'options' => self::CONFERENCE_LEVELS],
                        'amount' => ['label' => 'Amount of Incentive (PhP)', 'type' => 'amount', 'required' => true],
                        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
                    ],
                ],
            ],
            'ipr_incentive' => [
                'label' => 'IPR Incentive',
                'slug' => 'ipr-incentive',
                'code' => 'WPU-QSF-RIDE-RDO-03',
                'group' => 'incentive',
                'heading' => 'REQUEST FOR INCENTIVE OF IPR REGISTRATION',
                'description' => 'Request for incentive of an intellectual property registration.',
                'research_link' => null,
                'applicant' => true,
                'checks_card' => ['title' => 'Attachments', 'hint' => 'Tick each attachment submitted with this request. Requests with incomplete attachments are not processed.'],
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'wpu_coauthors' => ['label' => 'Name of WPU Co-author(s)/maker(s), if applicable', 'type' => 'textarea'],
                    'contributions' => $contributions,
                    'ip_type' => ['label' => 'Type of IP Application', 'type' => 'radio', 'required' => true, 'options' => [
                        'industrial_design' => 'Industrial Design', 'utility_model' => 'Utility Model', 'invention' => 'Invention',
                    ]],
                    'registration_no' => ['label' => 'Registration No.', 'type' => 'text', 'required' => true],
                    'date_filed' => ['label' => 'Date Filed', 'type' => 'date', 'required' => true],
                    'date_issued' => ['label' => 'Date of Issue/Publication', 'type' => 'date', 'required' => true],
                    'attachments' => ['label' => 'Attachments', 'type' => 'checks', 'required' => true, 'options' => [
                        'application_form' => 'Copy of the application form',
                        'certificate' => 'Original certificate of registration',
                    ]],
                ],
                'template_labels' => [
                    'ip_type' => ['industrial_design' => 'Industrial Design', 'utility_model' => 'Utility Model', 'invention' => 'Invention'],
                    'attachments' => ['application_form' => 'Copy of Application Form', 'certificate' => 'Original Certificate'],
                ],
                'reviews' => [
                    AccessControl::COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Reported in a quarterly report', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'reported_quarter' => ['label' => 'Quarter and year reported', 'type' => 'text', 'hint' => 'Required when reported. Enter the quarter and year, as in Q1 2027.'],
                    ],
                    AccessControl::RIDE_COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Verified as reported', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'scheme' => ['label' => 'Incentive Scheme', 'type' => 'radio', 'required' => true, 'options' => [
                            'acceptance' => 'Upon Acceptance', 'publication' => 'Upon Publication', 'issuance' => 'Upon Issuance of Registration',
                        ]],
                        'amount' => ['label' => 'Amount of Incentive (PhP)', 'type' => 'amount', 'required' => true],
                        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
                    ],
                ],
            ],
            'publication_incentive' => [
                'label' => 'Publication Incentive',
                'slug' => 'publication-incentive',
                'code' => 'WPU-QSF-RIDE-RDO-04',
                'group' => 'incentive',
                'heading' => 'REQUEST FOR INCENTIVE OF RESEARCH PUBLICATION',
                'description' => 'Request for incentive of a research publication (journal article, book, research notes or creative work).',
                'research_link' => null,
                'applicant' => true,
                'checks_card' => ['title' => 'Attachments', 'hint' => 'Tick each attachment submitted with this request. Requests with incomplete attachments are not processed.'],
                'fields' => [
                    'title' => ['label' => 'Title of Published Research', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'wpu_coauthors' => ['label' => 'Name of WPU Co-author(s), if applicable', 'type' => 'textarea'],
                    'contributions' => $contributions,
                    'publication_type' => ['label' => 'Type of Publication', 'type' => 'radio', 'required' => true, 'options' => [
                        'journal_article' => 'Journal Article', 'book' => 'Book', 'research_notes' => 'Research Notes', 'creative' => 'Creative Works',
                    ]],
                    'journal_title' => ['label' => 'Title of Journal/Book', 'type' => 'text', 'required' => true],
                    'volume_issue_pages' => ['label' => 'Volume/Issue/Page Number', 'type' => 'text', 'required' => true],
                    'publisher_editor' => ['label' => 'Publisher and Editor-in-Chief', 'type' => 'text', 'required' => true],
                    'issn_isbn' => ['label' => 'ISSN/ISBN', 'type' => 'text', 'required' => true],
                    'date_published' => ['label' => 'Date Published', 'type' => 'date', 'required' => true],
                    'indexing' => ['label' => 'Indexing', 'type' => 'text', 'required' => true],
                    'attachments' => ['label' => 'Attachments', 'type' => 'checks', 'required' => true, 'options' => [
                        'paper_copy' => 'Printed and electronic copy of the research paper/book',
                        'acceptance' => 'Letter of acceptance',
                        'review_evidence' => 'Evidence of review (publication editor-in-chief)',
                        'journal_cover' => 'Cover of the journal/book where the research paper was published',
                        'cw_full_work' => 'Copy of the full work (creative works, in print)',
                        'cw_cover' => 'Publication cover (creative works, in print)',
                        'cw_contents' => 'Table of contents (creative works, in print)',
                        'cw_catalog' => 'Catalog reflecting the title of the work and faculty creator (creative works, in print)',
                        'cw_online_copy' => 'Copy or screenshot of the full work (creative works, online)',
                        'cw_source_link' => 'Source link (creative works, online)',
                    ]],
                ],
                'template_labels' => [
                    'publication_type' => ['journal_article' => 'Journal Article', 'book' => 'Book', 'research_notes' => 'Research Notes', 'creative' => 'Creative Works'],
                    'attachments' => [
                        'paper_copy' => 'Printed and Electronic copy', 'acceptance' => 'Letter of Acceptance', 'review_evidence' => 'Evidence of Review',
                        'journal_cover' => 'Cover of the journal', 'cw_full_work' => 'Copy of the full work', 'cw_cover' => 'Publication cover',
                        'cw_contents' => 'Table of contents', 'cw_catalog' => 'Catalog reflecting', 'cw_online_copy' => 'Copy or screenshot', 'cw_source_link' => 'Source link',
                    ],
                ],
                'reviews' => [
                    AccessControl::COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Reported in a quarterly report', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'reported_quarter' => ['label' => 'Quarter and year reported', 'type' => 'text', 'hint' => 'Required when reported. Enter the quarter and year, as in Q1 2027.'],
                    ],
                    AccessControl::RIDE_COORDINATOR_RESEARCH => [
                        'reported' => ['label' => 'Verified as reported', 'type' => 'radio', 'required' => true, 'options' => ['reported' => 'Reported', 'not_reported' => 'Not Reported']],
                        'classification' => ['label' => 'Classification', 'type' => 'checks', 'required' => true, 'options' => [
                            'international_peer_reviewed' => 'International Peer Reviewed Journal', 'scopus_isi' => 'Scopus/ISI (Clarivate Analytics)',
                            'national' => 'National', 'ched_accredited' => 'CHED Accredited',
                        ]],
                        'amount' => ['label' => 'Amount of Incentive (PhP)', 'type' => 'amount', 'required' => true],
                        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $formType): ?array
    {
        return self::all()[$formType] ?? null;
    }

    public static function fromSlug(string $slug): ?string
    {
        foreach (self::all() as $formType => $definition) {
            if ($definition['slug'] === $slug) {
                return $formType;
            }
        }

        return null;
    }

    /** The request form type of a saved proposal, or null when it is not a request form. */
    public static function typeOf(?array $proposal): ?string
    {
        $formType = proposal_form_type($proposal);

        return isset(self::all()[$formType]) ? $formType : null;
    }

    /**
     * Approved Research Proposal Applications the user leads or co-authors, newest first. The project code
     * minted on final approval is the RIDE Control No. the request forms ask for.
     *
     * @return list<array{id: int, control_no: string, title: string, lead: string, coauthors: string, funding_source: string, status: string}>
     */
    public static function approvedResearchFor(int $userId): array
    {
        $options = [];
        foreach (Proposal::forUser($userId) as $proposal) {
            $isResearchApplication = proposal_is_research_application($proposal)
                || (proposal_form_type($proposal) === '' && (string) ($proposal['project_type'] ?? '') === 'research');
            if (!$isResearchApplication
                || !in_array((string) ($proposal['status'] ?? ''), ['ongoing', 'approved', 'completed'], true)
                || trim((string) ($proposal['project_code'] ?? '')) === '') {
                continue;
            }

            $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
            $summary = is_array($summary) ? $summary : [];
            $coauthors = [];
            foreach (is_array($summary['coauthors'] ?? null) ? $summary['coauthors'] : [] as $coauthor) {
                $name = trim((string) ($coauthor['first_name'] ?? '') . ' ' . (string) ($coauthor['last_name'] ?? ''));
                if (is_array($coauthor) && $name !== '') {
                    $coauthors[] = $name;
                }
            }

            $options[] = [
                'id' => (int) $proposal['id'],
                'control_no' => (string) $proposal['project_code'],
                'title' => (string) $proposal['title'],
                'lead' => trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? '')),
                'coauthors' => implode(', ', $coauthors),
                'funding_source' => (string) ($proposal['funding_source'] ?? ''),
                'status' => (string) $proposal['status'] === 'completed' ? 'completed' : 'ongoing',
            ];
        }

        return $options;
    }

    /**
     * Review fields a reviewer fills when acting on a step (the incentive forms' sections (5) and (7)).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function reviewFields(string $formType, string $step): array
    {
        return self::get($formType)['reviews'][$step] ?? [];
    }

    /**
     * Validated review values from the posted review[...] inputs, and the errors.
     *
     * @param array<string, array<string, mixed>> $fields
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    public static function validatedReview(array $fields, mixed $posted): array
    {
        $posted = is_array($posted) ? $posted : [];
        $values = [];
        $errors = [];
        foreach ($fields as $name => $field) {
            $raw = $posted[$name] ?? null;
            $value = match ($field['type']) {
                'radio' => array_key_exists(strtolower(trim((string) $raw)), $field['options']) ? strtolower(trim((string) $raw)) : '',
                'checks' => ResearchFormOptions::picked(is_array($raw) ? $raw : [], $field['options']),
                'amount' => preg_match('/^\d+(\.\d{1,2})?$/', str_replace(',', '', trim((string) $raw))) === 1 ? str_replace(',', '', trim((string) $raw)) : '',
                default => mb_substr(trim((string) $raw), 0, 2000),
            };
            if (!empty($field['required']) && ($value === '' || $value === [])) {
                $errors[] = $field['label'] . ' is required.';
            }
            $values[$name] = $value;
        }
        if (($values['reported'] ?? '') === 'reported' && array_key_exists('reported_quarter', $fields) && ($values['reported_quarter'] ?? '') === '') {
            $errors[] = 'Quarter and year reported is required when the output was reported.';
        }

        return [$values, $errors];
    }

    /** Field types whose value is a list rather than a string. */
    public static function isListType(string $type): bool
    {
        return in_array($type, ['checks', 'rows', 'timeline'], true);
    }
}
