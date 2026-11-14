<?php

namespace Tests\Feature;

use App\Enums\ProductCondition;
use App\Http\Middleware\SetLocale;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImport\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private const LAPTOP_HEADER = ['Brand *', 'Model *', 'Price (TZS) *', 'Condition', 'Status', 'Quantity', 'Serial number', 'Warranty (months)', 'Processor *', 'RAM (GB) *', 'Storage *', 'Operating system'];

    /** @param  list<list<mixed>>  $rows */
    private function csv(array $rows, string $separator = ','): UploadedFile
    {
        $lines = array_map(fn ($row) => implode($separator, array_map(
            fn ($v) => str_contains((string) $v, $separator) || str_contains((string) $v, '"') ? '"'.str_replace('"', '""', (string) $v).'"' : (string) $v,
            $row,
        )), $rows);

        return UploadedFile::fake()->createWithContent('stock.csv', implode("\n", $lines)."\n");
    }

    /** @param  list<list<mixed>>  $rows */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import-test-');
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'stock.xlsx', null, null, true);
    }

    private function upload(User $user, UploadedFile $file, array $overrides = []): TestResponse
    {
        return $this->actingAs($user)->post('/products/import', $overrides + [
            'category_id' => $this->laptopCategory()->id,
            'branch_id' => $this->firstBranch($user)->id,
            'file' => $file,
        ]);
    }

    private function importId(TestResponse $response): string
    {
        $response->assertRedirect();
        preg_match('#/products/import/([0-9a-f-]{36})$#', $response->headers->get('Location'), $m);

        return $m[1];
    }

    public function test_import_page_and_templates(): void
    {
        $user = $this->makeTenant('staff');
        $laptop = $this->laptopCategory();

        $this->actingAs($user)->get('/products/import')->assertOk()->assertSee(__('Excel template (.xlsx)'));

        foreach (['xlsx', 'csv'] as $format) {
            $response = $this->actingAs($user)->get("/products/import/template?category={$laptop->id}&format={$format}")->assertOk();
            $file = $response->baseResponse->getFile();
            $sheet = app(SpreadsheetReader::class)->read($file->getPathname(), $format, 10);

            $this->assertSame(array_slice(self::LAPTOP_HEADER, 0, 8), array_slice($sheet['header'], 0, 8), $format);
            $this->assertContains('Processor *', $sheet['header']);
            $this->assertContains('RAM (GB) *', $sheet['header']);
            $this->assertSame('Dell', $sheet['rows'][2][0], 'example row');
        }
    }

    public function test_preview_then_import_creates_valid_rows_and_reports_bad_ones(): void
    {
        $user = $this->makeTenant('staff');

        $file = $this->csv([
            self::LAPTOP_HEADER,
            ['Dell', 'Latitude 5420', '850,000', 'grade a', '', '', 'SN-1', '3', 'Core i5', '16GB', '512GB SSD', 'windows 11'],
            ['HP', 'EliteBook 840', 'TZS 1,200,000', 'Refurbished', 'Low stock', '4', '', '', 'Core i7', '32', '1TB', ''],
            ['Lenovo', 'ThinkPad T14', 'abc', 'Grade Z', '', '', 'SN-2', '', '', '8', '256GB', 'Windows 95'],
            ['', '', '', '', '', '', '', '', '', '', '', ''],
            ['Dell', 'Latitude 5420', '800000', '', '', '', 'SN-1', '', 'Core i5', '16', '512GB', ''],
        ]);

        $id = $this->importId($this->upload($user, $file));

        $preview = $this->actingAs($user)->get("/products/import/{$id}")->assertOk();
        $preview->assertSee('Latitude 5420')->assertSee('TZS 1,200,000')
            ->assertSee('Operating system must be one of: Windows 11')
            ->assertSee('Serial number SN-1 is also on row 2.');
        $this->assertSame(0, Product::count(), 'nothing is saved before confirming');

        $this->actingAs($user)->post("/products/import/{$id}")->assertRedirect("/products/import/{$id}/result");

        $this->assertSame(2, Product::count());
        $dell = Product::where('serial_number', 'SN-1')->with('specs', 'qrCode')->sole();
        $this->assertSame(850000, $dell->price);
        $this->assertSame(ProductCondition::GradeA, $dell->condition);
        $this->assertSame(1, $dell->quantity);
        $this->assertSame('16', $dell->specs->firstWhere('key', 'ram')->value);
        $this->assertSame('Windows 11', $dell->specs->firstWhere('key', 'operating_system')->value);
        $this->assertNotNull($dell->qrCode);
        $this->assertSame($this->firstBranch($user)->id, $dell->branch_id);

        $hp = Product::where('brand', 'HP')->sole();
        $this->assertSame('low_stock', $hp->status->value);
        $this->assertSame(4, $hp->quantity);

        $this->actingAs($user)->get("/products/import/{$id}/result")->assertOk()
            ->assertSee('2 products imported')
            ->assertSee(e(route('labels.index', ['products' => [$dell->id, $hp->id]])), false);

        // A second submit (double click, back button) imports nothing more.
        $this->actingAs($user)->post("/products/import/{$id}")->assertRedirect('/products/import')->assertSessionHas('error');
        $this->assertSame(2, Product::count());
    }

    public function test_xlsx_with_numeric_cells_and_semicolon_csv(): void
    {
        $user = $this->makeTenant();

        $id = $this->importId($this->upload($user, $this->xlsx([
            self::LAPTOP_HEADER,
            ['Dell', 'Latitude 7490', 650000, 'New', 'Available', 2, 356938035643809, 12, 'Core i7', 16, '256GB', 'Windows 10'],
        ])));
        $this->actingAs($user)->post("/products/import/{$id}");

        $product = Product::sole();
        $this->assertSame('356938035643809', $product->serial_number);
        $this->assertSame(650000, $product->price);
        $this->assertSame(12, $product->warranty_months);

        // A Swahili template (where Condition and Status are both "Hali"), Swahili values,
        // and ";" as separator, as Excel saves CSV in many regional settings.
        $template = $this->actingAs($user)->withCookie(SetLocale::COOKIE, 'sw')
            ->get('/products/import/template?category='.$this->laptopCategory()->id.'&format=csv');
        $header = app(SpreadsheetReader::class)->read($template->baseResponse->getFile()->getPathname(), 'csv', 10)['header'];
        $this->assertSame(['Chapa *', 'Modeli *', 'Bei (TZS) *', 'Hali', 'Hali'], array_slice($header, 0, 5));

        $row = array_fill(0, count($header), '');
        $set = function (string $name, string $value, int $skip = 0) use (&$row, $header) {
            $row[array_keys($header, $name)[$skip]] = $value;
        };
        $set('Chapa *', 'HP');
        $set('Modeli *', 'ProBook 450');
        $set('Bei (TZS) *', '700,000');
        $set('Hali', 'Daraja A');
        $set('Hali', 'Zimebaki chache', 1);
        $set('Prosesa *', 'Core i5');
        $set('RAM (GB) *', '8 GB');
        $set('Hifadhi *', '256GB');

        $id = $this->importId($this->withCookie(SetLocale::COOKIE, 'en')->upload($user, $this->csv([$header, $row], ';')));
        $this->actingAs($user)->post("/products/import/{$id}");

        $hp = Product::where('model', 'ProBook 450')->sole();
        $this->assertSame(ProductCondition::GradeA, $hp->condition);
        $this->assertSame('low_stock', $hp->status->value);
        $this->assertSame(700000, $hp->price);
    }

    public function test_files_without_required_columns_or_rows_are_rejected(): void
    {
        $user = $this->makeTenant();

        $this->upload($user, $this->csv([['Name', 'Cost'], ['Dell', '1']]))
            ->assertSessionHasErrors(['file' => __('The file is missing these columns: :columns. Download the :category template and copy your products into it.', [
                'columns' => 'Brand *, Model *, Price (TZS) *, Processor *, RAM (GB) *, Storage *',
                'category' => 'Laptop',
            ])]);

        $this->upload($user, $this->csv([self::LAPTOP_HEADER]))->assertSessionHasErrors('file');
        $this->upload($user, UploadedFile::fake()->create('stock.pdf', 10))->assertSessionHasErrors('file');
    }

    public function test_already_registered_serials_are_rejected(): void
    {
        $user = $this->makeTenant();
        $existing = Product::factory()->for($this->firstBranch($user))->create(['serial_number' => 'SN-TAKEN']);
        // Another company's serial is not a duplicate.
        Product::factory()->for($this->firstBranch($this->makeTenant()))->create(['serial_number' => 'SN-ELSEWHERE']);

        $id = $this->importId($this->upload($user, $this->csv([
            self::LAPTOP_HEADER,
            ['Dell', 'A', '1', '', '', '', 'SN-TAKEN', '', 'i5', '8', '256', ''],
            ['Dell', 'B', '1', '', '', '', 'SN-ELSEWHERE', '', 'i5', '8', '256', ''],
        ])));

        $this->actingAs($user)->get("/products/import/{$id}")
            ->assertSee("Serial number SN-TAKEN is already registered ({$existing->sku}).");
        $this->actingAs($user)->post("/products/import/{$id}");
        $this->assertTrue(Product::where('model', 'B')->exists());
        $this->assertFalse(Product::where('model', 'A')->exists());
    }

    public function test_plan_limit_caps_the_import(): void
    {
        $user = $this->makeTenant();
        $user->company->subscription->plan->update(['max_products' => 3]);
        Product::factory()->for($this->firstBranch($user))->create();

        $rows = [self::LAPTOP_HEADER];
        foreach (range(1, 4) as $i) {
            $rows[] = ['Dell', "Model {$i}", '1000', '', '', '', '', '', 'i5', '8', '256', ''];
        }
        $id = $this->importId($this->upload($user, $this->csv($rows)));

        $this->actingAs($user)->get("/products/import/{$id}")->assertSee('only the first 2 ready rows will be imported');
        $this->actingAs($user)->post("/products/import/{$id}");

        $this->assertSame(3, Product::count());
        $this->actingAs($user)->get("/products/import/{$id}/result")->assertSee('2 ready rows were not imported');
    }

    public function test_company_a_cannot_see_use_or_target_company_b(): void
    {
        $userA = $this->makeTenant();
        $userB = $this->makeTenant();
        $file = fn () => $this->csv([self::LAPTOP_HEADER, ['Dell', 'X1', '1', '', '', '', '', '', 'i5', '8', '256', '']]);

        // Another company's branch cannot be targeted.
        $this->upload($userA, $file(), ['branch_id' => $this->firstBranch($userB)->id])->assertSessionHasErrors('branch_id');

        // Another user's preview cannot be viewed, confirmed or its result read.
        $id = $this->importId($this->upload($userB, $file()));
        $this->actingAs($userA)->get("/products/import/{$id}")->assertNotFound();
        $this->actingAs($userA)->post("/products/import/{$id}")->assertRedirect('/products/import');
        $this->assertSame(0, Product::withoutGlobalScopes()->count());
        $this->assertTrue(Cache::has('product-import:'.$id), 'B can still import it');

        $this->actingAs($userB)->post("/products/import/{$id}");
        $this->actingAs($userA)->get("/products/import/{$id}/result")->assertNotFound();
        $this->assertSame($userB->company_id, Product::withoutGlobalScopes()->sole()->company_id);
    }

    public function test_branch_restricted_staff_can_only_import_into_their_branch(): void
    {
        $staff = $this->makeTenant('staff', branchRestricted: true);
        $otherBranch = Branch::factory()->create(['company_id' => $staff->company_id]);
        $file = $this->csv([self::LAPTOP_HEADER, ['Dell', 'X1', '1', '', '', '', '', '', 'i5', '8', '256', '']]);

        $this->upload($staff, $file, ['branch_id' => $otherBranch->id])->assertSessionHasErrors('branch_id');
    }
}
