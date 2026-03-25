<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\PurchaseOrderRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Sales\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $purchaseOrders = Invoice::query()
            ->purchaseOrders()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('purchases/purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('purchases/purchase-orders/create', [
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $business = $request->user()->currentBusiness();
        $purchaseOrder = $this->invoiceService->createPurchaseOrder($business, $request->validated());

        return redirect()->route('purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(Invoice $purchaseOrder): InertiaResponse|RedirectResponse
    {
        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        return Inertia::render('purchases/purchase-orders/show', [
            'purchaseOrder' => $purchaseOrder->load(['lines.account', 'lines.taxCode', 'contact', 'purchaseInvoices']),
        ]);
    }

    public function edit(Invoice $purchaseOrder): InertiaResponse|RedirectResponse
    {
        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        return Inertia::render('purchases/purchase-orders/create', [
            'purchaseOrder' => $purchaseOrder->load('lines'),
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(PurchaseOrderRequest $request, Invoice $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        $this->invoiceService->update($purchaseOrder, $request->validated());

        return redirect()->route('purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function convert(Invoice $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        $purchaseInvoice = $this->invoiceService->convertPurchaseOrderToInvoice($purchaseOrder);

        return redirect()->route('purchases.purchase-invoices.show', $purchaseInvoice)
            ->with('success', "Purchase Order #{$purchaseOrder->number} converted to Purchase Invoice #{$purchaseInvoice->number}.");
    }

    public function send(Invoice $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        $this->invoiceService->sendPurchaseOrder($purchaseOrder);

        return redirect()->route('purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order marked as sent.');
    }

    public function pdf(Invoice $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['lines.account', 'lines.taxCode', 'contact']);
        $business = $purchaseOrder->business;

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $purchaseOrder, 'business' => $business]);

        return $pdf->download("{$purchaseOrder->number}.pdf");
    }

    public function destroy(Invoice $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);

        if (! $purchaseOrder->isPurchaseOrder()) {
            abort(404);
        }

        $purchaseOrder->lines()->delete();
        $purchaseOrder->delete();

        return redirect()->route('purchases.purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }
}
