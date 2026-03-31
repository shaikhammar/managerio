<?php

namespace App\Services\Translation;

use App\Models\Business;
use App\Models\StyleGuide;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StyleGuideService
{
    public function create(Business $business, array $data, ?UploadedFile $file = null): StyleGuide
    {
        $fileData = $this->handleFileUpload($file);

        return StyleGuide::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'contact_id' => $data['contact_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            ...$fileData,
        ]);
    }

    public function update(StyleGuide $styleGuide, array $data, ?UploadedFile $file = null): StyleGuide
    {
        $fileData = [];

        if ($file !== null) {
            if ($styleGuide->file_path !== null) {
                Storage::disk('public')->delete($styleGuide->file_path);
            }

            $fileData = $this->handleFileUpload($file);
        }

        $styleGuide->update([
            'contact_id' => $data['contact_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            ...$fileData,
        ]);

        return $styleGuide->fresh();
    }

    public function delete(StyleGuide $styleGuide): void
    {
        if ($styleGuide->file_path !== null) {
            Storage::disk('public')->delete($styleGuide->file_path);
        }

        $styleGuide->delete();
    }

    /** @return array<string, mixed> */
    private function handleFileUpload(?UploadedFile $file): array
    {
        if ($file === null) {
            return [];
        }

        $path = $file->store('style-guides', 'public');

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}
