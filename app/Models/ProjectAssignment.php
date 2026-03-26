<?php

namespace App\Models;

use App\Domain\Translation\Enums\ProjectAssignmentRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_target_id',
        'contact_id',
        'role',
        'rate',
        'purchase_order_id',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProjectAssignmentRole::class,
            'rate' => 'decimal:4',
        ];
    }

    public function projectTarget(): BelongsTo
    {
        return $this->belongsTo(ProjectTarget::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'purchase_order_id');
    }
}
