<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'token', 'scans_count', 'last_scanned_at'];

    protected function casts(): array
    {
        return [
            'scans_count' => 'integer',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The only thing a printed QR encodes. Never change this format. */
    public function url(): string
    {
        return config('app.public_url').'/p/'.$this->token;
    }
}
