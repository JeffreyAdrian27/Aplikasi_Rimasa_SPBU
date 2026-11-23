# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Laravel 11 + Vue 3 + Inertia.js + Tailwind CSS + MySQL (Laragon), single unified repository.

## Users

- **Owner**: Monitors multi-branch financial health across all SPBU branches (Tanjung Sari, Kakap), reviews daily cashflow projections (Rek 1 & Rek 2), monitors peak working-capital needs, tracks client receivables, and configures opening balances and branch margins.
- **Admin Cabang**: Performs fast daily operational data entry for their assigned branch (Tanjung Sari or Kakap)—recording Pertamina fuel redemptions (Tebus), client credit sales & payment settlements, and daily cash shift entries (Rek 1 & Rek 2).

## Product Purpose

Replace the complex 9-sheet Excel workbook (`Manajemen_Keuangan_BBM_Multi_Produk.xlsx`) with a simple, error-resistant, fast web application for SPBU multi-product financial management (Solar, Pertalite, Pertamax, plus LPG/Oli & operational cash). Success means branch admins can input daily transactions in seconds without touching formulas, and the owner can instantly see cashflow, receivables, and working-capital risk.

## Positioning

A purpose-built SPBU financial operations workspace that preserves exact accounting rules (Rek 1 vs Rek 2 separation, automatic 3% margin transfer, TSPS cash exchange deductions, and daily peak cash deficit alerts) while collapsing 9 fragile spreadsheet tabs into 5 intuitive screens.

## Operating Context

- Daily SPBU accounting cycle in Indonesian Rupiah (Rp), operating across 3 shifts (Shift I, II, III).
- Two bank accounts tracked centrally across branches: **Rekening 1** (BBM turnover, Pertamina redemptions, client payments, BCA cash deposits after margin & TSPS) and **Rekening 2** (3% margin transfers from Rek 1, LPG/Oli/other income, SPBU operational expenses).
- Used on desktop/laptop at the SPBU office and occasionally on tablet/mobile by the Owner.

## Capabilities and Constraints

- **Multi-role auth**: Owner (full access across all branches + settings) and Admin Cabang (scoped to assigned branch for input & branch summary).
- **5 Core Modules**: Dashboard & Ringkasan, Tebus Pertamina, Penjualan & Piutang (+ Rekap Perusahaan), Kas Harian Cabang (Rek 1 & Rek 2 unified), and Proyeksi Kas Harian (monthly 1–31 simulation).
- **Exact Excel parity**: Formulas for total tebus, jatuh tempo, status pelunasan, margin %, stor tunai BCA, dana neto terpakai, and tambahan kas puncak must match the Excel workbook.
- **Seeded with real sample data** from `Manajemen_Keuangan_BBM_Multi_Produk.xlsx` on MySQL (Laragon).
- **Language**: Indonesian UI copy, clear labels, plain financial terminology familiar to SPBU staff.

## Evidence on Hand

- Reference Excel workbook: `Manajemen_Keuangan_BBM_Multi_Produk.xlsx` (branches: Tanjung Sari, Kakap; products: Solar, Pertalite, Pertamax; period: November 2026; opening balance Rek 1 Rp 200.000.000, Rek 2 Rp 0; margin 3%).

## Product Principles

1. **Simplicity over spreadsheet sprawl**: Collapse repetitive per-branch sheets into unified views with instant branch/account filters.
2. **Zero manual formula risk**: All totals, due dates, margins, BCA deposits, and daily balances compute automatically on the backend.
3. **Operate-first clarity**: Dense financial figures must be scannable at a glance (tabular numbers, right-aligned currency, unmistakable status badges).
4. **Role-focused workspace**: Branch admins see a clean, distraction-free input workspace; Owners see consolidated multi-branch intelligence.
