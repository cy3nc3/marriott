# CAPSTONE PAPER EDITING HANDOFF

Last updated: 2026-02-22  
Workspace: `C:\Users\jadeg\Documents\Capstone\marriott`  
Paper folder: `capstone-paper/`

## 1. Scope of this handoff

This handoff captures:

1. All progress made on editing the capstone paper.
2. Your explicit writing and formatting instructions.
3. Rules used while revising sections.
4. Suggested improvements for the next editing cycle.

This handoff is intentionally focused on capstone paper work only.

## 2. Main files and artifacts

Primary document versions:

1. `capstone-paper/MarriottConnect - Second Edition.docx` (current working file)
2. `capstone-paper/MarriottConnect - Second Edition.backup-before-update.docx` (baseline backup)
3. `capstone-paper/MarriottConnect - Second Edition.rewrite-v2.docx` (major rewrite checkpoint)
4. `capstone-paper/MarriottConnect - Second Edition.before-rrl-expansion.docx` (before expanded RRL narratives)
5. `capstone-paper/MarriottConnect - Second Edition.with-limitations.docx` (after adding Limitations section)

Reference and extraction files:

1. `capstone-paper/CAPSTONE_SYSTEM_DOCUMENTATION.md` (system-grounding reference)
2. `capstone-paper/tmp_docx_paragraphs.txt` (paragraph-level extraction for debugging spacing/style issues)
3. `capstone-paper/tmp_main_body_indexed.txt` (indexed body text checks)

Visual QA outputs (PDF + page images):

1. `capstone-paper/tmp/docs/rewrite_v2/`
2. `capstone-paper/tmp/docs/fix_intro_context/`
3. `capstone-paper/tmp/docs/context_joined/`
4. `capstone-paper/tmp/docs/rrl_final/`
5. `capstone-paper/tmp/docs/rrl_expanded/`
6. `capstone-paper/tmp/docs/scope_fixed/`
7. `capstone-paper/tmp/docs/scope_spacing_fix2/`
8. `capstone-paper/tmp/docs/with_limitations_main/`

## 3. Tooling and environment setup completed

Installed/used for editing and visual verification:

1. `python-docx` for paragraph/style-aware text replacement.
2. `LibreOffice (soffice)` for DOCX to PDF conversion.
3. `Poppler (pdftoppm)` for page rendering and visual spacing checks.
4. `pdf2image` for image conversion workflows where needed.

Purpose of this setup:

1. Edit text while preserving most document structure.
2. Verify layout and spacing issues page-by-page (not only plain text).

## 4. Consolidated user instructions (non-negotiable)

The following were explicitly requested and must be preserved for future edits:

1. Use `CAPSTONE_SYSTEM_DOCUMENTATION.md` as system reference for content updates.
2. Do not change the paper title; update body text only.
3. Mimic the current paper's writing style and structure.
4. Replace existing text content rather than redesigning chapter structure.
5. Use the backup document/page style as formatting reference when output looks off.
6. Expand Introduction and Project Context substantially.
7. Fix excessive spacing and awkward paragraph breaks.
8. Use system documentation, appendices interview evidence, and RRLs when writing.
9. Keep RRL sources from 2021 onward.
10. Keep only strongly relevant RRLs to MarriottConnect and Marriott School problems.
11. Ensure exactly 5 local (Philippines) and 5 international studies in RRL set.
12. In Scope, apply bullets only where appropriate (module items), not to role header lines.
13. Add a proper `Limitations` subsection (do not remove it).
14. Expand each RRL review and its relevance justification.

## 5. Editing progress timeline (what was actually done)

### 5.1 Initial rewrite pass

1. Replaced major body text across Introduction, Problem, Objectives, Scope, RRL, Methodology, and Requirements sections.
2. Kept chapter ordering and core section names aligned with existing paper format.
3. Created a restorable backup before heavy edits.

### 5.2 Introduction and Project Context expansion

1. Expanded Introduction to provide stronger institutional and operational framing.
2. Expanded Project Context significantly to include:
   - fragmentation across registrar/finance/academic flows
   - coordination and reconciliation bottlenecks
   - data lifecycle rationale (intake -> posting -> enrichment -> reuse)
   - role-governed data visibility logic
3. Addressed the user-reported "too much spacing" by revising paragraph segmentation and break handling.

### 5.3 Context paragraph continuity fixes

1. Joined context paragraphs that were unnaturally split.
2. Reduced visual whitespace blocks between related arguments.
3. Rechecked continuity across the page breaks where text was flowing as isolated fragments.

### 5.4 Scope structure and formatting fixes

1. Corrected list semantics so role header lines are plain text, not bullets.
2. Ensured module entries under each role use consistent bullet formatting.
3. Fixed inconsistent spacing between scope items where some blocks had tighter/looser line layout than others.
4. Preserved deferred items but clearly labeled them as deferred.

### 5.5 RRL relevance filtering and replacement

1. Audited relevance of existing RRLs against actual system modules and problems.
2. Removed weakly relevant sources and replaced them with stronger matches.
3. Enforced publication-year constraint (2021+).
4. Enforced ratio target: 5 local + 5 international.

