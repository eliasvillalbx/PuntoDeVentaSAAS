<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CalendarEvent extends Model
{
    protected $table = 'calendar_events';

    protected $fillable = [
        'empresa_id',
        'user_id',    // puedes usarlo como responsable
        'created_by', // quien lo creó
        'title',
        'description',
        'start',
        'end',
        'all_day',
        'color',
    ];

    protected $casts = [
        'start'   => 'datetime',
        'end'     => 'datetime',
        'all_day' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuarios asignados al evento (muchos a muchos)
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_user')
            ->withTimestamps();
    }
}
