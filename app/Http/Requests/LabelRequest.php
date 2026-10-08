<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1', 'max:200'],
            'products.*' => ['integer'],
            'template' => ['required', Rule::in(array_keys(config('labels.templates')))],
            'layout' => ['required', Rule::in(['single', 'sheet'])],
            'fields' => ['nullable', 'array'],
            'fields.*' => [Rule::in(config('labels.fields'))],
            'copies' => ['nullable', 'integer', 'min:1', 'max:'.config('labels.max_copies')],
        ];
    }
}
