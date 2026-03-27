# ManagerIO — Product Roadmap

> Reference document for future development phases. Each phase builds on the previous one.
> Current status: MVP complete (accounting foundation + basic sales/purchases/banking/payments).

---

## Current MVP — What's Built

### Core Accounting
- [x] Chart of Accounts (full CRUD, customisable types/subtypes)
- [x] Journal Entries (post, reverse, auto-generation)
- [x] Tax Codes

### Sales
- [x] Customers
- [x] Quotes → convert to Invoice
- [x] Sales Invoices (draft, post, void)
- [x] Credit Notes
- [x] Customer Receipts (partial payment, allocation)

### Purchases
- [x] Suppliers
- [x] Purchase Invoices
- [x] Supplier Payments (partial payment, allocation)

### Banking
- [x] Bank Accounts
- [x] Bank Transactions (manual + auto-linked)
- [x] Bank Reconciliation

### Reports
- [x] Profit & Loss
- [x] Balance Sheet
- [x] Trial Balance
- [x] General Ledger
- [x] Aged Receivables
- [x] Aged Payables

### Platform
- [x] Multi-business workspaces
- [x] Role-based access (Owner, Admin, Editor, Viewer)
- [x] Two-factor authentication
- [x] Auto-numbered document sequences

---

## Phase 1 — Complete Standard Accounting (Manager.io parity)

> Goal: Feature parity with Manager.io's core modules. No translation-specific work yet.

### 1.1 Missing Document Types
- [x] **Purchase Orders** — PO workflow separate from purchase invoices. Statuses: draft → sent → invoiced. Convert to Purchase Invoice posts journal entry and links back to PO.
- [x] **Debit Notes** — Purchase-side equivalent of credit notes. Auto-creates journal entry (DR AP, CR Expense/Tax Receivable) and reduces AP balance.
- [ ] **Delivery Notes** (optional) — Sales-side document confirming service/goods delivery before invoicing.

### 1.2 Missing Reports
- [x] **Cash Flow Statement** — Operating (indirect method: net income + depreciation addback + working capital changes), investing (fixed asset changes), and financing (equity changes) activities for a period.
- [x] **Statement of Changes in Equity** — Opening equity balance, net income, owner contributions/withdrawals, closing balance per equity account.
- [x] **Account Transactions Report** — All transactions for a single account with opening balance and running balance (bank statement view per account).

### 1.3 Missing Accounting Features
- [x] **Recurring Journal Entries** — Schedule a journal entry to auto-post on a regular interval (monthly, quarterly). Essential for prepayments, depreciation.
- [x] **Opening Balances** — Bulk import opening balances when migrating from another system. One-time journal entry per account with a start date.
- [x] **Inter-company Transactions** — Transfer funds or charge between businesses in the same account (relevant for agencies with subsidiaries).
- [x] **Budgets** — Set monthly/annual budget targets per account. Compare actuals vs budget in P&L.

### 1.4 Fixed Assets Module
- [x] **Fixed Assets register** — Track assets (computers, equipment, vehicles, etc.)
- [x] **Depreciation schedules** — Straight-line and declining balance methods
- [x] **Asset disposal** — Record sale or write-off with gain/loss journal entry
- [x] **Depreciation run** — One-click post depreciation for a period

### 1.5 UX / Platform
- [x] **Bulk actions** — Mark multiple invoices as sent, bulk delete drafts
- [x] **Document PDF generation** — Generate/download professional PDF for invoices, quotes, credit notes, debit notes, and purchase orders.
- [x] **Email sending** — Send invoices and quotes directly from the app via configurable SMTP
- [x] **Currency formatting** — Configurable base currency per business with symbol and formatting.
- [x] **Audit log** — Who changed what and when across all documents
- [x] **Flash notifications** — Sonner toast notifications for all success/error flash messages across the app
- [x] **Invoice status flow fix** — Invoices start as DRAFT; only PAID/VOID invoices are locked; editing a SENT invoice reverts it to DRAFT; no-op saves do not revert status
- [x] **DomainException handling** — All update/post controller actions catch DomainException and show it as a flash error instead of a 500
- [x] **Wayfinder route migration** — All document show/edit pages use Wayfinder-generated TypeScript URL helpers instead of hardcoded strings; snake_case route bindings (credit_note, debit_note, purchase_order) correctly pass `.id`
- [x] **Quote account field removed** — Quotes no longer accept or store account_id on line items; account assignment happens at the invoice stage after conversion
- [x] **invoice_lines.account_id nullable** — Migration makes account_id nullable to support quote lines and converted-invoice drafts before account assignment

---

## Phase 2 — Translation Industry Foundation

> Goal: Make ManagerIO genuinely useful for translation businesses, not just generic accounting.

### 2.1 Languages & Services