### 5.6 RRL expansion pass

1. Expanded each RRL entry with longer source discussion.
2. Added longer "why this is relevant to MarriottConnect" justification per source.
3. Grounded relevance narratives in documented school constraints and appendices interview evidence.

### 5.7 Limitations subsection insertion

1. Added explicit `Limitations` subsection after Scope and before RRL.
2. Covered practical boundaries:
   - deferred modules
   - LIS integration boundary
   - analytics depth boundary
   - reporting/export boundary
   - single-institution validation boundary

### 5.8 Asset organization and folder move

1. Moved capstone-related documents and helper artifacts into `capstone-paper/` to keep workspace cleaner.
2. Kept multiple doc checkpoints so rollbacks remain possible.

## 6. Current RRL set in the paper (validated roster)

Local (Philippines) studies in final references:

1. Grepon et al. (2021)
2. Zulueta et al. (2021)
3. Esquivel & Esquivel (2021)
4. Porlas et al. (2023)
5. Navarra & Antonio (2025)

International studies in final references:

1. Wulandari & Pinandito (2021)
2. Chai & Mostafa (2021)
3. Kanona (2022)
4. Hussain et al. (2023)
5. Muller et al. (2025)

Status:

1. Local count target met (5).
2. International count target met (5).
3. Year requirement met (all 2021+).

## 7. Known formatting/style observations still worth checking

1. `Limitations` appears present but currently may use `Normal` style instead of a heading style in the DOCX style map.
2. `Statement of the Problem` also appears as normal paragraph style, not heading style.
3. Under `Requirements Documentation`, several narrative lines are currently styled as `Heading 2`; this can affect TOC cleanliness and visual hierarchy.
4. Some extracted text artifacts (for example `Munoz`) appear in text dumps due encoding during extraction; verify original DOCX display before treating as a document error.

## 8. Practical writing rules used during revision

1. Keep tone academic but practical and operational (not overly theoretical).
2. Connect every major claim to either:
   - appendices interview findings, or
   - selected RRL evidence, or
   - implemented system behavior.
3. Prefer section-level flow:
   - issue
   - observed impact
   - system response
   - expected operational improvement
4. Avoid isolated one-line paragraphs unless they are section labels.
5. Keep role/module descriptions explicit and action-oriented.
6. Keep deferred features visible so scope boundaries remain defensible.

## 9. Suggested improvements for next editing cycle

### 9.1 Highest priority (content integrity)

1. Build a claim-to-evidence matrix:
   - each major chapter claim
   - interview excerpt support
   - RRL support
   - system module support
2. Add stronger synthesis transitions between:
   - Problem -> Objectives
   - Scope -> Limitations
   - RRL Synthesis -> Methodology
3. Tighten APA consistency across all references (capitalization, punctuation, issue/page completeness).

### 9.2 Highest priority (format integrity)

1. Normalize heading styles for all section/subsection titles.
2. Rebuild TOC after heading-style cleanup.
3. Apply one paragraph spacing rule globally (before/after) for body text.
4. Revalidate bullet indentation and line spacing per role block in Scope.

### 9.3 Recommended enhancements (defense readiness)

1. Add a short "Operational Assumptions" subsection after Limitations.
2. Add a compact "Traceability Matrix" appendix linking:
   - problems
   - objectives
   - modules
   - test evidence
3. Add a "Future Enhancement Prioritization" list aligned to deferred scope.

## 10. Should `CAPSTONE_SYSTEM_DOCUMENTATION.md` be expanded?

Short answer: it is already strong as a system reference, but it should be expanded for paper-writing traceability.

Add these sections to make it significantly stronger for future AI-assisted drafting:

1. Interview Evidence Digest:
   - stakeholder-by-stakeholder pain points
   - direct quote snippets
   - mapped impacted module
2. Claim-to-Source Mapping Table:
   - chapter claim
   - interview support
   - RRL support
   - implementation support
3. Chapter Writing Constraints:
   - target paragraph length per section
   - required tone
   - citation minimums
4. RRL Quality Log:
   - why each source is included
   - why rejected sources were removed
   - publication year/relevance check notes
5. Formatting Contract:
   - heading style map
   - spacing/bullets rules
   - known section formatting exceptions

## 11. Repeatable QA workflow for future edits

1. Edit content in DOCX using style-preserving operations.
2. Convert to PDF with `soffice`.
3. Render target pages with `pdftoppm`.
4. Compare against backup/reference pages for spacing and hierarchy.
5. Re-check:
   - heading styles
   - bullets
   - paragraph spacing
   - section order
6. Export fresh paragraph index (`tmp_docx_paragraphs.txt`) for spot checks.

## 12. Current readiness snapshot

1. Introduction and Project Context: expanded and substantially improved.
2. Scope: corrected structure and spacing pass completed.
3. Limitations: present and scoped.
4. RRL: 10 studies, 5 local + 5 international, 2021+ validated, with expanded relevance discussions.
5. Remaining quality work: heading-style normalization and final formatting consistency pass.
