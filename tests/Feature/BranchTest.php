<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_managers_manage_branches(): void
    {
        $manager = $this->makeTenant('manager');

        $this->actingAs($manager)->get('/branches')->assertOk();
        $this->actingAs($manager)->post('/branches', ['name' => 'Mlimani City', 'phone' => '0754000111', 'is_active' => '1'])->assertRedirect('/branches');

        $branch = Branch::withoutGlobalScopes()->where('name', 'Mlimani City')->firstOrFail();
        $this->assertSame($manager->company_id, $branch->company_id);

        $this->actingAs($manager)->put("/branches/{$branch->id}", ['name' => 'Mlimani', 'is_active' => '0'])->assertRedirect();
        $this->assertFalse($branch->fresh()->is_active);

        $this->actingAs($manager)->delete("/branches/{$branch->id}")->assertRedirect();
        $this->assertModelMissing($branch);
    }

    public function test_branch_with_products_cannot_be_deleted(): void
    {
        $owner = $this->makeTenant();
        $branch = $this->firstBranch($owner);
        Product::factory()->for($branch)->create();

        $this->actingAs($owner)->delete("/branches/{$branch->id}")->assertSessionHas('error');
        $this->assertModelExists($branch);
    }

    public function test_staff_cannot_manage_branches(): void
    {
        $staff = $this->makeTenant('staff');

        $this->actingAs($staff)->get('/branches')->assertForbidden();
        $this->actingAs($staff)->post('/branches', ['name' => 'X'])->assertForbidden();
    }

    public function test_company_a_cannot_see_or_modify_company_b_branches(): void
    {
        $ownerA = $this->makeTenant();
        $ownerB = $this->makeTenant();
        $branchB = $this->firstBranch($ownerB);

        $this->actingAs($ownerA)->get('/branches')->assertDontSee($branchB->name);
        $this->actingAs($ownerA)->get("/branches/{$branchB->id}/edit")->assertNotFound();
        $this->actingAs($ownerA)->put("/branches/{$branchB->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->actingAs($ownerA)->delete("/branches/{$branchB->id}")->assertNotFound();
        $this->assertNotSame('Hacked', $branchB->fresh()->name);
    }
}
