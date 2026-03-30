<?php

namespace App\Services\Translation;

use App\Models\Business;
use App\Models\TermBase;

class TermBaseService
{
    public function create(Business $business, array $data): TermBase
    {
        return TermBase::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'contact_id' => $data['contact_id'] ?? null,
            'name' => $data['name'],
            'subject_field' => $data['subject_field'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(TermBase $termBase, array $data): TermBase
    {
        $termBase->update([
            'contact_id' => $data['contact_id'] ?? null,
            'name' => $data['name'],
            'subject_field' => $data['subject_field'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $termBase->fresh();
    }

    public function delete(TermBase $termBase): void
    {
        $termBase->delete();
    }
}
