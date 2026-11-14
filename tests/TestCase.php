<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views are tested without the compiled Vite bundle, so a page script that has
        // not been built yet (npm run build) does not break feature tests.
        $this->withoutVite();
    }

    /** A company with one branch and a user of the given role (default owner). */
    protected function makeTenant(string $role = 'owner', bool $branchRestricted = false): User
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();

        return User::factory()->for($company)->create([
            'role' => $role,
            'branch_id' => $branchRestricted ? $branch->id : null,
        ]);
    }

    protected function firstBranch(User $user): Branch
    {
        return Branch::withoutGlobalScopes()->where('company_id', $user->company_id)->firstOrFail();
    }

    protected function laptopCategory(): Category
    {
        if (! Category::whereNull('company_id')->where('slug', 'laptop')->exists()) {
            $this->seed(CategorySeeder::class);
        }

        return Category::whereNull('company_id')->where('slug', 'laptop')->firstOrFail();
    }
}
