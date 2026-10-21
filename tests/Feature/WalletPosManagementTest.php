<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PosProduct;
use App\Models\PosProductCategory;
use App\Models\PosSale;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPosManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_history_is_paginated_with_stable_order_and_role_actions(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = Student::factory()->create();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 0,
            'status' => 'active',
        ]);
        $sales = collect();
        for ($i = 1; $i <= 51; $i++) {
            $sales->push(PosSale::query()->create([
                'sale_no' => 'PAGED-'.sprintf('%03d', $i),
                'wallet_id' => $wallet->id,
                'owner_type' => Student::class,
                'owner_id' => $student->id,
                'total_amount' => 100,
                'balance_before' => 100,
                'balance_after' => 0,
                'status' => $i === 51 ? PosSale::STATUS_REVERSED : PosSale::STATUS_POSTED,
                'created_at' => '2026-10-01 12:00:00',
            ]));
        }

        $response = $this->actingAs($user)->get(route('pos-sales.index'));
        $response->assertOk()->assertSee('PAGED-051')->assertDontSee('PAGED-001');
        $response->assertViewHas('sales', fn ($page) => $page->total() === 51 && $page->count() === 50);
        $response->assertDontSee('Reverse</button>', false);
        $this->get(route('pos-sales.index', ['page' => 2]))
            ->assertOk()->assertSee('PAGED-001')->assertDontSee('PAGED-051');

        $this->assignRole($user, 'finance_manager');
        $user->unsetRelation('roles');
        $response = $this->actingAs($user)->get(route('pos-sales.index'));
        $response->assertOk()->assertSee('action="'.route('pos-sales.destroy', $sales[49]).'"', false);
        $response->assertDontSee('action="'.route('pos-sales.destroy', $sales[50]).'"', false);
    }

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

    public function test_pos_manager_has_full_pos_module_access(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_manager');

        $expectedPermissions = [
            'wallets.view',
            'wallets.topup',
            'wallets.adjust',
            'wallet_transactions.view',
            'pos_products.manage',
            'pos_sales.create',
            'pos_sales.view',
            'pos_sales.reverse',
            'pos_reports.view',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertTrue($user->hasPermission($permission), 'Missing POS Manager permission: '.$permission);
        }

        $routes = [
            'dashboard',
            'pos-cashier.snack-shop',
            'pos-cashier.stationery',
            'wallets.index',
            'wallet-topups.create',
            'pos-product-categories.index',
            'pos-products.index',
            'pos-sales.index',
            'pos-sales.create',
            'reports.pos',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
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
        $response->assertSee('Snack Shop POS');
        $response->assertSee($student->admission_no);
    }

    public function test_pos_shows_student_preferred_and_english_names_without_duplicates(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $student->forceFill([
            'preferred_name' => 'Alex',
            'name_en' => 'Aye Chan',
        ])->save();
        $wallet = Wallet::query()->create([
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'current_balance' => 10000,
            'status' => Wallet::STATUS_ACTIVE,
        ]);
        $sale = PosSale::query()->create([
            'sale_no' => 'POS-NAME-TEST',
            'pos_section' => PosSale::SECTION_SNACK_SHOP,
            'wallet_id' => $wallet->id,
            'owner_type' => Student::class,
            'owner_id' => $student->id,
            'total_amount' => 0,
            'balance_before' => 10000,
            'balance_after' => 10000,
            'status' => PosSale::STATUS_POSTED,
            'performed_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('pos-cashier.snack-shop', [
            'identifier' => $student->admission_no,
        ]));

        $response->assertOk();
        $response->assertSee('Alex / Aye Chan');
        $this->assertSame('Alex / Aye Chan', $wallet->load('owner')->ownerName());
        $this->assertSame('Alex / Aye Chan', $sale->load('owner')->ownerName());

        $student->forceFill(['preferred_name' => null])->save();
        $wallet->unsetRelation('owner');
        $sale->unsetRelation('owner');

        $fallbackResponse = $this->actingAs($user)->get(route('pos-cashier.snack-shop', [
            'identifier' => $student->admission_no,
        ]));

        $fallbackResponse->assertOk();
        $fallbackResponse->assertSee('Aye Chan');
        $this->assertSame('Aye Chan', $wallet->ownerName());
        $this->assertSame('Aye Chan', $sale->ownerName());

        $student->forceFill(['preferred_name' => 'Aye Chan'])->save();
        $wallet->unsetRelation('owner');
        $sale->unsetRelation('owner');

        $this->assertSame('Aye Chan', $wallet->ownerName());
        $this->assertSame('Aye Chan', $sale->ownerName());
    }

    public function test_snack_shop_and_stationery_pages_show_only_their_own_products(): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, 'pos_cashier');
        $student = $this->createStudentWithEnrollment();
        $snack = $this->createPosProduct([
            'name' => 'Chicken Sandwich',
            'sku' => 'POS-SNACK-PAGE',
            'price' => 2500,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
        $stationery = $this->createPosProduct([
            'name' => 'Exercise Book',
            'sku' => 'POS-STA-PAGE',
            'price' => 1800,
            'stock_quantity' => 10,
            'status' => 'active',
        ], PosProductCategory::SECTION_STATIONERY);

        $snackResponse = $this->actingAs($user)->get(route('pos-cashier.snack-shop', [
            'identifier' => $student->admission_no,
        ]));

        $snackResponse->assertOk();
        $snackResponse->assertSee('Snack Shop POS');
        $snackResponse->assertSee('Shopping Cart');
        $snackResponse->assertSee('Complete Checkout');
        $snackResponse->assertDontSee('<select', false);
        $snackResponse->assertSee($snack->name);
        $snackResponse->assertDontSee($stationery->name);

        $stationeryResponse = $this->actingAs($user)->get(route('pos-cashier.stationery', [
            'identifier' => $student->admission_no,
        ]));

        $stationeryResponse->assertOk();
        $stationeryResponse->assertSee('Stationery POS');
        $stationeryResponse->assertSee($stationery->name);
        $stationeryResponse->assertDontSee($snack->name);
    }

    public function test_product_from_another_pos_section_cannot_be_submitted_directly(): void
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
        $stationery = $this->createPosProduct([
            'name' => 'Blue Pen',
            'sku' => 'POS-CROSS-SECTION',
            'price' => 1000,
            'stock_quantity' => 10,
            'status' => 'active',
        ], PosProductCategory::SECTION_STATIONERY);

        $response = $this->actingAs($user)
            ->from(route('pos-cashier.snack-shop', ['identifier' => $student->admission_no]))
            ->post(route('pos-sales.store'), [
                'pos_section' => PosSale::SECTION_SNACK_SHOP,
                'wallet_id' => $wallet->id,
                'product_ids' => [$stationery->id],
                'quantities' => [1],
            ]);

        $response->assertRedirect(route('pos-cashier.snack-shop', ['identifier' => $student->admission_no]));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('pos_sales', 0);
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
        $product = $this->createPosProduct([
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
        $salePage = $this->actingAs($user)->get(route('pos-sales.show', $sale));
        $salePage->assertOk();
        $salePage->assertSee('Scan Another Customer');
        $salePage->assertSee(route('pos-cashier.snack-shop'), false);
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
        $product = $this->createPosProduct([
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
        $product = $this->createPosProduct([
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
        $staff = Staff::query()->create([
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
            'owner_type' => Staff::class,
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
        $product = $this->createPosProduct([
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
        $product = $this->createPosProduct([
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

    private function createPosProduct(array $attributes, string $section = PosProductCategory::SECTION_SNACK_SHOP): PosProduct
    {
        $category = PosProductCategory::query()->firstOrCreate(
            ['code' => $section === PosProductCategory::SECTION_STATIONERY ? 'TEST-STA' : 'TEST-SNK'],
            [
                'name' => $section === PosProductCategory::SECTION_STATIONERY ? 'Stationery' : 'Snacks',
                'pos_section' => $section,
                'status' => 'active',
            ],
        );

        return PosProduct::query()->create([
            'pos_product_category_id' => $category->id,
            ...$attributes,
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
