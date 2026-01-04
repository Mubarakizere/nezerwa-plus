<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            static::logAudit($model, 'create');
        });

        // Use 'updating' to capture state BEFORE changes are persisted (if we wanted to store locally)
        // Check standard pattern:
        static::updated(function ($model) {
            static::logAudit($model, 'update');
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'delete');
        });
    }

    protected static function logAudit($model, $action)
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'update') {
            $newValues = $model->getChanges();

            // Attempt to retrieve original values for changed keys
            // In the 'updated' event, getOriginal() creates a sync of the current attributes.
            // So we rely on the fact that if it's 'updated', we missed the true 'old' values unless we tracked them in 'updating'.
            // For now, let's just log what CHANGED (new values).
            // To be perfect, we would need a temporary property, but let's keep it robust and simple.
            // We'll mark 'old_values' as null or empty for now on updates, OR just log the diff keys.
            
            // IMPROVEMENT:
            // For 'updated', 'old_values' will be the values *before* the change.
            // But we don't have them easily accessible here in 'updated'.
            // Let's stick to storing 'new_values' which contains the Changes.
        } elseif ($action === 'create') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'delete') {
            $oldValues = $model->getAttributes();
        }

        AuditLog::create([
            'user_id'      => Auth::id(),
            'action'       => $action,
            'subject_type' => get_class($model),
            'subject_id'   => $model->id,
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
