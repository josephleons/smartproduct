<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Users are not tenant-scoped by a global scope, so every query goes through the company relation. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => $request->user()->company->users()->with('branch')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'users', 'users.index')) {
            return $limited;
        }

        return view('users.form', ['user' => new User(['role' => UserRole::Staff]), ...$this->options()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($limited = $this->planLimitResponse($request->user()->company, 'users', 'users.index')) {
            return $limited;
        }

        $request->user()->company->users()->create($request->validated());

        return redirect()->route('users.index')->with('success', __('User added.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.form', ['user' => $user, ...$this->options()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // An owner cannot demote themselves and lock the company out of its settings.
        if ($user->is($request->user())) {
            $data['role'] = UserRole::Owner;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', __('User updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', __('User removed.'));
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'roles' => UserRole::companyRoles(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ];
    }
}
