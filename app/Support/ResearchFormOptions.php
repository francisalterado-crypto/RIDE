<?php

declare(strict_types=1);

namespace App\Support;

/** Option lists shared by the RDO forms (Research Proposal Application and the request forms). */
final class ResearchFormOptions
{
    public const SDGS = [
        'sdg_1' => 'SDG 1: No Poverty',
        'sdg_2' => 'SDG 2: Zero Hunger',
        'sdg_3' => 'SDG 3: Good Health and Well-Being',
        'sdg_4' => 'SDG 4: Quality Education',
        'sdg_5' => 'SDG 5: Gender Equality',
        'sdg_6' => 'SDG 6: Clean Water and Sanitation',
        'sdg_7' => 'SDG 7: Affordable and Clean Energy',
        'sdg_8' => 'SDG 8: Decent Work and Economic Growth',
        'sdg_9' => 'SDG 9: Industry, Innovation and Infrastructure',
        'sdg_10' => 'SDG 10: Reduced Inequalities',
        'sdg_11' => 'SDG 11: Sustainable Cities and Communities',
        'sdg_12' => 'SDG 12: Responsible Consumption and Production',
        'sdg_13' => 'SDG 13: Climate Action',
        'sdg_14' => 'SDG 14: Life Below Water',
        'sdg_15' => 'SDG 15: Life on Land',
        'sdg_16' => 'SDG 16: Peace, Justice and Strong Institutions',
        'sdg_17' => 'SDG 17: Partnerships for the Goals',
    ];

    public const CHED_ACHIEVE = [
        'access' => 'A: Access',
        'connectivity' => 'C: Connectivity',
        'human_capital' => 'H: Human Capital',
        'innovation' => 'I: Innovation',
        'excellence' => 'E: Excellence',
        'value' => 'V: Value for Money',
        'engagement' => 'E: Engagement',
    ];

    public const FUNDING_SUPPORT = [
        'external' => 'Externally Funded',
        'inhouse' => 'In-house Funded',
        'official_time' => 'Official Time',
    ];

    /**
     * Keeps only known option keys. POST text is title-cased globally, so keys are compared in lower case.
     *
     * @param array<string, string> $options
     * @return list<string>
     */
    public static function picked(mixed $values, array $options): array
    {
        if (!is_array($values)) {
            return [];
        }
        $picked = [];
        foreach ($values as $value) {
            $key = strtolower(trim((string) $value));
            if (isset($options[$key]) && !in_array($key, $picked, true)) {
                $picked[] = $key;
            }
        }

        return $picked;
    }

    /**
     * Labels of the picked keys, in the options' order.
     *
     * @param array<string, string> $options
     * @return list<string>
     */
    public static function labels(mixed $values, array $options): array
    {
        $picked = is_array($values) ? array_map('strval', $values) : [];

        return array_values(array_intersect_key($options, array_flip($picked)));
    }
}
