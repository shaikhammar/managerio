<?php

namespace App\Models;

use App\Domain\Translation\Enums\ProjectFileType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'path',
        'type',
        'size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectFileType::class,
            'size' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }

    public function formattedSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
