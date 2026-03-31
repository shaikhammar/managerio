<?php

namespace App\Services\Translation;

use App\Models\Business;
use App\Models\TranslationMemory;

class TranslationMemoryService
{
    public function create(Business $business, array $data): TranslationMemory
    {
        return TranslationMemory::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'contact_id' => $data['contact_id'] ?? null,
            'source_language_id' => $data['source_language_id'],
            'target_language_id' => $data['target_language_id'],
            'name' => $data['name'],
            'software' => $data['software'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(TranslationMemory $translationMemory, array $data): TranslationMemory
    {
        $translationMemory->update([
            'contact_id' => $data['contact_id'] ?? null,
            'source_language_id' => $data['source_language_id'],
            'target_language_id' => $data['target_language_id'],
            'name' => $data['name'],
            'software' => $data['software'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $translationMemory->fresh();
    }

    public function delete(TranslationMemory $translationMemory): void
    {
        $translationMemory->delete();
    }
}
