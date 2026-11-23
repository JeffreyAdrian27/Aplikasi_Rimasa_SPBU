# Design System — Rimasa SPBU

## Mode & Direction
- **Surface Mode**: Operate (financial operations dashboard & daily data-entry workspace).
- **Aesthetic**: Crisp, high-legibility light operational workspace ("Refined Financial Ledger") with a warm-slate sidebar and clean white ledger surfaces—built for hours of daily accounting work without eye fatigue.
- **Visual Authority**: Precision, tabular alignment, restrained color, and instant scanability. No decorative gradients, no gratuitous animations, no emoji icons.

## Color Tokens
- **Canvas Background**: `#F8FAFC` (Slate 50)
- **Surface / Card**: `#FFFFFF` with `1px solid #E2E8F0` border (Slate 200)
- **Sidebar / Navigation Shell**: `#0F172A` (Slate 900) with `#94A3B8` muted items and `#FFFFFF` active text
- **Primary Action (SPBU Red-Crimson / Pertamina-inspired restrained or Deep Emerald/Blue)**:
  - Primary Brand Accent: `#0F766E` (Teal 700 — calm financial authority, high contrast >= 5.4:1 on white)
  - Primary Hover: `#115E59` (Teal 800)
  - Primary Soft Tint: `#F0FDFA` (Teal 50)
- **Semantic States**:
  - Positive / Lunas / Surplus: `#15803D` text on `#DCFCE7` badge
  - Warning / Belum Lunas / Tempo: `#B45309` text on `#FEF3C7` badge
  - Danger / Defisit / Jatuh Tempo: `#B91C1C` text on `#FEE2E2` badge
  - Info / Rek 1 & Rek 2 Tags: `#1D4ED8` on `#DBEAFE` (Rek 1), `#6D28D9` on `#EDE9FE` (Rek 2)
- **Text Hierarchy**:
  - Primary: `#0F172A`
  - Secondary: `#475569`
  - Muted: `#64748B`

## Typography
- **Primary UI Font**: `Plus Jakarta Sans` (clean geometric legibility for Indonesian labels and headings)
- **Numeric / Ledger Font**: `JetBrains Mono` / `tabular-nums` on all currency, liter, and date columns so digits align vertically.
- **Scale**: Fixed rem scale (h1 `1.5rem`, h2 `1.125rem`, body `0.875rem`, table `0.8125rem`, micro label `0.75rem`).

## Layout & Components
- **App Shell**: Fixed left sidebar (`248px` on desktop, slide-over drawer on mobile) + sticky top bar (`60px`) with page title, active branch/role indicator, and quick action button.
- **Data Tables**: Compact, right-aligned numeric columns, sticky header, clean horizontal dividers, inline quick actions (e.g. 1-click "Tandai Lunas"), and clear summary footer row.
- **Forms**: Inline slide-over drawer or clean top collapsible card—avoiding heavy nested modals where possible; live preview of calculated totals (Volume x Harga, Jatuh Tempo) before saving.
- **Icons**: Consistent `lucide-vue-next` 1.75px stroke SVG icons only.
- **Motion**: `150ms–200ms` ease-out transitions for hover, drawer open, and tab switching; respects `prefers-reduced-motion`.
