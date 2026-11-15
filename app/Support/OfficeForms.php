<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FormTemplate;

/**
 * The office form behind each RIDE form: its code and name. The blank form itself is uploaded in the portal
 * (Admin > Form Templates, listed under Downloadable Forms) with the code in its title, and each form page
 * links to it.
 */
final class OfficeForms
{
    /** @var array<string, array{code: string, title: string, office: string}> form type => office form */
    private const FORMS = [
        'research' => ['code' => 'WPU-QSF-RIDE-RDO-20', 'title' => 'Research Proposal Application', 'office' => 'rdo'],
        'critical_activities_request' => ['code' => 'WPU-QSF-RIDE-RDO-23', 'title' => 'Request for Critical Activities', 'office' => 'rdo'],
        'research_program_proposal' => ['code' => 'WPU-QSF-RIDE-RDO-29', 'title' => 'Research Program/Project/Study/Center Proposal', 'office' => 'rdo'],
        'external_funding_proposal' => ['code' => 'WPU-QSF-RIDE-RDO-30', 'title' => 'Proposal for External Funding', 'office' => 'rdo'],
        'paper_presentation' => ['code' => 'WPU-QSF-RIDE-RDO-31', 'title' => 'Paper Presentation Request', 'office' => 'rdo'],
        'manuscript' => ['code' => 'WPU-QSF-RIDE-RDO-32', 'title' => 'Manuscript Writing for Publication', 'office' => 'rdo'],
        'completed_researches' => ['code' => 'WPU-QSF-RIDE-RDO-05', 'title' => 'Completed Researches', 'office' => 'rdo'],
        'ongoing_researches' => ['code' => 'WPU-QSF-RIDE-RDO-06', 'title' => 'Ongoing Researches', 'office' => 'rdo'],
        'research_output_published' => ['code' => 'WPU-QSF-RIDE-RDO-07', 'title' => 'Research Output Published', 'office' => 'rdo'],
        'research_output_presented' => ['code' => 'WPU-QSF-RIDE-RDO-08', 'title' => 'Research Output Presented', 'office' => 'rdo'],
        'commercialized' => ['code' => 'WPU-QSF-RIDE-RDO-09', 'title' => 'Commercialized Research', 'office' => 'rdo'],
        'resulted_in_extension' => ['code' => 'WPU-QSF-RIDE-RDO-10', 'title' => 'Research Resulted in Extension', 'office' => 'rdo'],
        'journal_citation' => ['code' => 'WPU-QSF-RIDE-RDO-11', 'title' => 'Journal Citation', 'office' => 'rdo'],
        'book_citation' => ['code' => 'WPU-QSF-RIDE-RDO-12', 'title' => 'Book Citation', 'office' => 'rdo'],
        'inventions_um_copyrights' => ['code' => 'WPU-QSF-RIDE-RDO-13', 'title' => 'Inventions, Utility Models and Copyrights', 'office' => 'rdo'],
        'linkages' => ['code' => 'WPU-QSF-RIDE-RDO-14', 'title' => 'Research Linkages', 'office' => 'rdo'],
        'progress_report' => ['code' => 'WPU-QSF-RDE-VPRDE-18', 'title' => 'Progress Report', 'office' => 'rdo'],
        'terminal_report' => ['code' => 'WPU-QSF-RDE-VPRDE-21', 'title' => 'Terminal Report Format', 'office' => 'rdo'],
        'terminal_report_assessment_form' => ['code' => 'WPU-QSF-RDE-VPRDE-22', 'title' => 'Terminal Report Assessment Form', 'office' => 'rdo'],
        'extension_program_proposal' => ['code' => 'WPU-QSF-RIDE-ESO-03', 'title' => 'Extension Program/Project Proposal', 'office' => 'eso'],
        'trainings_conducted' => ['code' => 'WPU-QSF-RIDE-ESO-06', 'title' => 'Trainings Conducted', 'office' => 'eso'],
        'technical_advisory' => ['code' => 'WPU-QSF-RIDE-ESO-07', 'title' => 'Technical Advisory', 'office' => 'eso'],
        'extension_linkages' => ['code' => 'WPU-QSF-RIDE-ESO-08', 'title' => 'Extension Linkages', 'office' => 'eso'],
        'outreach_activities' => ['code' => 'WPU-QSF-RIDE-ESO-09', 'title' => 'Outreach Activities', 'office' => 'eso'],
        'technology_adoption' => ['code' => 'WPU-QSF-RIDE-ESO-10', 'title' => 'Technology Adoption', 'office' => 'eso'],
    ];

    /** @return array{code: string, title: string, office: string}|null */
    public static function get(string $formType): ?array
    {
        return self::FORMS[$formType] ?? null;
    }

    /** @return array<string, array{code: string, title: string, office: string}> */
    public static function all(): array
    {
        return self::FORMS;
    }

    /** The uploaded blank form of a form type, when one is in Downloadable Forms. */
    public static function template(string $formType): ?array
    {
        $form = self::get($formType);
        if ($form === null) {
            return null;
        }

        return FormTemplate::forCode(
            $form['code'],
            $form['office'] === 'rdo' ? FormTemplate::AUDIENCE_RESEARCH : FormTemplate::AUDIENCE_EXTENSION
        );
    }
}
