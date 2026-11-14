<?php

namespace App\Http\Requests;

use App\Services\ProductRules;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Upload of a bulk import file: one category and one branch per file. */
class ProductImportRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user();
        // Same branch rule as adding a product by hand (active, own company, own branch for restricted staff).
        $productRules = ProductRules::rules($user, collect());

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(
                fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('company_id')->orWhere('company_id', $user->company_id))
            )],
            'branch_id' => $productRules['branch_id'],
            'file' => ['required', 'file', 'extensions:xlsx,csv', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return ['category_id' => __('category'), 'branch_id' => __('branch'), 'file' => __('file')];
    }
}
