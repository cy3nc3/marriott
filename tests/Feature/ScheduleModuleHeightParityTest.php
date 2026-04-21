<?php

use Illuminate\Support\Facades\File;

test('student, teacher, and parent schedule pages use the same hour height as admin schedule builder', function () {
    $adminScheduleBuilderPage = base_path('resources/js/pages/admin/schedule-builder/index.tsx');
    $targetPages = [
        base_path('resources/js/pages/student/schedule/index.tsx'),
        base_path('resources/js/pages/teacher/schedule/index.tsx'),
        base_path('resources/js/pages/parent/schedule/index.tsx'),
    ];

    preg_match('/const HOUR_HEIGHT = (\d+);/', File::get($adminScheduleBuilderPage), $adminMatches);

    expect($adminMatches)->toHaveCount(2);

    $adminHourHeight = (int) $adminMatches[1];

    foreach ($targetPages as $pagePath) {
        preg_match('/const HOUR_HEIGHT = (\d+);/', File::get($pagePath), $pageMatches);

        expect($pageMatches)->toHaveCount(2);
        expect((int) $pageMatches[1])->toBe($adminHourHeight);
    }
});
