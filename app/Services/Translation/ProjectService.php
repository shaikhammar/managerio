<?php

namespace App\Services\Translation;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Domain\Translation\Enums\ProjectStatus;
use App\Events\ProjectMovedToInProgress;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectFile;
use App\Models\ProjectTarget;
use App\Models\RateCard;
use App\Services\Accounting\NumberSequenceService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function __construct(
        private NumberSequenceService $numberSequence,
    ) {}

    public function create(Business $business, array $data): Project
    {
        return DB::transaction(function () use ($business, $data) {
            $reference = $this->numberSequence->getNext($business, 'project');

            $project = Project::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'source_language_id' => $data['source_language_id'],
                'service_type_id' => $data['service_type_id'],
                'name' => $data['name'],
                'reference' => $reference,
                'deadline' => $data['deadline'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => ProjectStatus::NEW,
            ]);

            foreach ($data['targets'] ?? [] as $targetData) {
                $this->syncTarget($project, $targetData);
            }

            return $project->load(['contact', 'sourceLanguage', 'serviceType', 'targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType', 'targets.assignments.contact']);
        });
    }

    public function update(Project $project, array $data): Project
    {
        if (! $project->isEditable()) {
            throw new DomainException('Closed projects cannot be edited.');
        }

        return DB::transaction(function () use ($project, $data) {
            $project->update([
                'contact_id' => $data['contact_id'] ?? $project->contact_id,
                'source_language_id' => $data['source_language_id'] ?? $project->source_language_id,
                'service_type_id' => $data['service_type_id'] ?? $project->service_type_id,
                'name' => $data['name'] ?? $project->name,
                'deadline' => array_key_exists('deadline', $data) ? $data['deadline'] : $project->deadline,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $project->notes,
            ]);

            if (isset($data['targets'])) {
                $incomingIds = collect($data['targets'])->pluck('id')->filter()->all();

                $project->targets()->whereNotIn('id', $incomingIds)->each(function (ProjectTarget $target) {
                    $target->assignments()->delete();
                    $target->delete();
                });

                foreach ($data['targets'] as $targetData) {
                    $this->syncTarget($project, $targetData);
                }
            }

            return $project->fresh(['contact', 'sourceLanguage', 'serviceType', 'targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType', 'targets.assignments.contact']);
        });
    }

    public function updateStatus(Project $project, ProjectStatus $newStatus): Project
    {
        if (! $project->status->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Cannot transition project from {$project->status->label()} to {$newStatus->label()}."
            );
        }

        DB::transaction(function () use ($project, $newStatus): void {
            $project->update(['status' => $newStatus]);

            if ($newStatus === ProjectStatus::IN_PROGRESS) {
                ProjectMovedToInProgress::dispatch($project);
            }
        });

        return $project->fresh();
    }

    public function generateQuote(Project $project): Invoice
    {
        if (! $project->canGenerateQuote()) {
            throw new DomainException('A quote has already been generated for this project.');
        }

        $project->loadMissing(['contact', 'targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType']);

        $business = $project->business;

        return DB::transaction(function () use ($project, $business) {
            $quote = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $project->contact_id,
                'type' => InvoiceType::QUOTE,
                'number' => $this->numberSequence->getNext($business, 'quote'),
                'date' => now()->format('Y-m-d'),
                'due_date' => $project->deadline?->format('Y-m-d'),
                'reference' => $project->reference,
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $business->currency_code,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'notes' => $project->notes,
            ]);

            $sortOrder = 0;
            $subtotal = '0.00';

            foreach ($project->targets as $target) {
                $pair = $target->languagePair;
                $serviceType = $target->serviceType ?? $project->serviceType;
                $description = sprintf(
                    '%s → %s (%s)',
                    $pair->sourceLanguage->name,
                    $pair->targetLanguage->name,
                    $serviceType?->name ?? 'Translation'
                );

                $unitPrice = $target->unit_price ?? $this->resolveClientRate($project, $target);
                $wordCount = $target->word_count ?? 1;

                $lineTotal = bcmul((string) $wordCount, (string) ($unitPrice ?? '0'), 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $quote->lines()->create([
                    'description' => $description,
                    'quantity' => $wordCount,
                    'unit_price' => $unitPrice ?? '0.0000',
                    'discount_percent' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $quote->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'balance_due' => $subtotal,
            ]);

            $project->update(['quote_id' => $quote->id]);

            return $quote;
        });
    }

    public function generateInvoice(Project $project): Invoice
    {
        if (! $project->canGenerateInvoice()) {
            throw new DomainException('An invoice cannot be generated for this project in its current state.');
        }

        $project->loadMissing(['contact', 'targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType']);

        $business = $project->business;

        return DB::transaction(function () use ($project, $business) {
            $invoice = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $project->contact_id,
                'type' => InvoiceType::INVOICE,
                'number' => $this->numberSequence->getNext($business, 'invoice'),
                'date' => now()->format('Y-m-d'),
                'reference' => $project->reference,
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $business->currency_code,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'notes' => $project->notes,
            ]);

            $sortOrder = 0;
            $subtotal = '0.00';

            foreach ($project->targets as $target) {
                $pair = $target->languagePair;
                $serviceType = $target->serviceType ?? $project->serviceType;
                $description = sprintf(
                    '%s → %s (%s)',
                    $pair->sourceLanguage->name,
                    $pair->targetLanguage->name,
                    $serviceType?->name ?? 'Translation'
                );

                $unitPrice = $target->unit_price ?? $this->resolveClientRate($project, $target);
                $wordCount = $target->word_count ?? 1;

                $lineTotal = bcmul((string) $wordCount, (string) ($unitPrice ?? '0'), 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $invoice->lines()->create([
                    'description' => $description,
                    'quantity' => $wordCount,
                    'unit_price' => $unitPrice ?? '0.0000',
                    'discount_percent' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'balance_due' => $subtotal,
            ]);

            $project->update([
                'invoice_id' => $invoice->id,
                'status' => ProjectStatus::INVOICED,
            ]);

            return $invoice;
        });
    }

    /** @return Collection<int, Invoice> */
    public function generatePurchaseOrders(Project $project): Collection
    {
        $project->loadMissing([
            'targets.languagePair.sourceLanguage',
            'targets.languagePair.targetLanguage',
            'targets.serviceType',
            'targets.assignments.contact',
        ]);

        $business = $project->business;
        $orders = collect();

        DB::transaction(function () use ($project, $business, $orders) {
            foreach ($project->targets as $target) {
                foreach ($target->assignments as $assignment) {
                    if ($assignment->purchase_order_id !== null) {
                        continue;
                    }

                    $pair = $target->languagePair;
                    $serviceType = $target->serviceType ?? $project->serviceType;
                    $description = sprintf(
                        '%s → %s (%s) — %s',
                        $pair->sourceLanguage->name,
                        $pair->targetLanguage->name,
                        $serviceType?->name ?? 'Translation',
                        $assignment->role->label()
                    );

                    $rate = $assignment->rate ?? $this->resolveTranslatorRate($assignment, $target);
                    $wordCount = $target->word_count ?? 1;
                    $lineTotal = $rate ? bcmul((string) $wordCount, (string) $rate, 2) : '0.00';

                    $po = Invoice::withoutGlobalScopes()->create([
                        'business_id' => $business->id,
                        'contact_id' => $assignment->contact_id,
                        'type' => InvoiceType::PURCHASE_ORDER,
                        'number' => $this->numberSequence->getNext($business, 'purchase_order'),
                        'date' => now()->format('Y-m-d'),
                        'due_date' => $project->deadline?->format('Y-m-d'),
                        'reference' => $project->reference,
                        'status' => InvoiceStatus::DRAFT,
                        'currency_code' => $business->currency_code,
                        'subtotal' => $lineTotal,
                        'tax_amount' => 0,
                        'total' => $lineTotal,
                        'amount_paid' => 0,
                        'balance_due' => $lineTotal,
                        'notes' => $project->notes,
                    ]);

                    $po->lines()->create([
                        'description' => $description,
                        'quantity' => $wordCount,
                        'unit_price' => $rate ?? '0.0000',
                        'discount_percent' => 0,
                        'tax_amount' => 0,
                        'line_total' => $lineTotal,
                        'sort_order' => 0,
                    ]);

                    $assignment->update(['purchase_order_id' => $po->id]);
                    $orders->push($po);
                }
            }
        });

        return $orders;
    }

    public function storeFile(Project $project, UploadedFile $file, string $type): ProjectFile
    {
        $path = $file->store("project-files/{$project->id}", 'local');

        return $project->files()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $type,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
        ]);
    }

    public function deleteFile(ProjectFile $projectFile): void
    {
        Storage::disk('local')->delete($projectFile->path);
        $projectFile->delete();
    }

    private function syncTarget(Project $project, array $data): ProjectTarget
    {
        $target = isset($data['id'])
            ? ProjectTarget::find($data['id'])
            : null;

        if ($target && $target->project_id === $project->id) {
            $target->update([
                'language_pair_id' => $data['language_pair_id'],
                'service_type_id' => $data['service_type_id'] ?? null,
                'word_count' => $data['word_count'] ?? null,
                'unit_price' => $data['unit_price'] ?? null,
            ]);
        } else {
            $target = $project->targets()->create([
                'language_pair_id' => $data['language_pair_id'],
                'service_type_id' => $data['service_type_id'] ?? null,
                'word_count' => $data['word_count'] ?? null,
                'unit_price' => $data['unit_price'] ?? null,
            ]);
        }

        $incomingAssignmentIds = collect($data['assignments'] ?? [])->pluck('id')->filter()->all();
        $target->assignments()->whereNotIn('id', $incomingAssignmentIds)->delete();

        foreach ($data['assignments'] ?? [] as $assignmentData) {
            if (isset($assignmentData['id'])) {
                $target->assignments()->where('id', $assignmentData['id'])->update([
                    'contact_id' => $assignmentData['contact_id'],
                    'role' => $assignmentData['role'],
                    'rate' => $assignmentData['rate'] ?? null,
                ]);
            } else {
                $target->assignments()->create([
                    'contact_id' => $assignmentData['contact_id'],
                    'role' => $assignmentData['role'],
                    'rate' => $assignmentData['rate'] ?? null,
                ]);
            }
        }

        return $target->fresh('assignments');
    }

    private function resolveClientRate(Project $project, ProjectTarget $target): ?string
    {
        $serviceTypeId = $target->service_type_id ?? $project->service_type_id;

        $rateCard = RateCard::query()
            ->where('language_pair_id', $target->language_pair_id)
            ->where('service_type_id', $serviceTypeId)
            ->where('is_active', true)
            ->where(function ($q) use ($project) {
                $q->where('contact_id', $project->contact_id)
                    ->orWhereNull('contact_id');
            })
            ->orderByRaw('contact_id IS NULL ASC')
            ->first();

        return $rateCard?->unit_rate;
    }

    private function resolveTranslatorRate(ProjectAssignment $assignment, ProjectTarget $target): ?string
    {
        $project = $target->project;
        $serviceTypeId = $target->service_type_id ?? $project->service_type_id;

        $rateCard = RateCard::query()
            ->where('type', 'translator')
            ->where('contact_id', $assignment->contact_id)
            ->where('language_pair_id', $target->language_pair_id)
            ->where('service_type_id', $serviceTypeId)
            ->where('is_active', true)
            ->first();

        return $rateCard?->unit_rate;
    }
}
