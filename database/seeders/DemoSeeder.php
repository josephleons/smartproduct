<?php

namespace Database\Seeders;

use App\Actions\CreateProduct;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Plan;
use App\Models\ProductEvent;
use App\Models\QrCode;
use App\Models\User;
use App\Services\Billing\PaymentProcessor;
use App\Services\Billing\SubscriptionManager;
use App\Services\VisitorContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Local demo data. Log in as admin@smartlabel.test (superadmin) or owner@smartlabel.test, password "password". */
class DemoSeeder extends Seeder
{
    public function run(CreateProduct $createProduct, SubscriptionManager $subscriptions, PaymentProcessor $payments): void
    {
        // Demo prices only, so the billing screens have numbers locally. Set real prices in Admin > Plans.
        Plan::where('slug', 'normal')->update(['monthly_price' => 30000, 'max_branches' => 2, 'max_users' => 5, 'max_products' => 300]);
        Plan::where('slug', 'premium')->update(['monthly_price' => 75000]);

        User::create(['company_id' => null, 'name' => 'Platform Admin', 'email' => 'admin@smartlabel.test', 'password' => 'password', 'role' => UserRole::SuperAdmin]);

        $company = Company::create([
            'name' => 'Kariakoo Electronics',
            'slug' => 'kariakoo-electronics',
            'phone' => '0712345678',
            'whatsapp' => '0712345678',
            'email' => 'info@kariakoo-electronics.test',
            'address' => 'Msimbazi St, Kariakoo, Dar es Salaam',
        ]);

        $kariakoo = Branch::create(['company_id' => $company->id, 'name' => 'Kariakoo', 'location' => 'Msimbazi St, Dar es Salaam', 'phone' => '0712345678', 'whatsapp' => '0712345678']);
        $mlimani = Branch::create(['company_id' => $company->id, 'name' => 'Mlimani City', 'location' => 'Sam Nujoma Rd, Dar es Salaam', 'phone' => '0754000111', 'whatsapp' => '0754000111']);

        $owner = User::create(['company_id' => $company->id, 'name' => 'Asha Mushi', 'email' => 'owner@smartlabel.test', 'password' => 'password', 'role' => UserRole::Owner]);
        User::create(['company_id' => $company->id, 'name' => 'Juma Said', 'email' => 'manager@smartlabel.test', 'password' => 'password', 'role' => UserRole::Manager]);
        User::create(['company_id' => $company->id, 'branch_id' => $mlimani->id, 'name' => 'Neema Paul', 'email' => 'staff@smartlabel.test', 'password' => 'password', 'role' => UserRole::Staff]);

        // Subscription: trial started 20 days ago, first month paid by M-Pesa.
        $this->travelBack(20, fn () => $subscriptions->start($company, Plan::where('slug', 'normal')->first()));
        $subscription = $company->subscription()->first();
        $subscriptions->issueDueInvoice($subscription, now()->toImmutable());
        $invoice = $subscription->invoices()->first();
        if ($invoice && $invoice->isOpen()) {
            $payments->record($invoice, ['amount' => $invoice->amount, 'method' => PaymentMethod::Mpesa->value, 'reference' => 'QJK3X8Y2LM'], User::where('email', 'admin@smartlabel.test')->first());
        }

        $laptop = Category::whereNull('company_id')->where('slug', 'laptop')->first();
        $phone = Category::whereNull('company_id')->where('slug', 'smartphone')->first();
        $tv = Category::whereNull('company_id')->where('slug', 'tv')->first();

        $products = [
            [$laptop, $kariakoo, 'Dell', 'Latitude 5420', 850000, ProductCondition::GradeA, 3, ['processor' => 'Core i5-1145G7', 'generation' => '11th', 'ram' => 16, 'storage' => '512GB SSD', 'display' => '14" FHD', 'battery_health' => 88, 'operating_system' => 'Windows 11']],
            [$laptop, $kariakoo, 'HP', 'EliteBook 840 G6', 780000, ProductCondition::GradeB, 3, ['processor' => 'Core i7-8565U', 'generation' => '8th', 'ram' => 8, 'storage' => '256GB SSD', 'operating_system' => 'Windows 10']],
            [$laptop, $mlimani, 'Lenovo', 'ThinkPad T14', 1150000, ProductCondition::Refurbished, 6, ['processor' => 'Ryzen 5 PRO 4650U', 'ram' => 16, 'storage' => '512GB SSD', 'operating_system' => 'Windows 11']],
            [$laptop, $mlimani, 'Apple', 'MacBook Air M1', 1950000, ProductCondition::GradeA, 6, ['processor' => 'Apple M1', 'ram' => 8, 'storage' => '256GB SSD', 'battery_health' => 91, 'operating_system' => 'macOS']],
            [$phone, $kariakoo, 'Samsung', 'Galaxy A54', 720000, ProductCondition::New, 12, ['storage' => 128, 'ram' => 8, 'screen' => '6.4" AMOLED', 'camera' => '50 MP', 'battery' => '5000 mAh', 'sim' => 'Dual SIM', 'network' => '5G']],
            [$phone, $kariakoo, 'Tecno', 'Camon 20', 430000, ProductCondition::New, 12, ['storage' => 256, 'ram' => 8, 'battery' => '5000 mAh', 'sim' => 'Dual SIM', 'network' => '4G']],
            [$phone, $mlimani, 'Apple', 'iPhone 12', 1250000, ProductCondition::GradeA, 3, ['storage' => 128, 'ram' => 4, 'battery' => '84% health', 'sim' => 'Dual SIM + eSIM', 'network' => '5G']],
            [$tv, $kariakoo, 'Hisense', '43A6K', 690000, ProductCondition::New, 24, ['size' => 43, 'resolution' => '4K UHD', 'smart_tv' => true, 'hdmi' => 3, 'usb' => 2, 'operating_system' => 'VIDAA']],
            [$tv, $mlimani, 'Samsung', 'UA55CU7000', 1450000, ProductCondition::New, 24, ['size' => 55, 'resolution' => '4K UHD', 'smart_tv' => true, 'hdmi' => 3, 'usb' => 1, 'operating_system' => 'Tizen']],
        ];

        $created = collect();
        foreach ($products as $i => [$category, $branch, $brand, $model, $price, $condition, $warranty, $specs]) {
            $created->push($createProduct->handle($owner, [
                'category_id' => $category->id,
                'branch_id' => $branch->id,
                'brand' => $brand,
                'model' => $model,
                'price' => $price,
                'condition' => $condition->value,
                'quantity' => 1 + $i % 3,
                'warranty_months' => $warranty,
                'status' => match ($i) {
                    2 => ProductStatus::LowStock->value,
                    6 => ProductStatus::Sold->value,
                    default => ProductStatus::Available->value,
                },
                'specs' => $specs,
            ]));
        }

        // Two weeks of scan and contact events for the charts.
        foreach ($created as $product) {
            $scans = 0;
            foreach (range(0, 13) as $daysAgo) {
                foreach (range(1, random_int(1, 6)) as $n) {
                    $at = now()->subDays($daysAgo)->setTime(random_int(9, 19), random_int(0, 59));
                    $ip = VisitorContext::hashIp('10.0.'.$daysAgo.'.'.random_int(1, 40));
                    $type = match (true) {
                        $n % 5 === 0 => EventType::WhatsappClick,
                        $n % 7 === 0 => EventType::CallClick,
                        default => EventType::Scan,
                    };
                    ProductEvent::create([
                        'company_id' => $company->id,
                        'branch_id' => $product->branch_id,
                        'product_id' => $product->id,
                        'type' => $type,
                        'ip_hash' => $ip,
                        'device_type' => 'mobile',
                        'is_unique' => random_int(0, 4) > 0,
                        'created_at' => $at,
                    ]);
                    $scans += $type === EventType::Scan ? 1 : 0;
                }
            }
            QrCode::where('product_id', $product->id)->update(['scans_count' => $scans, 'last_scanned_at' => now()->subHours(random_int(1, 30))]);
        }
    }

    private function travelBack(int $days, callable $callback): mixed
    {
        Carbon::setTestNow(now()->subDays($days));
        CarbonImmutable::setTestNow(now());

        try {
            return $callback();
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }
}
