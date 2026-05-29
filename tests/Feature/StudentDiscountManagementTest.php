<?php

namespace Tests\Feature;

use App\Models\DiscountDefinition;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDiscountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_student_discount_pages(): void
    {
        $this->get(route('student-discounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_student_discount_list(): void
    {
        $user = User::factory()->create();
        StudentDiscount::factory()->create();

        $response = $this->actingAs($user)->get(route('student-discounts.index'));

        $response->assertOk();
        $response->assertSee('Student Discounts');
    }

    public function test_student_discount_create_page_shows_type_to_search_student_helper(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('student-discounts.create'));

        $response->assertOk();
        $response->assertSee('Type student name or admission no');
    }

    public function test_authenticated_users_can_assign_a_student_discount(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $discountDefinition = DiscountDefinition::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post(route('student-discounts.store'), [
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Sibling scholarship',
        ]);

        $response->assertRedirect(route('student-discounts.index'));

        $this->assertDatabaseHas('student_discounts', [
            'student_id' => $student->id,
            'discount_definition_id' => $discountDefinition->id,
            'status' => 'active',
        ]);
    }

    public function test_inactive_discount_definition_cannot_be_assigned(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $discountDefinition = DiscountDefinition::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)
            ->from(route('student-discounts.create'))
            ->post(route('student-discounts.store'), [
                'student_id' => $student->id,
                'discount_definition_id' => $discountDefinition->id,
                'start_date' => '2026-05-01',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('student-discounts.create'));
        $response->assertSessionHasErrors('discount_definition_id');
    }

    public function test_authenticated_users_can_update_a_student_discount(): void
    {
        $user = User::factory()->create();
        $studentDiscount = StudentDiscount::factory()->create([
            'discount_definition_id' => DiscountDefinition::factory()->create(['status' => 'active'])->id,
            'status' => 'inactive',
            'end_date' => null,
        ]);

        $response = $this->actingAs($user)->patch(route('student-discounts.update', $studentDiscount), [
            'student_id' => $studentDiscount->student_id,
            'discount_definition_id' => $studentDiscount->discount_definition_id,
            'start_date' => $studentDiscount->start_date?->format('Y-m-d'),
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Reactivated',
        ]);

        $response->assertRedirect(route('student-discounts.index'));

        $this->assertDatabaseHas('student_discounts', [
            'id' => $studentDiscount->id,
            'status' => 'active',
            'notes' => 'Reactivated',
        ]);
    }

    public function test_authenticated_users_can_deactivate_a_student_discount(): void
    {
        $user = User::factory()->create();
        $studentDiscount = StudentDiscount::factory()->create();

        $response = $this->actingAs($user)->delete(route('student-discounts.destroy', $studentDiscount));

        $response->assertRedirect(route('student-discounts.index'));
        $this->assertDatabaseHas('student_discounts', [
            'id' => $studentDiscount->id,
            'status' => 'inactive',
        ]);
    }
}
