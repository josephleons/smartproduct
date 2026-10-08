<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_updates_company_profile(): void
    {
        $owner = $this->makeTenant();

        $this->actingAs($owner)->get('/settings/company')->assertOk();
        $this->actingAs($owner)->put('/settings/company', ['name' => 'New Name Ltd', 'whatsapp' => '0712000000'])->assertRedirect();

        $this->assertSame('New Name Ltd', $owner->company->fresh()->name);
    }

    public function test_managers_and_staff_cannot_edit_company_or_users(): void
    {
        $manager = $this->makeTenant('manager');

        $this->actingAs($manager)->get('/settings/company')->assertForbidden();
        $this->actingAs($manager)->get('/settings/users')->assertForbidden();
    }

    public function test_owner_manages_users(): void
    {
        $owner = $this->makeTenant();
        $branch = $this->firstBranch($owner);

        $this->actingAs($owner)->post('/settings/users', [
            'name' => 'Neema Paul',
            'email' => 'neema@example.com',
            'role' => 'staff',
            'branch_id' => $branch->id,
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect('/settings/users');

        $staff = User::where('email', 'neema@example.com')->firstOrFail();
        $this->assertSame($owner->company_id, $staff->company_id);
        $this->assertSame(UserRole::Staff, $staff->role);

        $this->actingAs($owner)->delete("/settings/users/{$staff->id}")->assertRedirect();
        $this->assertModelMissing($staff);

        // Owners cannot delete themselves.
        $this->actingAs($owner)->delete("/settings/users/{$owner->id}")->assertForbidden();
    }

    public function test_company_a_cannot_see_or_modify_company_b_users(): void
    {
        $ownerA = $this->makeTenant();
        $ownerB = $this->makeTenant();

        $this->actingAs($ownerA)->get('/settings/users')->assertDontSee($ownerB->email);
        $this->actingAs($ownerA)->get("/settings/users/{$ownerB->id}/edit")->assertForbidden();
        $this->actingAs($ownerA)->delete("/settings/users/{$ownerB->id}")->assertForbidden();
        $this->actingAs($ownerA)->post('/settings/users', [
            'name' => 'X', 'email' => 'x@example.com', 'role' => 'staff',
            'branch_id' => $this->firstBranch($ownerB)->id,
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_custom_categories_and_spec_templates(): void
    {
        $manager = $this->makeTenant('manager');

        $this->actingAs($manager)->post('/settings/categories', [
            'name' => 'Tablet',
            'fields' => [
                ['label' => 'Storage', 'type' => 'number', 'unit' => 'GB', 'options' => '', 'is_required' => '1', 'show_on_label' => '1'],
                ['label' => 'Network', 'type' => 'select', 'options' => 'Wi-Fi, 4G', 'is_required' => '0', 'show_on_label' => '0'],
            ],
        ])->assertRedirect('/settings/categories');

        $category = Category::where('company_id', $manager->company_id)->where('name', 'Tablet')->firstOrFail();
        $this->assertSame(['storage', 'network'], $category->specFields->pluck('key')->all());
        $this->assertSame(['Wi-Fi', '4G'], $category->specFields[1]->options);

        $this->actingAs($manager)->getJson("/categories/{$category->id}/fields")
            ->assertOk()
            ->assertJsonPath('fields.0.key', 'storage')
            ->assertJsonPath('fields.0.required', true);

        // Dropdown fields need options.
        $this->actingAs($manager)->post('/settings/categories', [
            'name' => 'Bad', 'fields' => [['label' => 'Colour', 'type' => 'select', 'options' => '']],
        ])->assertSessionHasErrors('fields.0.options');
    }

    public function test_system_categories_are_read_only_and_other_companies_categories_are_hidden(): void
    {
        $managerA = $this->makeTenant('manager');
        $managerB = $this->makeTenant('manager');
        $laptop = $this->laptopCategory();
        $categoryB = Category::factory()->create(['company_id' => $managerB->company_id, 'name' => 'Hidden Cat']);

        $this->actingAs($managerA)->get("/settings/categories/{$laptop->id}/edit")->assertForbidden();
        $this->actingAs($managerA)->getJson("/categories/{$laptop->id}/fields")->assertOk();

        $this->actingAs($managerA)->get('/settings/categories')->assertDontSee('Hidden Cat');
        $this->actingAs($managerA)->getJson("/categories/{$categoryB->id}/fields")->assertForbidden();
        $this->actingAs($managerA)->delete("/settings/categories/{$categoryB->id}")->assertForbidden();
    }
}
