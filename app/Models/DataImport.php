<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImport extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'source',
        'total_rows', 'success_rows', 'failed_rows', 'duplicate_rows', 'errors',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
