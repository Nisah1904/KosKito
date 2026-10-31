<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LABELS = [
        'pending' => 'Menunggu konfirmasi',
        'confirmed' => 'Diterima',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];

    protected $fillable = [
        'property_id', 'tenant_id', 'duration', 'start_date', 'note', 'status',
        'code', 'rejection_reason', 'responded_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'responded_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function label(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