- [ ] **Language model** — ISO 639 language codes, names, native names
- [ ] **Language Pairs** — Source + target combinations. Active/inactive status.
- [ ] **Service Types** — Translation, Editing, Proofreading, DTP, Localization, Transcription, Interpreting, Subtitling, Post-editing (MTPE), etc. Each with a default billing unit (word, hour, page, minute).

### 2.2 Rate Cards

- [x] **Client Rate Cards** — Rates per language pair + service type for a specific client. Overrides default rates.
- [x] **Default Rate Card** — Business-wide base rates per language pair + service type.
- [x] **Translator Rate Cards** — What you pay each freelancer per language pair + service type.
- [x] **Volume tiers** — Lower per-word rate above a word count threshold (e.g. >10,000 words = $0.09/word).
- [x] **Rush/expedited rates** — Multiplier or fixed surcharge for short-turnaround work.
- [x] **Minimum fees** — Minimum charge per assignment regardless of word count.

### 2.3 Translation Projects / Jobs

- [x] **Project model** — Client, source language, target language(s), service type, word count, deadline, reference number, internal notes.
- [x] **Project statuses** — New → In Progress → Review → Completed → Delivered → Invoiced → Closed.
- [x] **Multi-target projects** — One project with multiple target languages, each with separate word counts and translators.
- [x] **Project team** — Assign translator(s), editor(s), proofreader(s) per language combination.
- [x] **File attachments** — Attach source files and deliverables to a project (local storage initially).
- [x] **Project → Quote** — Auto-calculate a client quote from project details and rate card. Convert to sales quote.
- [x] **Project → Invoice** — Generate client invoice from project on completion.
- [x] **Project → Purchase Orders** — Auto-generate POs for assigned translators from project details and translator rates.

### 2.4 CAT Tool Analysis

- [x] **CAT Analysis model** — Import or manually enter word count analysis per translation memory match band.
- [x] **Match bands** — Context match (100%+), Exact match (100%), Fuzzy 95–99%, Fuzzy 85–94%, Fuzzy 75–84%, Fuzzy 50–74%, No match (0–49%), Repetitions.
- [x] **Weighted word count** — Apply discount percentages per match band to calculate effective word count for billing.
- [x] **Analysis → Quote** — Use CAT analysis to auto-populate line items in a client quote with correct pricing.
- [x] **Analysis → PO** — Use CAT analysis to auto-populate translator PO with weighted word counts.
- [x] **Import formats** — Support SDL Trados, memoQ, Phrase (Memsource) analysis export formats (CSV/XLIFF).

### 2.5 Extended Vendor Profiles (Translators)

- [ ] **Language pairs per translator** — Which source → target combinations a translator works in.
- [ ] **Service types per translator** — Translation, editing, proofreading, DTP, etc.
- [ ] **Specialisations** — Legal, Medical, Technical, Marketing, Financial, IT, Life Sciences, etc.
- [ ] **CAT tools** — Which CAT tools the translator uses (Trados, memoQ, Phrase, Wordfast, etc.).
- [ ] **Availability** — Available, Busy, On Leave status.
- [ ] **Quality rating** — Internal 1–5 star rating with notes.
- [ ] **Certification** — ISO 17100, ATA, NAATI, and other credentials.

### 2.6 Purchase Orders for Freelancers

- [ ] **Purchase Order model** — Linked to a project, issued to a translator/vendor.
- [ ] **PO statuses** — Draft → Sent → Accepted → In Progress → Delivered → Invoiced.
- [ ] **PO line items** — Service type, language pair, word count, unit, rate, total.
- [ ] **PO → Purchase Invoice** — When translator submits their invoice, link it to the PO with one click.
- [ ] **PO PDF** — Generate downloadable PO for the translator.

---

## Phase 3 — Advanced Translation Management

> Goal: Full project management platform for growing agencies.

### 3.1 Translation Memory & Terminology

- [ ] **Translation Memory (TM) management** — Track which TMs are used per client/project.
- [ ] **Terminology database** — Client glossaries and term bases. Associate per project.
- [ ] **Style guides** — Attach client style guides to projects.

### 3.2 Project Dashboard & Pipeline

- [ ] **Project board** — Kanban-style view of all active projects by status.
- [ ] **Project calendar** — View deadlines on a calendar. Highlight overdue projects.
- [ ] **Capacity planning** — See how many words each translator has in the pipeline vs their capacity.
- [ ] **Project search & filter** — Filter by status, client, language pair, service type, deadline range.

### 3.3 Translation-Specific Reports

- [ ] **Project Profitability** — Revenue (client invoice) vs cost (translator POs) per project. Margin per project.
- [ ] **Revenue by Language Pair** — How much revenue each language pair generates over a period.
- [ ] **Revenue by Service Type** — Split by translation, editing, DTP, etc.
- [ ] **Revenue by Client** — Top clients by revenue for a period.
- [ ] **Translator Utilisation** — Word volume per translator, revenue generated, average rate.
- [ ] **Average Margin Report** — Gross margin across all projects for a period.
- [ ] **Delivery Performance** — On-time delivery rate. Overdue project tracking.
- [ ] **Pipeline Report** — Projects in progress with expected invoice value.

