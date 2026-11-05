<?php

namespace App\Services;

/** Conservative classification of an explicitly selected map feature, never a nearby GPS point. */
class InstitutionMapClassification
{
    public static function detect(array $feature): array
    {
        $tags = $feature['extratags'] ?? [];
        $tag = static fn (string $key): string => strtolower(trim((string) ($tags[$key] ?? '')));
        $amenity = $tag('amenity') ?: (($feature['category'] ?? '') === 'amenity' ? ($feature['feature_type'] ?? '') : '');
        $school = $amenity === 'school' || $tag('building') === 'school';
        $private = in_array($tag('operator:type'), ['private', 'religious'], true)
            || in_array($tag('ownership'), ['private', 'religious'], true)
            || $tag('school:government') === 'no';
        if ($school && $private) {
            return ['type' => null, 'excluded' => true, 'message' => 'This map record identifies a private school. Only public schools and barangay halls in Lipa City can be added.'];
        }
        $public = in_array($tag('operator:type'), ['public', 'government'], true)
            || in_array($tag('ownership'), ['public', 'government'], true)
            || $tag('school:government') === 'yes';
        if ($school && $public) {
            return ['type' => 'School', 'excluded' => false, 'message' => 'Automatically detected institution type: Public School. Please confirm this is correct.'];
        }
        $hall = $amenity === 'townhall' || $tag('office') === 'government'
            || (($feature['category'] ?? '') === 'office' && ($feature['feature_type'] ?? '') === 'government');
        if ($hall && ($tag('government') === 'barangay' || $tag('townhall:type') === 'barangay')) {
            return ['type' => 'Barangay Hall', 'excluded' => false, 'message' => 'Automatically detected institution type: Barangay Hall. Please confirm this is correct.'];
        }
        return ['type' => null, 'excluded' => false, 'message' => 'Map data cannot confirm the institution type. Select Public School or Barangay Hall after checking the institution.'];
    }
}
