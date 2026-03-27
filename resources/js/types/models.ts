// ── Business ──────────────────────────────────────────────
export type Business = {
    id: number;
    name: string;
    legal_name: string | null;
    tax_number: string | null;
    email: string | null;
    phone: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string;
    currency_code: string;
    fiscal_year_start: number;
    logo_path: string | null;
    created_at: string;
    updated_at: string;
};

export type BusinessSummary = {
    id: number;
    name: string;
    currency_code: string;
    country: string;
    role: string;
    members_count: number;
};

export type CurrentBusiness = {
    id: number;
    name: string;
    currency_code: string;
    role: string;
    can_edit: boolean;
    can_manage: boolean;
};

// ── Account ──────────────────────────────────────────────
export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';

export type AccountSubType =
    | 'cash' | 'bank' | 'accounts_receivable' | 'tax_receivable' | 'prepaid_expense' | 'other_current_asset' | 'fixed_asset'
    | 'accounts_payable' | 'tax_payable' | 'credit_card' | 'other_current_liability' | 'long_term_liability'
    | 'owner_equity' | 'retained_earnings' | 'intercompany'
    | 'sales_revenue' | 'service_revenue' | 'other_revenue'
    | 'cost_of_services' | 'operating_expense' | 'payroll_expense' | 'depreciation' | 'other_expense';

export type Account = {
    id: number;
    business_id: number;
    code: string;
    name: string;
    type: AccountType;
    sub_type: AccountSubType | null;
    description: string | null;
    is_system: boolean;
    is_active: boolean;
    parent_id: number | null;
    parent?: Account;
    children?: Account[];
    balance?: number;
    created_at: string;
    updated_at: string;
};

export type AccountOption = Pick<Account, 'id' | 'code' | 'name' | 'type'>;

