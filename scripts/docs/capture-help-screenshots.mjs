import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const baseUrl = process.env.DOCS_SCREENSHOT_BASE_URL ?? 'https://msqc.tech';
const password = process.env.DOCS_SCREENSHOT_PASSWORD ?? 'password';
const outputDir = path.resolve('public/docs/screenshots');

const accounts = {
    super_admin: 'superadmin@marriott.edu',
    admin: 'alex.avellanosa@marriott.edu',
    registrar: 'jocelyn.cleofe@marriott.edu',
    finance: 'corrine.avellanosa@marriott.edu',
    teacher: 'rowell.almonte@marriott.edu',
    student: 'balmes.232007000001@marriott.edu',
    parent: 'parent.232007000001@marriott.edu',
};

const pagesByRole = {
    super_admin: [
        ['super-admin-dashboard', '/dashboard'],
        ['super-admin-user-manager', '/super-admin/user-manager'],
        ['super-admin-announcements', '/announcements'],
        ['super-admin-audit-logs', '/super-admin/audit-logs'],
        ['super-admin-permissions', '/super-admin/permissions'],
        ['super-admin-system-settings', '/super-admin/system-settings'],
    ],
    admin: [
        ['admin-dashboard', '/dashboard'],
        ['admin-announcements', '/announcements'],
        ['admin-school-year-manager', '/admin/academic-controls'],
        ['admin-curriculum-manager', '/admin/curriculum-manager'],
        ['admin-section-manager', '/admin/section-manager'],
        ['admin-schedule-builder', '/admin/schedule-builder'],
        ['admin-grade-verification', '/admin/grade-verification'],
        ['admin-class-lists', '/admin/class-lists'],
    ],
    registrar: [
        ['registrar-dashboard', '/dashboard'],
        ['registrar-announcements', '/announcements'],
        ['registrar-student-directory', '/registrar/student-directory'],
        ['registrar-enrollment', '/registrar/enrollment'],
        ['registrar-permanent-records', '/registrar/permanent-records'],
        ['registrar-data-import', '/registrar/data-import'],
        ['registrar-batch-promotion', '/registrar/batch-promotion'],
        ['registrar-remedial-entry', '/registrar/remedial-entry'],
        ['registrar-student-departure', '/registrar/student-departure'],
    ],
    finance: [
        ['finance-dashboard', '/dashboard'],
        ['finance-announcements', '/announcements'],
        ['finance-student-ledgers', '/finance/student-ledgers'],
        ['finance-cashier-panel', '/finance/cashier-panel'],
        ['finance-transaction-history', '/finance/transaction-history'],
        ['finance-data-import', '/finance/data-import'],
        ['finance-product-inventory', '/finance/product-inventory'],
        ['finance-discount-manager', '/finance/discount-manager'],
        ['finance-fee-structure', '/finance/fee-structure'],
        ['finance-daily-reports', '/finance/daily-reports'],
    ],
    teacher: [
        ['teacher-dashboard', '/dashboard'],
        ['teacher-announcements', '/announcements'],
        ['teacher-schedule', '/teacher/schedule'],
        ['teacher-attendance', '/teacher/attendance'],
        ['teacher-grading-sheet', '/teacher/grading-sheet'],
        ['teacher-historical-records', '/teacher/historical-records'],
        ['teacher-remedial-encoding', '/teacher/remedial-encoding'],
        ['teacher-advisory-board', '/teacher/advisory-board'],
    ],
    student: [
        ['student-dashboard', '/dashboard'],
        ['student-schedule', '/student/schedule'],
        ['student-grades', '/student/grades'],
    ],
    parent: [
        ['parent-dashboard', '/dashboard'],
        ['parent-schedule', '/parent/schedule'],
        ['parent-grades', '/parent/grades'],
        ['parent-billing-information', '/parent/billing-information'],
    ],
};

async function login(page, email) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('[data-test="login-button"]').click();
    await page.waitForLoadState('networkidle');
}

async function hideNonContentChrome(page) {
    await page.addStyleTag({
        content: `
            [data-sonner-toaster],
            [data-slot="sidebar"],
            [data-slot="sidebar-rail"],
            [data-slot="sidebar-trigger"] {
                display: none !important;
            }
        `,
    });
}

async function screenshotContent(page, filePath) {
    await hideNonContentChrome(page);
    await page.waitForTimeout(700);

    const content = page.locator('main[data-slot="sidebar-inset"] .simplebar-content').first();

    if (await content.count()) {
        await content.screenshot({
            path: filePath,
            animations: 'disabled',
            timeout: 7000,
        });
        return;
    }

    const main = page.locator('main[data-slot="sidebar-inset"]').first();
    if (await main.count()) {
        await main.screenshot({
            path: filePath,
            animations: 'disabled',
            timeout: 7000,
        });
        return;
    }

    await page.screenshot({
        path: filePath,
        animations: 'disabled',
        fullPage: true,
    });
}

async function captureRole(browser, role) {
    const context = await browser.newContext({
        viewport: { width: 1440, height: 1000 },
        deviceScaleFactor: 1,
        colorScheme: 'light',
    });
    const page = await context.newPage();

    await login(page, accounts[role]);

    for (const [slug, href] of pagesByRole[role]) {
        const filePath = path.join(outputDir, `${slug}.png`);
        try {
            await page.goto(`${baseUrl}${href}`, { waitUntil: 'domcontentloaded' });
            await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
            await screenshotContent(page, filePath);
            console.log(`${slug}.png`);
        } catch (error) {
            const fallbackPath = path.join(outputDir, `${slug}.failed.png`);
            await page.screenshot({ path: fallbackPath, fullPage: true }).catch(() => {});
            console.error(`FAILED ${role} ${href}: ${error.message}`);
        }
    }

    await context.close();
}

await fs.mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const requestedRoles = (process.env.DOCS_SCREENSHOT_ROLES ?? '')
    .split(',')
    .map((role) => role.trim())
    .filter(Boolean);
const rolesToCapture =
    requestedRoles.length > 0 ? requestedRoles : Object.keys(pagesByRole);

try {
    for (const role of rolesToCapture) {
        await captureRole(browser, role);
    }
} finally {
    await browser.close();
}
