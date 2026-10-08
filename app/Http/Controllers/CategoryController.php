<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        return view('categories.index', [
            'categories' => Category::visibleTo($request->user()->company_id)
                ->withCount('specFields')
                ->orderByRaw('company_id IS NULL DESC')
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        DB::transaction(function () use ($request) {
            $category = Category::create([
                'company_id' => $request->user()->company_id,
                'name' => $request->validated('name'),
                'slug' => $this->uniqueSlug($request->user()->company_id, $request->validated('name')),
                'icon' => $request->validated('icon'),
            ]);
            $this->syncFields($category, $request->validated('fields') ?? []);
        });

        return redirect()->route('categories.index')->with('success', __('Category created.'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.form', ['category' => $category->load('specFields')]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        DB::transaction(function () use ($request, $category) {
            $category->update($request->safe()->only(['name', 'icon']));
            $this->syncFields($category, $request->validated('fields') ?? []);
        });

        return redirect()->route('categories.index')->with('success', __('Category updated. Existing products keep the specs they were saved with.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', __('This category is used by products and cannot be deleted.'));
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', __('Category deleted.'));
    }

    /**
     * Upsert fields by key. Products keep their copied spec labels, so editing
     * or removing a field never changes saved products.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(Category $category, array $fields): void
    {
        $keys = [];

        foreach (array_values($fields) as $order => $field) {
            $key = Str::limit(Str::snake(preg_replace('/[^A-Za-z0-9 ]/', ' ', $field['label'])), 50, '') ?: 'field_'.$order;
            while (in_array($key, $keys, true)) {
                $key .= '_'.$order;
            }
            $keys[] = $key;

            $category->specFields()->updateOrCreate(['key' => $key], [
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['type'] === 'select' ? $field['options'] : null,
                'unit' => $field['unit'] ?? null,
                'is_required' => $field['is_required'],
                'show_on_label' => $field['show_on_label'],
                'sort_order' => $order + 1,
            ]);
        }

        $category->specFields()->whereNotIn('key', $keys)->delete();
    }

    private function uniqueSlug(int $companyId, string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
