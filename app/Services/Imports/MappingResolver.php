<?php

namespace App\Services\Imports;

class MappingResolver
{
    public function __construct(private HeaderNormalizer $headerNormalizer) {}

    /**
     * @param  array<int, string>  $sourceHeaders
     * @return array{mapping: array<string, string>, collisions: array<string, array<int, array{index: int, header: string}>>}
     */
    public function resolve(array $sourceHeaders): array
    {
        $mapping = [];
        $mappingSources = [];
        $collisions = [];

        foreach ($sourceHeaders as $index => $sourceHeader) {
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
                $mappingSources[$canonicalField] = [
                    'index' => $index,
                    'header' => $trimmedHeader,
                ];

                continue;
            }

            if (! array_key_exists($canonicalField, $collisions)) {
                $collisions[$canonicalField] = [$mappingSources[$canonicalField]];
            }

            $collisions[$canonicalField][] = [
                'index' => $index,
                'header' => $trimmedHeader,
            ];
        }

        return [
            'mapping' => $mapping,
            'collisions' => $collisions,
        ];
    }

    /**
     * @param  array<string, string>|array{mapping?: array<string, string>, collisions?: array<string, array<int, string>>}  $resolvedMapping
     * @param  array<int, string>  $requiredFields
     * @return array<int, string>
     */
    public function missingRequiredFields(array $resolvedMapping, array $requiredFields): array
    {
        $mapping = $resolvedMapping['mapping'] ?? $resolvedMapping;
        if (! is_array($mapping)) {
            $mapping = [];
        }

        $missing = [];

        foreach ($requiredFields as $requiredField) {
            $canonicalField = $this->headerNormalizer->canonicalize($requiredField);

            if ($canonicalField === '') {
                continue;
            }

            if (! array_key_exists($canonicalField, $mapping)) {
                $missing[] = $canonicalField;
            }
        }

        return array_values(array_unique($missing));
    }
}
