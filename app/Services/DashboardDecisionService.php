<?php

namespace App\Services;

class DashboardDecisionService
{
    /**
     * @param  array<int, array{bucket: string, amount: float|int}>  $overdueAgingRows
     * @param  array<int, array{month: string, collected: float|int}>  $monthlyCollectionTotals
     * @param  array<int, array{bucket: string, amount: float|int}>  $receivableRiskCompositionRows
     * @param  array<int, array{month: string, efficiency: float|int}>  $monthlyStabilityRows
     * @return array<int, array<string, mixed>>
     */
    public function financeTrends(
        array $overdueAgingRows,
        array $monthlyCollectionTotals,
        array $receivableRiskCompositionRows,
        array $monthlyStabilityRows,
    ): array {
        return [
            [
                'id' => 'receivable-risk-composition',
                'label' => 'Unpaid Balance Mix',
                'summary' => 'Shows how much unpaid balance is already overdue',
                'display' => 'pie',
                'points' => collect($receivableRiskCompositionRows)->map(fn (array $row): array => [
                    'label' => (string) $row['bucket'],
                    'value' => round((float) $row['amount'], 2),
                ])->all(),
                'chart' => [
                    'x_key' => 'bucket',
                    'rows' => $receivableRiskCompositionRows,
                    'series' => [[
                        'key' => 'amount',
                        'label' => 'Amount',
                    ]],
                ],
            ],
            [
                'id' => 'revenue-stability-outlook',
                'label' => 'Collection Pattern',
                'summary' => 'Six-month collection percentage pattern',
                'display' => 'line',
                'points' => collect($monthlyStabilityRows)->map(fn (array $row): array => [
                    'label' => (string) $row['month'],
                    'value' => $row['efficiency'],
                ])->all(),
                'chart' => [
                    'x_key' => 'month',
                    'rows' => collect($monthlyStabilityRows)->map(fn (array $row): array => [
                        'month' => (string) $row['month'],
                        'efficiency' => $row['efficiency'],
                    ])->all(),
                    'series' => [[
                        'key' => 'efficiency',
                        'label' => 'Efficiency %',
                    ]],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{section: string, enrolled: int, capacity: int, utilization: float|int}>  $sectionCapacityRows
     * @param  array<int, array{section: string, items: int}>  $sectionQueueRows
     * @param  array<int, array{transition: string, rate: float|int, re_enrolled?: int, did_not_enroll?: int, new_or_transferee?: int}>  $continuityRows
     * @return array<int, array<string, mixed>>
     */
    public function registrarTrends(
        array $sectionCapacityRows,
        array $sectionQueueRows,
        int $requirementsCompleteCount,
        int $requirementsMissingCount,
        array $continuityRows,
    ): array {
        return [
            [
                'id' => 'section-capacity-utilization',
                'label' => 'Section Capacity',
                'summary' => 'Sections nearing the target number of students',
                'display' => 'bar',
                'points' => collect($sectionCapacityRows)->map(fn (array $row): array => [
                    'label' => (string) $row['section'],
                    'value' => $row['utilization'],
                ])->all(),
                'chart' => [
                    'x_key' => 'section',
                    'rows' => collect($sectionCapacityRows)->take(12)->all(),
                    'series' => [[
                        'key' => 'utilization',
                        'label' => 'Utilization %',
                    ]],
                ],
            ],
            [
                'id' => 'section-queue-hotspots',
                'label' => 'Enrollment Queue by Section',
                'summary' => 'Sections with the most enrollment records waiting for payment',
                'display' => 'bar',
                'points' => collect($sectionQueueRows)->map(fn (array $row): array => [
                    'label' => (string) $row['section'],
                    'value' => $row['items'],
                ])->all(),
                'chart' => [
                    'x_key' => 'section',
                    'rows' => $sectionQueueRows,
                    'series' => [[
                        'key' => 'items',
                        'label' => 'Queue Items',
                    ]],
                ],
            ],
            [
                'id' => 'requirements-readiness',
                'label' => 'Missing Requirements',
                'summary' => 'Students with complete vs missing enrollment requirements',
                'display' => 'pie',
                'points' => [
                    ['label' => 'Complete', 'value' => $requirementsCompleteCount],
                    ['label' => 'Missing', 'value' => $requirementsMissingCount],
                ],
                'chart' => [
                    'x_key' => 'status',
                    'rows' => [
                        ['status' => 'Complete', 'students' => $requirementsCompleteCount],
                        ['status' => 'Missing', 'students' => $requirementsMissingCount],
                    ],
                    'series' => [[
                        'key' => 'students',
                        'label' => 'Students',
                    ]],
                ],
            ],
            [
                'id' => 'cohort-continuity-trend',
                'label' => 'Enrollment Continuity',
                'summary' => 'Compares re-enrolled, non-returning, and new/transferee students',
                'display' => 'bar',
                'points' => collect($continuityRows)->map(fn (array $row): array => [
                    'label' => (string) $row['transition'],
                    'value' => $row['re_enrolled'] ?? 0,
                ])->all(),
                'chart' => [
                    'x_key' => 'transition',
                    'rows' => $continuityRows,
                    'series' => [
                        [
                            'key' => 're_enrolled',
                            'label' => 'Re-Enrolled',
                        ],
                        [
                            'key' => 'did_not_enroll',
                            'label' => 'Did Not Enroll',
                        ],
                        [
                            'key' => 'new_or_transferee',
                            'label' => 'New/Transferee',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, value: int}>  $auditTrendPoints
     * @param  array<int, array{type: string, events: int}>  $auditRiskPatternRows
     * @return array<int, array<string, mixed>>
     */
    public function superAdminTrends(
        array $auditTrendPoints,
        array $auditRiskPatternRows,
    ): array {
        return [
            [
                'id' => 'admin-actions-last-7-days',
                'label' => 'Actions Last 7 Days',
                'summary' => 'Administrative activity recorded each day',
                'display' => 'line',
                'points' => $auditTrendPoints,
                'chart' => [
                    'x_key' => 'day',
                    'rows' => collect($auditTrendPoints)->map(fn (array $point): array => [
                        'day' => $point['label'],
                        'actions' => $point['value'],
                    ])->all(),
                    'series' => [[
                        'key' => 'actions',
                        'label' => 'Actions',
                    ]],
                ],
            ],
            [
                'id' => 'audit-risk-pattern',
                'label' => 'Action Types Today',
                'summary' => 'Important administrative actions compared with other logged actions',
                'display' => 'pie',
                'points' => collect($auditRiskPatternRows)->map(fn (array $point): array => [
                    'label' => (string) $point['type'],
                    'value' => $point['events'],
                ])->values()->all(),
                'chart' => [
                    'x_key' => 'type',
                    'rows' => $auditRiskPatternRows,
                    'series' => [[
                        'key' => 'events',
                        'label' => 'Events',
                    ]],
                ],
            ],
        ];
    }

    public function financeOverdueUrgency(int $daysOverdue): string
    {
        if ($daysOverdue >= 15) {
            return 'Critical';
        }

        if ($daysOverdue >= 7) {
            return 'High';
        }

        return 'Medium';
    }

    public function financeOverduePriorityScore(float $balance, int $daysOverdue): int
    {
        return min((int) round(($balance / 1000) + ($daysOverdue * 2) + 40), 99);
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @return array<int, array{id: string, label: string, href: string}>
     */
    public function prioritizeFinanceActionLinks(
        array $actionLinks,
        float $overdueConcentration,
        int $criticalQueueCount,
        float $monthlyCollectionEfficiency,
        int $cashierQueueCount = 0,
    ): array {
        if ($cashierQueueCount >= 20) {
            return $this->reorderActionLinks($actionLinks, [
                'open-cashier-panel',
                'review-overdue-accounts',
                'open-student-ledgers',
                'open-due-reminder-scheduling',
                'open-daily-reports',
            ]);
        }

        if ($overdueConcentration >= 35 || $criticalQueueCount > 0) {
            return $this->reorderActionLinks($actionLinks, [
                'review-overdue-accounts',
                'open-due-reminder-scheduling',
                'open-student-ledgers',
                'open-cashier-panel',
                'open-daily-reports',
            ]);
        }

        if ($monthlyCollectionEfficiency < 85) {
            return $this->reorderActionLinks($actionLinks, [
                'open-due-reminder-scheduling',
                'open-daily-reports',
                'open-student-ledgers',
                'review-overdue-accounts',
                'open-cashier-panel',
            ]);
        }

        return $actionLinks;
    }

    /**
     * @return array<int, array{id: string, title: string, impact: string, urgency: string, priority_score: int, reason: string, href: string}>
     */
    public function financeOverdueActionQueue(
        int $criticalQueueCount,
        int $highQueueCount,
        int $mediumQueueCount,
        string $ledgerHref,
        int $cashierQueueCount = 0,
        ?string $cashierHref = null,
    ): array {
        return collect([
            $cashierQueueCount >= 20 && $cashierHref ? [
                'id' => 'cashier-queue',
                'title' => 'Cashier queue is busy',
                'impact' => "{$cashierQueueCount} enrollment payment(s) waiting",
                'urgency' => 'Today',
                'priority_score' => 98,
                'reason' => 'Assign another cashier or process the oldest payment records first.',
                'href' => $cashierHref,
            ] : null,
            $criticalQueueCount > 0 ? [
                'id' => 'overdue-critical',
                'title' => 'Payments need follow-up',
                'impact' => "{$criticalQueueCount} account(s)",
                'urgency' => 'Today',
                'priority_score' => 95,
                'reason' => 'Accounts with 15+ days overdue need immediate intervention.',
                'href' => $ledgerHref,
            ] : null,
            $highQueueCount > 0 ? [
                'id' => 'overdue-high',
                'title' => 'Payments need reminders',
                'impact' => "{$highQueueCount} account(s)",
                'urgency' => 'Within 24h',
                'priority_score' => 80,
                'reason' => 'Accounts with 7-14 days overdue should receive follow-up reminders.',
                'href' => $ledgerHref,
            ] : null,
            $mediumQueueCount > 0 ? [
                'id' => 'overdue-medium',
                'title' => 'New overdue dues',
                'impact' => "{$mediumQueueCount} account(s)",
                'urgency' => 'This week',
                'priority_score' => 65,
                'reason' => 'Early-stage overdue accounts need preventive follow-up.',
                'href' => $ledgerHref,
            ] : null,
        ])->filter()->values()->all();
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @return array<int, array{id: string, label: string, href: string}>
     */
    public function prioritizeRegistrarActionLinks(
        array $actionLinks,
        int $requirementsMissingCount,
        float $requirementsComplianceRate,
        int $intakeQueuePressure,
    ): array {
        if ($requirementsMissingCount >= 20 && $requirementsComplianceRate < 85) {
            return $this->reorderActionLinks($actionLinks, [
                'open-missing-requirements',
                'open-enrollment-queue',
                'open-conditional-records',
            ]);
        }

        if ($intakeQueuePressure >= 25) {
            return $this->reorderActionLinks($actionLinks, [
                'open-enrollment-queue',
                'open-missing-requirements',
                'open-conditional-records',
            ]);
        }

        return $actionLinks;
    }

    public function superAdminAuditPriorityScore(string $action): int
    {
        return str_contains(strtolower($action), 'delete') ? 95 : 80;
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @return array<int, array{id: string, label: string, href: string}>
     */
    public function prioritizeSuperAdminActionLinks(
        array $actionLinks,
        ?int $backupAgeHours,
        int $riskAuditLogsToday,
    ): array {
        if (($backupAgeHours ?? 999) >= 24) {
            return $this->reorderActionLinks($actionLinks, [
                'view-audit-logs',
            ]);
        }

        if ($riskAuditLogsToday >= 4) {
            return $this->reorderActionLinks($actionLinks, [
                'view-audit-logs',
            ]);
        }

        return $actionLinks;
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    public function financeAlerts(
        float $monthlyCollectionEfficiency,
        float $overdueConcentration,
        float $todayCollection,
        int $cashierQueueCount = 0,
    ): array {
        $alerts = [];

        if ($monthlyCollectionEfficiency < 70) {
            $alerts[] = [
                'id' => 'collection-efficiency',
                'title' => 'Monthly collection is below target',
                'message' => "The school has collected {$monthlyCollectionEfficiency}% of dues expected this month.",
                'severity' => 'critical',
            ];
        } elseif ($monthlyCollectionEfficiency < 85) {
            $alerts[] = [
                'id' => 'collection-efficiency',
                'title' => 'Monthly collection needs follow-up',
                'message' => "The school has collected {$monthlyCollectionEfficiency}% of dues expected this month.",
                'severity' => 'warning',
            ];
        }

        if ($overdueConcentration >= 60) {
            $alerts[] = [
                'id' => 'overdue-concentration',
                'title' => 'Many dues need follow-up',
                'message' => "{$overdueConcentration}% of receivables are already overdue.",
                'severity' => 'critical',
            ];
        } elseif ($overdueConcentration >= 35) {
            $alerts[] = [
                'id' => 'overdue-concentration',
                'title' => 'Some dues need follow-up',
                'message' => "{$overdueConcentration}% of receivables are already overdue.",
                'severity' => 'warning',
            ];
        }

        if ($cashierQueueCount >= 20) {
            $alerts[] = [
                'id' => 'cashier-queue',
                'title' => 'Cashier queue is busy',
                'message' => "{$cashierQueueCount} enrollment payment(s) are waiting for cashier processing.",
                'severity' => $cashierQueueCount >= 40 ? 'critical' : 'warning',
            ];
        }

        if ($todayCollection <= 0) {
            $alerts[] = [
                'id' => 'today-collection',
                'title' => 'No collections posted today',
                'message' => 'No payment transactions have been posted in the current day.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'finance-stable',
                'title' => 'Finance work is on track',
                'message' => 'Collections, payment follow-up, and cashier queue are within expected range.',
                'severity' => 'info',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    public function prioritizeFinanceKpis(
        array $kpis,
        float $overdueConcentration,
        int $criticalQueueCount,
        float $monthlyCollectionEfficiency,
        int $cashierQueueCount = 0,
    ): array {
        if ($cashierQueueCount >= 20) {
            return $this->sortKpisByPriority($kpis, [
                'finance-cashier-queue' => 0,
                'collection-efficiency' => 1,
                'overdue-recovery-target' => 2,
                'finance-revenue-stability' => 3,
            ]);
        }

        if ($overdueConcentration >= 35 || $criticalQueueCount > 0) {
            return $this->sortKpisByPriority($kpis, [
                'overdue-recovery-target' => 0,
                'collection-efficiency' => 1,
                'finance-cashier-queue' => 2,
                'finance-revenue-stability' => 3,
            ]);
        }

        if ($monthlyCollectionEfficiency < 85) {
            return $this->sortKpisByPriority($kpis, [
                'collection-efficiency' => 0,
                'overdue-recovery-target' => 1,
                'finance-cashier-queue' => 2,
                'finance-revenue-stability' => 3,
            ]);
        }

        return $kpis;
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    public function registrarAlerts(int $intakeQueuePressure): array
    {
        if ($intakeQueuePressure >= 60) {
            return [[
                'id' => 'queue-pressure',
                'title' => 'High enrollment queue pressure',
                'message' => "{$intakeQueuePressure} enrollment records are waiting for registrar processing.",
                'severity' => 'critical',
            ]];
        }

        if ($intakeQueuePressure >= 25) {
            return [[
                'id' => 'queue-pressure',
                'title' => 'Enrollment queue is rising',
                'message' => "{$intakeQueuePressure} enrollment records are waiting for registrar processing.",
                'severity' => 'warning',
            ]];
        }

        return [[
            'id' => 'registrar-stable',
            'title' => 'Registrar dashboard is stable',
            'message' => 'Enrollment throughput and requirements compliance are within target thresholds.',
            'severity' => 'info',
        ]];
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    public function prioritizeRegistrarKpis(
        array $kpis,
        int $requirementsMissingCount,
        float $requirementsComplianceRate,
        int $intakeQueuePressure,
    ): array {
        if ($requirementsMissingCount >= 20 && $requirementsComplianceRate < 85) {
            return $this->sortKpisByPriority($kpis, [
                'requirements-compliance' => 0,
                'intake-queue-age' => 1,
                'for-cashier-pipeline' => 2,
                'registrar-capacity-bottlenecks' => 3,
            ]);
        }

        if ($intakeQueuePressure >= 25) {
            return $this->sortKpisByPriority($kpis, [
                'intake-queue-age' => 0,
                'for-cashier-pipeline' => 1,
                'requirements-compliance' => 2,
                'registrar-capacity-bottlenecks' => 3,
            ]);
        }

        return $kpis;
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    public function adminAlerts(
        float $enrollmentYoYGrowth,
        int $sectionsWithoutAdviser,
        int $unassignedSubjects,
        int $sectionCapacityGap,
    ): array {
        $alerts = [];

        if ($enrollmentYoYGrowth <= -10) {
            $alerts[] = [
                'id' => 'enrollment-yoy',
                'title' => 'Enrollment trend dropped significantly',
                'message' => "YoY enrollment moved {$enrollmentYoYGrowth}% compared to the previous school year.",
                'severity' => 'critical',
            ];
        } elseif ($enrollmentYoYGrowth < 0) {
            $alerts[] = [
                'id' => 'enrollment-yoy',
                'title' => 'Enrollment trend is declining',
                'message' => "YoY enrollment moved {$enrollmentYoYGrowth}% compared to the previous school year.",
                'severity' => 'warning',
            ];
        }

        if ($sectionsWithoutAdviser > 0) {
            $alerts[] = [
                'id' => 'adviser-gap',
                'title' => 'Sections without advisers detected',
                'message' => "{$sectionsWithoutAdviser} section(s) are still unassigned.",
                'severity' => $sectionsWithoutAdviser >= 5 ? 'critical' : 'warning',
            ];
        }

        if ($unassignedSubjects > 0) {
            $alerts[] = [
                'id' => 'subject-gap',
                'title' => 'Unassigned subjects require staffing',
                'message' => "{$unassignedSubjects} subject(s) have no qualified teacher assignment.",
                'severity' => $unassignedSubjects >= 10 ? 'critical' : 'warning',
            ];
        }

        if ($sectionCapacityGap > 0) {
            $alerts[] = [
                'id' => 'section-capacity-gap',
                'title' => 'Projected section capacity gap',
                'message' => "Forecast suggests {$sectionCapacityGap} more section(s) are needed next school year.",
                'severity' => $sectionCapacityGap >= 3 ? 'critical' : 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'admin-stable',
                'title' => 'Academic operations are stable',
                'message' => 'Enrollment trend, staffing, and schedule health are within expected thresholds.',
                'severity' => 'info',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    public function prioritizeAdminKpis(
        array $kpis,
        int $sectionCapacityGap,
        int $pendingTeacherDemandCount,
        float $gradeVerificationSla,
    ): array {
        if ($sectionCapacityGap > 0) {
            return $this->sortKpisByPriority($kpis, [
                'admin-capacity-gap' => 0,
                'admin-teacher-demand' => 1,
                'admin-incomplete-schedules' => 2,
                'admin-grade-verification-sla' => 3,
                'admin-next-sy-forecast' => 4,
            ]);
        }

        if ($pendingTeacherDemandCount > 0) {
            return $this->sortKpisByPriority($kpis, [
                'admin-teacher-demand' => 0,
                'admin-incomplete-schedules' => 1,
                'admin-capacity-gap' => 2,
                'admin-grade-verification-sla' => 3,
                'admin-next-sy-forecast' => 4,
            ]);
        }

        if ($gradeVerificationSla < 90) {
            return $this->sortKpisByPriority($kpis, [
                'admin-grade-verification-sla' => 0,
                'admin-teacher-demand' => 1,
                'admin-incomplete-schedules' => 2,
                'admin-capacity-gap' => 3,
                'admin-next-sy-forecast' => 4,
            ]);
        }

        return $kpis;
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @return array<int, array{id: string, label: string, href: string}>
     */
    public function prioritizeAdminActionLinks(
        array $actionLinks,
        int $sectionCapacityGap,
        int $pendingTeacherDemandCount,
        float $gradeVerificationSla,
    ): array {
        if ($sectionCapacityGap > 0) {
            return $this->reorderActionLinks($actionLinks, [
                'open-section-manager',
                'open-teacher-profiles',
                'open-grade-verification',
            ]);
        }

        if ($pendingTeacherDemandCount > 0) {
            return $this->reorderActionLinks($actionLinks, [
                'open-teacher-profiles',
                'open-section-manager',
                'open-grade-verification',
            ]);
        }

        if ($gradeVerificationSla < 90) {
            return $this->reorderActionLinks($actionLinks, [
                'open-grade-verification',
                'open-section-manager',
                'open-teacher-profiles',
            ]);
        }

        return $actionLinks;
    }

    /**
     * @param  array<int, array{subject: string, assigned_teachers: int, minimum_needed: int, gap: int}>  $subjectDemandRows
     * @param  array<int, array{status: string, count: int}>  $gradeVerificationPipelineRows
     * @return array<int, array<string, mixed>>
     */
    public function adminTrends(
        int $currentSectionCount,
        int $projectedSectionsNeeded,
        int $sectionCapacityGap,
        array $subjectDemandRows,
        array $gradeVerificationPipelineRows,
    ): array {
        return [
            [
                'id' => 'section-capacity-planning',
                'label' => 'Needed Sections',
                'summary' => 'Compare projected enrollment with current sections',
                'display' => 'bar',
                'points' => [
                    ['label' => 'Current Sections', 'value' => $currentSectionCount],
                    ['label' => 'Projected Needed', 'value' => $projectedSectionsNeeded],
                    ['label' => 'Gap', 'value' => max($sectionCapacityGap, 0)],
                ],
                'chart' => [
                    'x_key' => 'metric',
                    'rows' => [
                        ['metric' => 'Current Sections', 'value' => $currentSectionCount],
                        ['metric' => 'Projected Needed', 'value' => $projectedSectionsNeeded],
                        ['metric' => 'Gap', 'value' => max($sectionCapacityGap, 0)],
                    ],
                    'series' => [[
                        'key' => 'value',
                        'label' => 'Sections',
                    ]],
                ],
            ],
            [
                'id' => 'subject-staffing-pressure',
                'label' => 'Subjects Needing Teachers',
                'summary' => 'Subjects without enough qualified teacher coverage',
                'display' => 'area',
                'points' => collect($subjectDemandRows)->map(fn (array $row): array => [
                    'label' => $row['subject'],
                    'value' => $row['gap'],
                ])->all(),
                'chart' => [
                    'x_key' => 'subject',
                    'rows' => $subjectDemandRows,
                    'series' => [[
                        'key' => 'gap',
                        'label' => 'Teachers Needed',
                    ]],
                ],
            ],
            [
                'id' => 'grade-verification-pipeline',
                'label' => 'Grade Verification Status',
                'summary' => 'Current quarter submissions by status',
                'display' => 'bar',
                'points' => collect($gradeVerificationPipelineRows)->map(fn (array $row): array => [
                    'label' => $row['status'],
                    'value' => $row['count'],
                ])->all(),
                'chart' => [
                    'x_key' => 'status',
                    'rows' => $gradeVerificationPipelineRows,
                    'series' => [[
                        'key' => 'count',
                        'label' => 'Submissions',
                    ]],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    public function teacherAlerts(int $atRiskCount, int $pendingGrades, int $attendanceRisk): array
    {
        $alerts = [];

        if ($pendingGrades > 0) {
            $alerts[] = [
                'id' => 'pending-grades-alert',
                'title' => 'Some grades still need encoding',
                'message' => "You have {$pendingGrades} unposted student grade rows for the current quarter.",
                'severity' => 'warning',
            ];
        }

        if ($atRiskCount >= 5) {
            $alerts[] = [
                'id' => 'academic-risk-alert',
                'title' => 'Some students have low grades',
                'message' => "{$atRiskCount} learners in your classes have grades below 75.",
                'severity' => 'danger',
            ];
        }

        if ($attendanceRisk >= 3) {
            $alerts[] = [
                'id' => 'attendance-risk-alert',
                'title' => 'Some students may need attendance follow-up',
                'message' => "{$attendanceRisk} learners have repeated absences or late marks in the last 14 days.",
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'teacher-stable',
                'title' => 'Teacher dashboard is on track',
                'message' => 'Grade encoding and student follow-up items are within expected range.',
                'severity' => 'info',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @return array<int, array{id: string, label: string, href: string}>
     */
    public function prioritizeTeacherActionLinks(
        array $actionLinks,
        int $totalPendingGradeRows,
        int $attendanceRiskCount,
        int $atRiskLearnersCount,
    ): array {
        if ($totalPendingGradeRows > 0) {
            return $this->reorderActionLinks($actionLinks, [
                'open-grading-sheet',
                'open-attendance',
                'open-advisory-board',
            ]);
        }

        if ($attendanceRiskCount > 0 || $atRiskLearnersCount > 0) {
            return $this->reorderActionLinks($actionLinks, [
                'open-attendance',
                'open-grading-sheet',
                'open-advisory-board',
            ]);
        }

        return $actionLinks;
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    public function prioritizeTeacherKpis(
        array $kpis,
        int $totalPendingGradeRows,
        float $submissionSlaRate,
        int $atRiskLearnersCount,
        int $attendanceRiskCount,
    ): array {
        if ($totalPendingGradeRows > 0 || $submissionSlaRate < 90) {
            return $this->sortKpisByPriority($kpis, [
                'grade-rows-pending' => 0,
                'teacher-grade-sla' => 1,
                'at-risk-learners' => 2,
                'teacher-attendance-risk' => 3,
            ]);
        }

        if ($atRiskLearnersCount >= 10 || $attendanceRiskCount >= 5) {
            return $this->sortKpisByPriority($kpis, [
                'at-risk-learners' => 0,
                'teacher-attendance-risk' => 1,
                'grade-rows-pending' => 2,
                'teacher-grade-sla' => 3,
            ]);
        }

        return $kpis;
    }

    /**
     * @param  array<int, array{label: string, value: int}>  $academicRiskByClass
     * @param  array<int, array{label: string, value: int}>  $pendingRowsByClass
     * @param  array<int, array{label: string, value: int}>  $attendanceRiskBySection
     * @param  array<int, array{status: string, count: int}>  $slaStatusRows
     * @return array<int, array<string, mixed>>
     */
    public function teacherTrends(
        array $academicRiskByClass,
        array $pendingRowsByClass,
        array $attendanceRiskBySection,
        array $slaStatusRows,
    ): array {
        return [
            [
                'id' => 'academic-risk-by-class',
                'label' => 'Students With Low Grades',
                'summary' => 'Count of learners with grades below 75 for the current quarter',
                'display' => 'bar',
                'points' => $academicRiskByClass,
                'chart' => [
                    'x_key' => 'class',
                    'rows' => collect($academicRiskByClass)->map(fn (array $row): array => [
                        'class' => $row['label'],
                        'count' => $row['value'],
                    ])->all(),
                    'series' => [[
                        'key' => 'count',
                        'label' => 'Students',
                    ]],
                ],
            ],
            [
                'id' => 'pending-grade-rows-by-class',
                'label' => 'Grade Encoding Status',
                'summary' => 'Unposted student grade rows per class',
                'display' => 'bar',
                'points' => $pendingRowsByClass,
                'chart' => [
                    'x_key' => 'class',
                    'rows' => collect($pendingRowsByClass)->map(fn (array $row): array => [
                        'class' => $row['label'],
                        'pending_rows' => $row['value'],
                    ])->all(),
                    'series' => [[
                        'key' => 'pending_rows',
                        'label' => 'Rows',
                    ]],
                ],
            ],
            [
                'id' => 'attendance-risk-hotspots',
                'label' => 'Class Attendance Summary',
                'summary' => 'Sections with students who may need attendance follow-up',
                'display' => 'bar',
                'points' => $attendanceRiskBySection,
                'chart' => [
                    'x_key' => 'section',
                    'rows' => collect($attendanceRiskBySection)->map(fn (array $row): array => [
                        'section' => $row['label'],
                        'at_risk' => $row['value'],
                    ])->all(),
                    'series' => [[
                        'key' => 'at_risk',
                        'label' => 'Students',
                    ]],
                ],
            ],
            [
                'id' => 'submission-sla-breakdown',
                'label' => 'Pending Grade Submissions',
                'summary' => 'Verified, submitted, and pending submission split',
                'display' => 'pie',
                'points' => collect($slaStatusRows)->map(fn (array $row): array => [
                    'label' => $row['status'],
                    'value' => $row['count'],
                ])->all(),
                'chart' => [
                    'x_key' => 'status',
                    'rows' => $slaStatusRows,
                    'series' => [[
                        'key' => 'count',
                        'label' => 'Count',
                    ]],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, message: string, severity: string}>
     */
    public function superAdminAlerts(
        ?int $backupAgeHours,
        int $riskAuditLogsToday,
        bool $maintenanceMode,
        bool $parentPortalEnabled,
    ): array {
        $alerts = [];

        if ($backupAgeHours === null || $backupAgeHours >= 72) {
            $alerts[] = [
                'id' => 'backup-stale',
                'title' => 'Backup needs checking',
                'message' => $backupAgeHours === null
                    ? 'No valid backup timestamp was found in system settings.'
                    : "Last backup is {$backupAgeHours} hours old.",
                'severity' => 'critical',
            ];
        } elseif ($backupAgeHours >= 24) {
            $alerts[] = [
                'id' => 'backup-warning',
                'title' => 'Backup should be checked',
                'message' => "Last backup is {$backupAgeHours} hours old.",
                'severity' => 'warning',
            ];
        }

        if ($riskAuditLogsToday >= 10) {
            $alerts[] = [
                'id' => 'audit-risk',
                'title' => 'Important actions need review',
                'message' => "{$riskAuditLogsToday} important administrative actions were logged today.",
                'severity' => 'critical',
            ];
        } elseif ($riskAuditLogsToday >= 4) {
            $alerts[] = [
                'id' => 'audit-risk',
                'title' => 'Some actions may need review',
                'message' => "{$riskAuditLogsToday} important administrative actions were logged today.",
                'severity' => 'warning',
            ];
        }

        if (! $parentPortalEnabled) {
            $alerts[] = [
                'id' => 'parent-portal-disabled',
                'title' => 'Parent portal is disabled',
                'message' => 'Parent-facing services are currently turned off in settings.',
                'severity' => 'warning',
            ];
        }

        if ($maintenanceMode) {
            $alerts[] = [
                'id' => 'maintenance',
                'title' => 'System is currently in maintenance mode',
                'message' => 'Only authorized users should be making configuration changes.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'super-admin-stable',
                'title' => 'Super admin dashboard is on track',
                'message' => 'Backups and important administrative actions are within expected range.',
                'severity' => 'info',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    public function prioritizeSuperAdminKpis(array $kpis, ?int $backupAgeHours): array
    {
        if (($backupAgeHours ?? 999) >= 24) {
            return $this->sortKpisByPriority($kpis, [
                'super-recovery-readiness' => 0,
                'super-high-risk-ratio' => 1,
                'super-high-risk-events-today' => 2,
            ]);
        }

        return $kpis;
    }

    /**
     * @return array<string, mixed>
     */
    public function financeCollectionEfficiencyCard(
        float $collectionEfficiencyPercent,
        string $monthlyCollectibleFormatted,
        string $monthlyCollectedFormatted,
    ): array {
        return [
            'id' => 'finance-collection-efficiency',
            'title' => 'Collected This Month',
            'decision' => 'Can the school meet this month\'s expected expenses, or should finance increase follow-up?',
            'metric' => number_format($collectionEfficiencyPercent, 2).'%',
            'status' => $this->statusByBelowThresholds($collectionEfficiencyPercent, 55, 75),
            'confidence' => 'high',
            'trigger' => 'Needs action below 75%',
            'rationale' => 'This compares this month\'s collection against dues expected this month.',
            'basis_points' => [
                [
                    'label' => 'Expected This Month',
                    'value' => $monthlyCollectibleFormatted,
                    'explanation' => null,
                ],
                [
                    'label' => 'Collected This Month',
                    'value' => $monthlyCollectedFormatted,
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Review accounts with unpaid dues.',
                'Schedule payment reminders for due accounts.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function financeOverdueExposureCard(
        float $overdueConcentration,
        string $outstandingBalanceFormatted,
        string $overdueOutstandingFormatted,
    ): array {
        return [
            'id' => 'finance-overdue-exposure',
            'title' => 'Payments Needing Follow-up',
            'decision' => 'Which accounts should finance contact first?',
            'metric' => number_format($overdueConcentration, 2).'%',
            'status' => $this->statusByAboveThresholds($overdueConcentration, 60, 35),
            'confidence' => 'high',
            'trigger' => 'Needs action at 35% or higher',
            'rationale' => 'This shows how much unpaid balance is already past due.',
            'basis_points' => [
                [
                    'label' => 'Outstanding Balance',
                    'value' => $outstandingBalanceFormatted,
                    'explanation' => null,
                ],
                [
                    'label' => 'Overdue Outstanding',
                    'value' => $overdueOutstandingFormatted,
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Open overdue accounts and follow up the oldest dues first.',
                'Use reminders for accounts that are newly overdue.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function financeCashierQueueCard(int $cashierQueueCount, float $averageAgeHours): array
    {
        return [
            'id' => 'finance-cashier-queue',
            'title' => 'Cashier Queue',
            'decision' => 'Does finance need another cashier to process enrollment payments faster?',
            'metric' => "{$cashierQueueCount} waiting",
            'status' => $this->statusByAboveThresholds((float) $cashierQueueCount, 40, 20),
            'confidence' => 'high',
            'trigger' => 'Needs action at 20+ waiting',
            'rationale' => 'A long payment queue can delay enrollment completion.',
            'basis_points' => [
                [
                    'label' => 'Enrollment Payments Waiting',
                    'value' => $cashierQueueCount,
                    'explanation' => null,
                ],
                [
                    'label' => 'Average Wait',
                    'value' => "{$averageAgeHours}h",
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Assign another cashier during busy payment periods.',
                'Process the oldest enrollment payment records first.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function registrarIntakeFlowCard(
        int $intakeQueuePressure,
        int $forCashierPipeline,
    ): array {
        return [
            'id' => 'registrar-intake-flow',
            'title' => 'Enrollment Queue',
            'decision' => 'Which enrollment records need action before they become active students?',
            'metric' => "{$intakeQueuePressure} queue items",
            'status' => $this->statusByAboveThresholds((float) $intakeQueuePressure, 60, 25),
            'confidence' => 'high',
            'trigger' => 'Needs action at 25+ waiting',
            'rationale' => 'Enrollment records in the queue still need office action before completion.',
            'basis_points' => [
                [
                    'label' => 'For Cashier Payment',
                    'value' => $forCashierPipeline,
                    'explanation' => null,
                ],
                [
                    'label' => 'Current Queue',
                    'value' => $intakeQueuePressure,
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Prioritize oldest pending enrollments first.',
                'Check if records are waiting for payment, requirements, or section assignment.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function superBackupReliabilityCard(
        ?int $backupAgeHours,
        ?string $latestBackupAt,
    ): array {
        return [
            'id' => 'governance-backup-freshness',
            'title' => 'Latest Backup',
            'decision' => 'Is it safe to perform major changes, or should a backup be verified first?',
            'metric' => $backupAgeHours === null ? 'Unknown' : "{$backupAgeHours}h since last backup",
            'status' => $backupAgeHours === null
                ? 'at_risk'
                : $this->statusByAboveThresholds((float) $backupAgeHours, 72, 24),
            'confidence' => $latestBackupAt ? 'high' : 'low',
            'trigger' => 'Check if older than 24h',
            'rationale' => 'A recent backup helps the school recover records if something goes wrong.',
            'basis_points' => [
                [
                    'label' => 'Last Backup',
                    'value' => $latestBackupAt ?: 'Not available',
                    'explanation' => null,
                ],
                [
                    'label' => 'Backup Age (Hours)',
                    'value' => $backupAgeHours === null ? 'Unknown' : $backupAgeHours,
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Run and verify the backup job before major changes.',
                'Confirm that the latest backup can be restored.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function superAuditRiskCard(int $riskAuditLogsToday, int $auditLogsToday): array
    {
        return [
            'id' => 'governance-audit-risk',
            'title' => 'Important Actions Today',
            'decision' => 'Do any important administrative changes need review?',
            'metric' => "{$riskAuditLogsToday} action(s) to review",
            'status' => $this->statusByAboveThresholds((float) $riskAuditLogsToday, 10, 4),
            'confidence' => 'high',
            'trigger' => 'Review when 4+ important actions occur',
            'rationale' => 'Deleted records, resets, and setting changes should be easy to review.',
            'basis_points' => [
                [
                    'label' => 'Total Actions Today',
                    'value' => $auditLogsToday,
                    'explanation' => null,
                ],
                [
                    'label' => 'Important Actions Today',
                    'value' => $riskAuditLogsToday,
                    'explanation' => null,
                ],
            ],
            'recommended_actions' => [
                'Review who changed important records today.',
                'Check if any action needs correction or follow-up.',
            ],
        ];
    }

    private function statusByBelowThresholds(float $value, float $atRiskIfBelow, float $watchIfBelow): string
    {
        if ($value < $atRiskIfBelow) {
            return 'at_risk';
        }

        if ($value < $watchIfBelow) {
            return 'watch';
        }

        return 'on_track';
    }

    private function statusByAboveThresholds(float $value, float $atRiskIfAtLeast, float $watchIfAtLeast): string
    {
        if ($value >= $atRiskIfAtLeast) {
            return 'at_risk';
        }

        if ($value >= $watchIfAtLeast) {
            return 'watch';
        }

        return 'on_track';
    }

    /**
     * @param  array<int, array{id: string, label: string, value: mixed, meta?: string|null}>  $kpis
     * @param  array<string, int>  $priority
     * @return array<int, array{id: string, label: string, value: mixed, meta?: string|null}>
     */
    private function sortKpisByPriority(array $kpis, array $priority): array
    {
        usort($kpis, fn (array $a, array $b): int => ($priority[$a['id']] ?? 99) <=> ($priority[$b['id']] ?? 99));

        return $kpis;
    }

    /**
     * @param  array<int, array{id: string, label: string, href: string}>  $actionLinks
     * @param  array<int, string>  $orderedIds
     * @return array<int, array{id: string, label: string, href: string}>
     */
    private function reorderActionLinks(array $actionLinks, array $orderedIds): array
    {
        $byId = collect($actionLinks)->keyBy('id');

        return collect($orderedIds)
            ->map(fn (string $id) => $byId->get($id))
            ->filter()
            ->values()
            ->all();
    }
}
