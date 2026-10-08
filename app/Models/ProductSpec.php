<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['product_id', 'spec_field_id', 'key', 'label', 'value', 'unit', 'show_on_label', 'sort_order'];

    protected function casts(): array
    {
        return ['show_on_label' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function specField(): BelongsTo
    {
        return $this->belongsTo(CategorySpecField::class, 'spec_field_id');
    }

    public function displayValue(): string
    {
        return $this->unit ? $this->value.' '.$this->unit : $this->value;
    }
}
