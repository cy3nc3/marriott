<?php

namespace App\Services\Imports;

class MappingResolver
{
    public function __construct(private HeaderNormalizer $headerNormalizer) {}

    /**
     * @param  array<int, string>  $sourceHeaders
     * @return array<string, string>
     */
    public function resolve(array $sourceHeaders): array
    {
        $mapping = [];

        foreach ($sourceHeaders as $sourceHeader) {
            $trimmedHeader = trim($sourceHeader);
            if ($trimmedHeader === '') {
                continue;
            }

            $canonicalField = $this->headerNormalizer->canonicalize($trimmedHeader);
            if ($canonicalField === '') {
                continue;
            }

            if (! array_key_exists($canonicalField, $mapping)) {
                $mapping[$canonicalField] = $trimmedHeader;
            }
        }

        return $mapping;
    }

    /**
     * @param  array<string, string>  $resolvedMapping
     * @param  array<int, string>  $requiredFields
     * @return array<int, string>
     */
    public function missingRequiredFields(array $resolvedMapping, array $requiredFields): array
    {
        $missing = [];

        foreach ($requiredFields as $requiredField) {
            $canonicalField = $this->headerNormalizer->canonicalize($requiredField);

            if ($canonicalField === '') {
                continue;
            }

            if (! array_key_exists($canonicalField, $resolvedMapping)) {
                $missing[] = $canonicalField;
            }
        }

        return array_values(array_unique($missing));
    }
}
