<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PosProduct;
use App\Models\PosSale;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPosManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_cashier_can_topup_student_wallet(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('wallet-topups.store'), [
            'wallet_id' => $wallet->id,
            'amount' => 10000,
            'payment_method' => 'cash',
            'notes' => 'Initial top-up',
        ]);

        $response->assertRedirect(route('wallets.show', $wallet));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'current_balance' => 10000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_TOPUP,
            'amount' => 10000,
        ]);
    }

    public function test_pos_cashier_can_open_barcode_first_cashier_screen(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();

        $response = $this->actingAs($user)->get(route('pos-cashier.index', [
            'identifier' => $student->admission_no,
        ]));

        $response->assertOk();
        $response->assertSee('POS Cashier');
        $response->assertSee($student->admission_no);
    }

    public function test_pos_cashier_can_record_pos_sale_from_wallet_balance(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => 'active',
        ]);
        $product = PosProduct::query()->create([
            'name' => 'Noodle Cup',
            'sku' => 'POS-NOODLE',
            'price' => 2500,
            'stock_quantity' => 8,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('pos-sales.store'), [
            'wallet_id' => $wallet->id,
            'product_ids' => [$product->id],
            'quantities' => [2],
            'notes' => 'Break time purchase',
        ]);

        $sale = PosSale::query()->firstOrFail();

        $response->assertRedirect(route('pos-sales.show', $sale));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'current_balance' => 5000,
        ]);
        $this->assertDatabaseHas('pos_sales', [
            'id' => $sale->id,
            'total_amount' => 5000,
            'status' => PosSale::STATUS_POSTED,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_SALE,
            'amount' => 5000,
            'reference_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'stock_quantity' => 6,
        ]);
    }

    public function test_finance_manager_can_reverse_pos_sale(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'finance_manager');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => 'active',
        ]);
        $product = PosProduct::query()->create([
            'name' => 'Fruit Salad',
            'sku' => 'POS-FRUIT',
            'price' => 4000,
            'stock_quantity' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('pos-sales.store'), [
            'wallet_id' => $wallet->id,
            'product_ids' => [$product->id],
            'quantities' => [1],
            'notes' => 'Lunch purchase',
        ]);

        $sale = PosSale::query()->with('items')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('pos-sales.destroy', $sale));

        $response->assertRedirect(route('pos-sales.index'));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'current_balance' => 10000,
        ]);
        $this->assertDatabaseHas('pos_sales', [
            'id' => $sale->id,
            'status' => PosSale::STATUS_REVERSED,
        ]);
        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_REVERSAL,
            'amount' => 4000,
        ]);
    }

    public function test_finance_manager_can_reverse_wallet_topup(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'finance_manager');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('wallet-topups.store'), [
            'wallet_id' => $wallet->id,
            'amount' => 7000,
            'payment_method' => 'cash',
            'notes' => 'Top-up before reversal',
        ]);

        $topup = WalletTransaction::query()->where('transaction_type', WalletTransaction::TYPE_TOPUP)->firstOrFail();

        $response = $this->actingAs($user)->delete(route('wallet-transactions.destroy', $topup));

        $response->assertRedirect(route('wallets.show', $wallet));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'current_balance' => 0,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $topup->id,
            'status' => WalletTransaction::STATUS_REVERSED,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_REVERSAL,
            'amount' => -7000,
        ]);
    }

    public function test_finance_manager_can_adjust_wallet_balance(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'finance_manager');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 3000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('wallet-adjustments.store'), [
            'wallet_id' => $wallet->id,
            'amount_delta' => 2000,
            'reason' => 'Manual correction',
            'notes' => 'Added missing credit',
        ]);

        $response->assertRedirect(route('wallets.show', $wallet));
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'current_balance' => 5000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_ADJUSTMENT,
            'amount' => 2000,
        ]);
    }

    public function test_pos_cashier_cannot_topup_inactive_wallet(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 0,
            'status' => Wallet::STATUS_INACTIVE,
        ]);

        $response = $this->actingAs($user)->from(route('wallets.show', $wallet))->post(route('wallet-topups.store'), [
            'wallet_id' => $wallet->id,
            'amount' => 1000,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('wallets.show', $wallet));
        $response->assertSessionHasErrors('wallet_id');
        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => WalletTransaction::TYPE_TOPUP,
            'amount' => 1000,
        ]);
    }

    public function test_pos_cashier_cannot_sell_inactive_product_by_direct_request(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => 'active',
        ]);
        $product = PosProduct::query()->create([
            'name' => 'Hidden Item',
            'sku' => 'POS-HIDDEN',
            'price' => 1500,
            'stock_quantity' => 5,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)->from(route('pos-sales.create', ['identifier' => $student->admission_no]))->post(route('pos-sales.store'), [
            'wallet_id' => $wallet->id,
            'product_ids' => [$product->id],
            'quantities' => [1],
        ]);

        $response->assertRedirect(route('pos-sales.create', ['identifier' => $student->admission_no]));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('pos_sales', 0);
    }

    public function test_wallet_search_can_find_staff_by_name(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'finance_manager');
        $staff = \App\Models\Staff::query()->create([
            'staff_no' => 'STF-1001',
            'first_name' => 'May',
            'last_name' => 'Thu',
            'phone' => '09123456789',
            'email' => 'maythu@example.com',
            'department' => 'Finance',
            'designation' => 'Cashier',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        Wallet::query()->create([
            'owner_type' => \App\Models\Staff::class,
            'owner_id' => $staff->id,
            'current_balance' => 5000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('wallets.index', ['search' => 'May']));

        $response->assertOk();
        $response->assertSee('May Thu');
        $response->assertSee('STF-1001');
    }

    public function test_principal_can_view_pos_reports(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'principal');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => 'active',
        ]);
        $product = PosProduct::query()->create([
            'name' => 'Cake Slice',
            'sku' => 'POS-CAKE',
            'price' => 3000,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('wallet-topups.store'), [
            'wallet_id' => $wallet->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user)->post(route('pos-sales.store'), [
            'wallet_id' => $wallet->id,
            'product_ids' => [$product->id],
            'quantities' => [1],
        ]);

        $response = $this->actingAs($user)->get(route('reports.pos'));

        $response->assertOk();
        $response->assertSee('POS Reports');
        $response->assertSee('Posted Sales');
        $response->assertSee('Posted Top-ups');
        $response->assertSee('Daily Cashier Closing Summary');
        $response->assertSee($user->name);
    }

    public function test_cashier_summary_counts_only_posted_topups_and_sales(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'finance_manager');
        $student = $this->createStudentWithEnrollment();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => 'active',
        ]);
        $product = PosProduct::query()->create([
            'name' => 'Toast',
            'sku' => 'POS-TOAST',
            'price' => 2000,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('wallet-topups.store'), [
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'payment_method' => 'cash',
        ]);

        $topup = WalletTransaction::query()->where('transaction_type', WalletTransaction::TYPE_TOPUP)->latest('id')->firstOrFail();
        $this->actingAs($user)->delete(route('wallet-transactions.destroy', $topup));

        $this->actingAs($user)->post(route('pos-sales.store'), [
            'wallet_id' => $wallet->id,
            'product_ids' => [$product->id],
            'quantities' => [1],
        ]);

        $sale = PosSale::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->delete(route('pos-sales.destroy', $sale));

        $response = $this->actingAs($user)->get(route('reports.pos'));

        $response->assertOk();
        $response->assertSee('Daily Cashier Closing Summary');
        $response->assertSee((string) $user->name);
        $response->assertDontSee('>2<', false);
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $user->roles()->sync([
            Role::query()->where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    private function createStudentWithEnrollment(): Student
    {
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create([
            'grade_id' => $grade->id,
        ]);
        $student = Student::factory()->create([
            'status' => Student::STATUS_ACTIVE,
        ]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'fee_plan_id' => null,
            'enrollment_date' => now()->toDateString(),
            'status' => Enrollment::STATUS_ACTIVE,
            'remarks' => null,
        ]);

        return $student;
    }
}
