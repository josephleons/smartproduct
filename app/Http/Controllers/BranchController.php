<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        return view('branches.index', [
            'branches' => Branch::query()->withCount(['products', 'users'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Branch::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'branches', 'branches.index')) {
            return $limited;
        }

        return view('branches.form', ['branch' => new Branch(['is_active' => true])]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'branches', 'branches.index')) {
            return $limited;
        }

        Branch::create($request->validated());

        return redirect()->route('branches.index')->with('success', __('Branch created.'));
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.form', ['branch' => $branch]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch->update($request->validated());

        return redirect()->route('branches.index')->with('success', __('Branch updated.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        if ($branch->products()->withTrashed()->exists()) {
            return back()->with('error', __('This branch has products. Deactivate it instead of deleting it.'));
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', __('Branch deleted.'));
    }
}
