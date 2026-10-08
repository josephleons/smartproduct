<?php

namespace App\Models;

use App\Enums\SpecFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorySpecField extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['category_id', 'key', 'label', 'type', 'options', 'unit', 'is_required', 'show_on_label', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => SpecFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'show_on_label' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