// ── Tax Code ─────────────────────────────────────────────
export type TaxCode = {
    id: number;
    business_id: number;
    name: string;
    rate: number;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type TaxCodeOption = Pick<TaxCode, 'id' | 'name' | 'rate'>;

// ── Contact ──────────────────────────────────────────────
export type ContactType = 'customer' | 'supplier' | 'both';

export type Contact = {
    id: number;
    business_id: number;
    type: ContactType;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
    currency_code: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type ContactOption = Pick<Contact, 'id' | 'name'>;

// ── Journal Entry ────────────────────────────────────────
export type JournalEntry = {
    id: number;
    business_id: number;
    entry_number: string;
    date: string;
    description: string | null;
    reference: string | null;
    source_type: string | null;
    source_id: number | null;
    is_posted: boolean;
    posted_at: string | null;
    reversal_of_id: number | null;
    created_by: number;
    creator?: { id: number; name: string };
    lines?: JournalEntryLine[];
    reversal_of?: JournalEntry;
    reversals?: JournalEntry[];
    created_at: string;
    updated_at: string;
};

export type JournalEntryLine = {
    id: number;
    journal_entry_id: number;
    account_id: number;
    contact_id: number | null;
    description: string | null;
    debit: number;
    credit: number;
    tax_code_id: number | null;
    account?: Account;
    contact?: Contact;
    tax_code?: TaxCode;
};

// ── Recurring Journal Entry ───────────────────────────────
export type RecurringFrequency = 'monthly' | 'quarterly' | 'annually';

export type RecurringJournalEntry = {
    id: number;
    business_id: number;
    name: string;
    description: string | null;
    frequency: RecurringFrequency;
    start_date: string;
    end_date: string | null;
    next_run_date: string;
    last_run_at: string | null;
    day_of_month: number;
    is_active: boolean;
    template_lines: JournalTemplateLine[];
    created_by: number;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

export type JournalTemplateLine = {
    account_id: number;
    description: string;
    debit: number;
    credit: number;
};

// ── Budget ────────────────────────────────────────────────
export type AccountBudget = {
    id: number;
    business_id: number;
    account_id: number;
    year: number;
    month: number | null;
    amount: number;
    account?: Account;
    created_at: string;
    updated_at: string;
};

export type BudgetRow = {
    account: Account;
    months: Record<number, { budgeted: number; actual: number; variance: number }>;
    total_budgeted: number;
    total_actual: number;
    total_variance: number;
};

export type BudgetReport = {
    year: number;
    accounts: BudgetRow[];
};

// ── Intercompany Transaction ──────────────────────────────
export type IntercompanyTransaction = {
    id: number;
    source_business_id: number;
    target_business_id: number;
    source_account_id: number;
    target_account_id: number;
    amount: string;
    date: string;
    description: string;
    reference: string | null;
    source_journal_entry_id: number | null;
    target_journal_entry_id: number | null;
    created_by: number;
    source_business?: { id: number; name: string };
    target_business?: { id: number; name: string };
    source_account?: Account;
    target_account?: Account;
    source_journal_entry?: JournalEntry;
    target_journal_entry?: JournalEntry;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

// ── Fixed Assets ─────────────────────────────────────────
export type AssetStatus = 'active' | 'retired' | 'disposed';

export type DepreciationMethod = 'straight_line' | 'declining_balance';

export type FixedAsset = {
    id: number;
    business_id: number;
    asset_account_id: number;
    accumulated_depreciation_account_id: number;
    depreciation_expense_account_id: number;
    name: string;
    description: string | null;
    asset_tag: string | null;
    purchase_date: string;
    purchase_cost: string;
    salvage_value: string;
    useful_life_months: number;
    depreciation_method: DepreciationMethod;
    status: AssetStatus;
    disposal_date: string | null;
    disposal_proceeds: string | null;
    notes: string | null;
    created_by: number;
    asset_account?: Account;
    accumulated_depreciation_account?: Account;
    depreciation_expense_account?: Account;
    depreciation_entries?: DepreciationEntry[];
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

export type DepreciationEntry = {
    id: number;
    business_id: number;
    fixed_asset_id: number;
    journal_entry_id: number | null;
    period_start: string;
    period_end: string;
    depreciation_amount: string;
    created_by: number;
    fixed_asset?: FixedAsset;
    journal_entry?: JournalEntry;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

// ── Invoice ──────────────────────────────────────────────
export type InvoiceType = 'quote' | 'invoice' | 'credit_note' | 'purchase_invoice' | 'debit_note' | 'purchase_order';
export type InvoiceStatus =
    | 'draft'
    | 'sent'
    | 'accepted'
    | 'in_progress'
    | 'delivered'
    | 'approved'
    | 'paid'
    | 'partially_paid'
    | 'overdue'
    | 'void'
    | 'cancelled'
    | 'partially_received'
    | 'received'
    | 'invoiced';

export type Invoice = {
    id: number;
    business_id: number;
    contact_id: number;
    type: InvoiceType;
    number: string;
    date: string;
    due_date: string | null;
    reference: string | null;
    status: InvoiceStatus;
    subtotal: number;
    tax_amount: number;
    total: number;
    amount_paid: number;
    balance_due: number;
    notes: string | null;
    terms: string | null;
    currency_code: string;
    exchange_rate: number;
    journal_entry_id: number | null;
    contact?: Contact;
    lines?: InvoiceLine[];
    journal_entry?: JournalEntry;
    payment_allocations?: PaymentAllocation[];
    created_at: string;
    updated_at: string;
};

export type InvoiceLine = {
    id: number;
    invoice_id: number;
    account_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_code_id: number | null;
    tax_amount: number;
    line_total: number;
    sort_order: number;
    language_pair_id?: number | null;
    service_type_id?: number | null;
    billing_unit?: string | null;
    account?: Account;
    tax_code?: TaxCode;
    language_pair?: {
        id: number;
        source_language_id: number;
        target_language_id: number;
        sourceLanguage?: { name: string; code: string };
        targetLanguage?: { name: string; code: string };
    } | null;
    service_type?: { id: number; name: string; default_unit?: string } | null;
};

export type LanguagePairOption = {
    id: number;
    source_language_id: number;
    target_language_id: number;
    sourceLanguage?: { name: string; code: string };
    targetLanguage?: { name: string; code: string };
};

export type ServiceTypeOption = {
    id: number;
    name: string;
    default_unit?: string;
};

// ── Payment ──────────────────────────────────────────────
export type PaymentType = 'receipt' | 'payment';

export type Payment = {
    id: number;
    business_id: number;
    contact_id: number;
    type: PaymentType;
    number: string;
    date: string;
    amount: number;
    bank_account_id: number;
    reference: string | null;
    description: string | null;
    journal_entry_id: number | null;
    contact?: Contact;
    bank_account?: Account;
    journal_entry?: JournalEntry;
    allocations?: PaymentAllocation[];
    created_at: string;
    updated_at: string;
};

export type PaymentAllocation = {
    id: number;
    payment_id: number;
    invoice_id: number;
    amount: number;
    payment?: Payment;
    invoice?: Invoice;
};

// ── Banking ──────────────────────────────────────────────
export type BankTransaction = {
    id: number;
    business_id: number;
    bank_account_id: number;
    date: string;
    description: string;
    amount: number;
    reference: string | null;
    is_reconciled: boolean;
    reconciled_at: string | null;
    journal_entry_id: number | null;
    payment_id: number | null;
    bank_account?: Account;
    created_at: string;
    updated_at: string;
};

// ── Reports ──────────────────────────────────────────────
export type ReportAccountBalance = {
    id: number;
    code: string;
    name: string;
    balance: number;
};

export type ProfitAndLossReport = {
    period: { start: string; end: string };
    revenue: { accounts: ReportAccountBalance[]; total: number };
    expenses: { accounts: ReportAccountBalance[]; total: number };
    net_profit: number;
};

export type BalanceSheetReport = {
    as_of: string;
    assets: { accounts: ReportAccountBalance[]; total: number };
    liabilities: { accounts: ReportAccountBalance[]; total: number };
    equity: { accounts: ReportAccountBalance[]; total: number; retained_earnings: number };
    total_liabilities_and_equity: number;
};

export type TrialBalanceEntry = {
    account: Account;
    debit: number;
    credit: number;
};

// ── Dashboard ────────────────────────────────────────────
export type BankAccountSummary = {
    id: number;
    name: string;
    balance: number;
};

export type DashboardInvoice = {
    id: number;
    number: string;
    contact: string | null;
    date: string;
    total: number;
    balance_due: number;
    status: InvoiceStatus;
};

export type DashboardPayment = {
    id: number;
    number: string;
    contact: string | null;
    date: string;
    amount: number;
    type: PaymentType;
};

// ── Pagination ───────────────────────────────────────────
export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
} & PaginationMeta;

// ── Translation — Phase 2.1 ───────────────────────────────

export type BillingUnit = 'word' | 'hour' | 'page' | 'minute' | 'line' | 'character';

export type Language = {
    id: number;
    business_id: number;
    code: string;
    name: string;
    native_name: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type LanguageOption = Pick<Language, 'id' | 'code' | 'name'>;

export type LanguagePair = {
    id: number;
    business_id: number;
    source_language_id: number;
    target_language_id: number;
    is_active: boolean;
    source_language?: Language;
    target_language?: Language;
    created_at: string;
    updated_at: string;
};

export type ServiceType = {
    id: number;
    business_id: number;
    name: string;
    code: string;
    description: string | null;
    default_unit: BillingUnit;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

// ── Translation — Phase 2.2 ───────────────────────────────

export type RateCardType = 'default' | 'client' | 'translator';

export type RateCardVolumeTier = {
    id: number;
    rate_card_id: number;
    minimum_words: number;
    unit_rate: string;
    created_at: string;
    updated_at: string;
};

export type RateCard = {
    id: number;
    business_id: number;
    type: RateCardType;
    contact_id: number | null;
    language_pair_id: number;
    service_type_id: number;
    unit_rate: string;
    unit: BillingUnit;
    minimum_fee: string | null;
    rush_multiplier: string | null;
    rush_fixed_surcharge: string | null;
    notes: string | null;
    is_active: boolean;
    contact?: { id: number; name: string; type: string };
    language_pair?: LanguagePair;
    service_type?: ServiceType;
    volume_tiers?: RateCardVolumeTier[];
    created_at: string;
    updated_at: string;
};

// ── Translation — Phase 2.3 ───────────────────────────────

export type ProjectStatus = 'new' | 'in_progress' | 'review' | 'completed' | 'delivered' | 'invoiced' | 'closed';
export type ProjectAssignmentRole = 'translator' | 'editor' | 'proofreader' | 'dtp';
export type ProjectFileType = 'source' | 'deliverable' | 'reference';

export type ProjectAssignment = {
    id: number;
    project_target_id: number;
    contact_id: number;
    role: ProjectAssignmentRole;
    rate: string | null;
    purchase_order_id: number | null;
    contact?: { id: number; name: string };
    purchase_order?: { id: number; number: string; status: string } | null;
    created_at: string;
    updated_at: string;
};

export type ProjectTarget = {
    id: number;
    project_id: number;
    language_pair_id: number;
    service_type_id: number | null;
    word_count: number | null;
    unit_price: string | null;
    language_pair?: LanguagePair;
    service_type?: ServiceType | null;
    assignments?: ProjectAssignment[];
    cat_analyses?: CatAnalysis[];
    created_at: string;
    updated_at: string;
};

export type ProjectFile = {
    id: number;
    project_id: number;
    name: string;
    path: string;
    type: ProjectFileType;
    size: number;
    mime_type: string;
    created_at: string;
    updated_at: string;
};

// ── Translation — Phase 2.4 ───────────────────────────────

export type CatTool = 'manual' | 'trados' | 'memoq' | 'phrase' | 'wordfast' | 'deja_vu' | 'xtm' | 'crowdin' | 'smartcat' | 'transifex';
export type CatMatchBand = 'context_match' | 'exact_match' | 'fuzzy_95_99' | 'fuzzy_85_94' | 'fuzzy_75_84' | 'fuzzy_50_74' | 'no_match' | 'repetitions';

export type CatAnalysisBand = {
    id: number;
    cat_analysis_id: number;
    band: CatMatchBand;
    words: number;
    discount_percent: string;
    created_at: string;
    updated_at: string;
};

export type CatAnalysis = {
    id: number;
    business_id: number;
    project_target_id: number;
    name: string;
    tool: CatTool;
    bands?: CatAnalysisBand[];
    created_at: string;
    updated_at: string;
};

// ── Translation — Phase 2.5 ───────────────────────────────

export type TranslatorAvailability = 'available' | 'busy' | 'on_leave';
export type TranslatorSpecialisation =
    | 'legal'
    | 'medical'
    | 'technical'
    | 'marketing'
    | 'financial'
    | 'it'
    | 'life_sciences'
    | 'literary'
    | 'gaming'
    | 'general';
export type TranslatorCertification = 'iso_17100' | 'ata' | 'naati' | 'iti' | 'ciol' | 'fit' | 'dip_trans';

export type TranslatorProfile = {
    id: number;
    business_id: number;
    contact_id: number;
    availability: TranslatorAvailability;
    quality_rating: number | null;
    quality_notes: string | null;
    specialisations: TranslatorSpecialisation[];
    cat_tools: CatTool[];
    certifications: TranslatorCertification[];
    contact?: { id: number; name: string; email: string | null; phone: string | null };
    language_pairs?: LanguagePair[];
    service_types?: ServiceType[];
    created_at: string;
    updated_at: string;
};

export type Project = {
    id: number;
    business_id: number;
    contact_id: number;
    source_language_id: number;
    service_type_id: number;
    name: string;
    reference: string | null;
    deadline: string | null;
    notes: string | null;
    status: ProjectStatus;
    quote_id: number | null;
    invoice_id: number | null;
    contact?: { id: number; name: string };
    source_language?: Language;
    service_type?: ServiceType;
    targets?: ProjectTarget[];
    files?: ProjectFile[];
    quote?: { id: number; number: string; status: string } | null;
    invoice?: { id: number; number: string; status: string } | null;
    created_at: string;
    updated_at: string;
};
