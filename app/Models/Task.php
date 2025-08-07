<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'goal_id',
        'title',
        'description',
        'type',
        'status',
        'recurrence_type',
        'due_date',
        'completed_at',
        'last_reset_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'goal_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'last_reset_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the goal that owns the task.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the completions for the task.
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * Mark the task as completed.
     */
    public function markAsCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Record completion history
        $this->completions()->create([
            'completed_at' => now(),
            'notes' => $notes,
        ]);

        // Handle recurring tasks
        if ($this->type === 'recurring' && $this->recurrence_type) {
            $this->resetRecurringTask();
        }
    }

    /**
     * Reset recurring task based on recurrence type.
     */
    protected function resetRecurringTask(): void
    {
        $nextReset = match ($this->recurrence_type) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => null,
        };

        if ($nextReset) {
            $this->update([
                'status' => 'pending',
                'completed_at' => null,
                'last_reset_at' => now(),
            ]);
        }
    }

    /**
     * Check if task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date &&
               $this->status === 'pending' &&
               $this->due_date->isPast();
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date || $this->status === 'completed') {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }
}
