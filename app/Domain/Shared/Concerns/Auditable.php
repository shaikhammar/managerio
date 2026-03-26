<?php

namespace App\Domain\Shared\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model) {
            $model->recordAudit('created', [], $model->getAuditableAttributes());
        });

        static::updated(function (self $model) {
            $dirty = $model->getDirty();
            $excluded = $model->getAuditExcluded();
            $changed = array_diff_key($dirty, array_flip($excluded));

            if (empty($changed)) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $changed);
            $model->recordAudit('updated', $old, array_intersect_key($model->getAttributes(), $changed));
        });

        static::deleted(function (self $model) {
            $model->recordAudit('deleted', $model->getAuditableAttributes(), []);
        });
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected function getAuditableAttributes(): array
    {
        $excluded = array_merge(['created_at', 'updated_at'], $this->getAuditExcluded());

        return array_diff_key($this->getAttributes(), array_flip($excluded));
    }

    protected function getAuditExcluded(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : ['created_at', 'updated_at'];
    }

    protected function getAuditLabel(): string
    {
        if (property_exists($this, 'auditLabel') && isset($this->{$this->auditLabel})) {
            return (string) $this->{$this->auditLabel};
        }

        return (string) $this->getKey();
    }

    private function recordAudit(string $event, array $oldValues, array $newValues): void
    {
        $businessId = $this->getAttribute('business_id');

        if (! $businessId) {
            return;
        }

        AuditLog::create([
            'business_id' => $businessId,
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'auditable_label' => $this->getAuditLabel(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