### 3.4 Client Portal (read-only)

- [ ] **Client-facing quote approval** — Send a quote link; client can approve/reject with a comment.
- [ ] **Invoice portal** — Client receives a payment link and can view outstanding invoices.
- [ ] **Project status page** — Optional page where clients can check delivery status of active projects.

### 3.5 Workflow Automation

- [ ] **Auto-assign translator** — Based on language pair, service type, and availability, suggest best-fit translator.
- [ ] **Automatic PO generation** — When a project moves to "In Progress", auto-create POs for assigned translators.
- [ ] **Invoice reminders** — Auto-send payment reminder emails on overdue invoices.
- [ ] **Project deadline alerts** — Notify assigned translators of approaching deadlines.

---

## Phase 4 — Integrations & Scale

> Goal: Connect ManagerIO to the wider translation and business ecosystem.

### 4.1 Multi-Currency

- [ ] **Multi-currency invoicing** — Issue invoices in any currency. Display in client's currency and convert to base currency for accounting.
- [ ] **Exchange rate management** — Manual or auto-fetched rates. Store historical rates per date.
- [ ] **Currency gain/loss** — Auto-calculate and post realised/unrealised FX gain/loss.
- [ ] **Multi-currency payments** — Record receipts/payments in foreign currencies.

### 4.2 CAT Tool Integrations

- [ ] **Phrase (Memsource) API** — Pull project data, word counts, and assignments directly.
- [ ] **memoQ API** — Import projects and analysis from memoQ server.
- [ ] **SDL Trados GroupShare** — Sync projects and PO generation.
- [ ] **XTRF integration** — Two-way sync for agencies already using XTRF.

### 4.3 Accounting Integrations

- [ ] **Bank feed import** — Import bank statement CSV (OFX, MT940, CSV formats). Auto-match to transactions.
- [ ] **Xero / QuickBooks export** — Export journal entries for accountants who use other platforms.

### 4.4 Communication

- [ ] **Email templates** — Customisable email templates for invoices, quotes, POs, and reminders.
- [ ] **In-app messaging** — Brief notes/messages per project between team members.
- [ ] **Translator portal** — Freelancers can log in to view their assigned POs, submit invoices, and download documents.

### 4.5 Payroll & HR (basic)

- [ ] **Employees** — In-house staff separate from freelance suppliers.
- [ ] **Payslips** — Basic payroll processing with gross/net pay and deductions.
- [ ] **Leave management** — Approve/track holiday and sick leave.

---

## Notes for Future Development Sessions

### Key Architectural Decisions Already Made
1. `Invoice` model is polymorphic — handles sales invoices, purchase invoices, quotes, and credit notes via `InvoiceType` enum. New document types should follow this pattern or extend it.
2. `Contact` model is unified for customers and suppliers — distinguished by `type` column. Translator profiles should extend Contact, not replace it.
3. Every financial transaction auto-creates a `JournalEntry` via service layer. Maintain this pattern for any new financial events.
4. `BelongsToBusiness` trait auto-scopes all models to the current business session. New models must include this trait.
5. `BusinessRole` enum drives all authorisation. New permissions should use the `canEdit()` / `canManage()` helpers.
6. All policies extend from a base pattern — check `ContactPolicy` as the reference implementation.
7. Events are dispatched from service classes (not controllers). Follow `InvoicePosted`, `PaymentReceived` pattern for new domain events.
8. Wayfinder URL helpers: camelCase route bindings (e.g. `{invoice}`, `{quote}`) accept full Eloquent objects; snake_case bindings (e.g. `{credit_note}`, `{debit_note}`, `{purchase_order}`) only accept `string | number` — always pass `.id` for those.
9. Flash messages are shared via `HandleInertiaRequests` middleware (`flash.success` / `flash.error`) and displayed as Sonner toasts via the `useFlash()` hook mounted in `AppSidebarLayout`. DomainExceptions in service layer are caught in controllers and returned as `back()->with('error', ...)`.
10. Quotes never generate journal entries and have no account_id on their lines. Account assignment happens when the converted invoice draft is edited before posting.

### Suggested Implementation Order for Phase 2
1. Languages + Language Pairs + Service Types (no UI complexity, foundation for everything else)
2. Rate Cards (complex data model, but no UI beyond CRUD)
3. Projects (the centrepiece — start with basic CRUD and status management)
4. CAT Analysis (can be added to projects incrementally)
5. Extended vendor profiles (add to existing Contact/Supplier records)
6. Purchase Orders for freelancers (builds on existing purchase invoice flow)
7. Translation-specific reports (builds on existing report infrastructure)
