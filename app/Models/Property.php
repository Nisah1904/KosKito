<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_LABELS = [
        'pending' => 'Menunggu verifikasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'inactive' => 'Nonaktif',
    ];

    public const SOURCE_LABELS = [
        'owner' => 'Pendaftaran pemilik',
        'import' => 'Impor data',
        'admin' => 'Ditambahkan admin',
        'web' => 'Info publik dari web',
    ];

    public const TYPE_LABELS = [
        'putri' => 'Kost Putri',
        'putra' => 'Kost Putra',
        'campur' => 'Kost Campur',
        'unknown' => 'Tipe belum diketahui',
    ];

    public const FACILITY_OPTIONS = [
        'wifi',
        'ac',
        'kamar mandi dalam',
        'dapur',
        'parkir motor',
        'parkir mobil',
        'laundry',
        'meja belajar',
        'lemari',
        'cctv',
        'bebas 24 jam',
        'dekat masjid',
    ];

    protected $fillable = [
        'owner_id', 'name', 'price', 'location', 'address', 'campus_id', 'type',
        'image', 'facilities', 'description', 'rules', 'status', 'rooms',
        'available_rooms', 'latitude', 'longitude', 'electricity_included',
        'internet_included', 'electricity_fee', 'internet_fee', 'phone',
        'source', 'source_url', 'is_basic_info', 'is_demo', 'data_verified_at', 'verified_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'price' => 'integer',
            'rooms' => 'integer',
            'available_rooms' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'electricity_included' => 'boolean',
            'internet_included' => 'boolean',
            'electricity_fee' => 'integer',
            'internet_fee' => 'integer',
            'is_demo' => 'boolean',
            'is_basic_info' => 'boolean',
            'data_verified_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function photos()
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('sort');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function primaryPhoto(): ?PropertyPhoto
    {
        return $this->photos->first();
    }

    public function monthlyCost(): int
    {
        $electricity = $this->electricity_included ? 0 : $this->electricity_fee;
        $internet = $this->internet_included ? 0 : $this->internet_fee;

        return $this->price + $electricity + $internet;
    }

    public function hasPrice(): bool
    {
        return $this->price > 0;
    }

    public function priceLabel(): string
    {
        return $this->hasPrice() ? 'Rp' . number_format($this->price, 0, ',', '.') : 'Harga belum tersedia';
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Kost';
    }

    public function roomsLabel(): string
    {
        if ($this->is_basic_info || $this->rooms === 0) {
            return 'Tanyakan ketersediaan kamar';
        }

        return $this->available_rooms > 0 ? $this->available_rooms . '/' . $this->rooms . ' kamar tersedia' : 'Kamar penuh';
    }

    public function canBeBooked(): bool
    {
        return !$this->is_basic_info && $this->hasPrice() && $this->available_rooms > 0 && $this->owner?->role === User::ROLE_OWNER;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->data_verified_at !== null;
    }
}
