# DESIGN.md — JadwalPintar (Apple / Craft System)

## Subject / Audience / Primary Job

Subject: Academic Information System with intelligent course scheduling
Audience: Indonesian university admin staff, lecturers, and students
Primary Job: Admin generates a conflict-free class timetable in minutes, not weeks
Visual Anchor: Apple macOS / Craft Pro Application (subtle warm-neutral canvas, crisp white cards, hairline borders, SF-style typography, segmented pill controls)

## Voice

Practical, competent, Indonesian-first.
- No corporate buzzwords, no "AI-powered" hype
- Copy reads like a smart macOS/Craft productivity app
- Technical but approachable — "Jadwal bentrok" not "Schedule conflict detected"

## Token Spine

### Colors (Apple / Craft semantic roles)

  --color-canvas:         #F5F5F7     /* Apple system gray 6 / Craft canvas */
  --color-card:           #FFFFFF     /* elevated rounded cards */
  --color-card-subtle:    #FBFBFD     /* subtle toolbar / sidebar background */
  --color-border:         rgba(0, 0, 0, 0.07)  /* hairline 1px separator */
  --color-border-strong:  rgba(0, 0, 0, 0.12)  /* active borders, inputs */

  --color-primary:        #0071E3     /* Apple System Blue: primary CTA, high-intent actions */
  --color-primary-hover:  #0077ED     /* Blue hover */
  --color-primary-subtle: rgba(0, 113, 227, 0.08) /* Blue tint for active nav & tags */

  --color-accent-ti:      #0071E3     /* System Blue: Teknik Informatika */
  --color-accent-si:      #AF52DE     /* System Purple: Sistem Informasi */
  --color-accent-bd:      #FF9500     /* System Orange: Bisnis Digital */

  --color-success:        #34C759     /* System Green: 0 bentrok, approved */
  --color-danger:         #FF3B30     /* System Red: bentrok HC-1/HC-2 */

  --color-text:           #1D1D1F     /* Apple primary label (high contrast ink) */
  --color-text-secondary: #86868B     /* Apple secondary label */
  --color-text-tertiary:  #A1A1A6     /* Captions, placeholders */

### Typography

  Font stack:     -apple-system, BlinkMacSystemFont, "SF Pro Text", "Plus Jakarta Sans", sans-serif
  Mono:           "SF Mono", "JetBrains Mono", ui-monospace, monospace (tabular numbers, codes, slots)

  Scale:
    --text-xs:    0.75rem / 1rem
    --text-sm:    0.875rem / 1.25rem
    --text-base:  1rem / 1.5rem
    --text-lg:    1.125rem / 1.75rem
    --text-xl:    1.25rem / 1.75rem
    --text-2xl:   1.5rem / 2rem
    --text-3xl:   1.875rem / 2.25rem

### Spacing

  Scale: 4px base (0.25rem)
  --space-1:  0.25rem    /* 4px */
  --space-2:  0.5rem     /* 8px */
  --space-3:  0.75rem    /* 12px */
  --space-4:  1rem       /* 16px */
  --space-6:  1.5rem     /* 24px */
  --space-8:  2rem       /* 32px */

### Radius (Craft continuous curvature)

  --radius-sm:   0.375rem  /* 6px  — segmented buttons, tags */
  --radius-md:   0.625rem  /* 10px — cards, inputs */
  --radius-lg:   0.875rem  /* 14px — panels, timetable blocks */
  --radius-xl:   1.25rem   /* 20px — main window container */
  --radius-full: 9999px    /* pills, badges */

### Shadows (Soft Apple diffuse)

  --shadow-sm:   0 1px 2px rgba(0, 0, 0, 0.04), 0 0 1px rgba(0, 0, 0, 0.08)
  --shadow-card: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 0 1px rgba(0, 0, 0, 0.08)
  --shadow-pill: 0 1px 3px rgba(0, 0, 0, 0.08), 0 0.5px 1px rgba(0, 0, 0, 0.04)

## Signature Bet

Schedule Grid as the Hero Surface.

The scheduling result is displayed as an interactive week-grid (rows = time slots,
cols = days) where each assigned class is a colored block. Colors encode prodi/fakultas.
Admin can click a block to see WHY it was placed there (constraint explanation popover).
Unassigned/conflicted items sit in a "dock" sidebar, draggable onto the grid for manual
override.

This is not a dashboard. This is a constraint workspace — the admin sees the REASONING,
not just the result. No other open-source SIAKAD does this.

## Design Knobs

  DESIGN_VARIANCE:  5   (functional tool, not flashy, but not boring)
  VISUAL_DENSITY:   7   (academic data is dense — tables, grids, forms)
  MOTION_INTENSITY: 2   (hover feedback + page transitions only)

## Screen Archetypes

  Admin Scheduling:     Workspace (grid + constraint dock + explanation popover)
  Admin Dashboard:      Executive Dashboard (enrollment stats, schedule status)
  Admin Master Data:    List-Detail (CRUD tables with side panel)
  KRS Mahasiswa:        Guided Flow (step: pilih matkul -> review -> submit)
  Quiz:                 Guided Flow (soal per soal, timer bar)
  Nilai/Transkrip:      Dense Ledger (grade table, monospace numbers)
  Dosen Dashboard:      List-Detail (kelas hari ini + action items)

## Icon Set

  Lucide React (one set, consistent stroke width)

## Banned (AI Slop)

  Per anti-ui-slop contract. Specifically for this project:
  - No gradient heroes or purple/cyan anything
  - No glassmorphism on tables (this is a data tool)
  - No emoji as nav icons — Lucide only
  - No "AI-powered" marketing copy (the scheduler is an algorithm, not AI)
  - No generic dashboard with 6 identical stat cards
  - Sentence case everywhere, no ALL-CAPS nav/labels
  - No bounce/elastic on hover
