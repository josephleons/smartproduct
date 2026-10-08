<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * System categories shared by every company (company_id = null).
 * Brand, model, condition, warranty and price live on products, so they are not spec fields.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $slug => $template) {
            $category = Category::updateOrCreate(
                ['company_id' => null, 'slug' => $slug],
                ['name' => $template['name'], 'icon' => $template['icon']],
            );

            foreach ($template['fields'] as $order => $field) {
                $category->specFields()->updateOrCreate(['key' => $field[0]], [
                    'label' => $field[1],
                    'type' => $field[2],
                    'unit' => $field[3] ?? null,
                    'options' => $field[4] ?? null,
                    'is_required' => $field[5] ?? false,
                    'show_on_label' => $field[6] ?? false,
                    'sort_order' => $order + 1,
                ]);
            }
        }
    }

    /** Field tuple: [key, label, type, unit, options, required, show_on_label] */
    private function templates(): array
    {
        return [
            'laptop' => [
                'name' => 'Laptop',
                'icon' => 'tabler-device-laptop',
                'fields' => [
                    ['processor', 'Processor', 'text', null, null, true, true],
                    ['generation', 'Generation', 'text'],
                    ['ram', 'RAM', 'number', 'GB', null, true, true],
                    ['storage', 'Storage', 'text', null, null, true, true],
                    ['gpu', 'GPU', 'text'],
                    ['display', 'Display', 'text'],
                    ['battery_health', 'Battery health', 'number', '%'],
                    ['operating_system', 'Operating system', 'select', null, ['Windows 11', 'Windows 10', 'macOS', 'Linux', 'ChromeOS', 'None']],
                ],
            ],
            'smartphone' => [
                'name' => 'Smartphone',
                'icon' => 'tabler-device-mobile',
                'fields' => [
                    ['storage', 'Storage', 'number', 'GB', null, true, true],
                    ['ram', 'RAM', 'number', 'GB', null, true, true],
                    ['screen', 'Screen', 'text'],
                    ['camera', 'Camera', 'text'],
                    ['battery', 'Battery', 'text', null, null, false, true],
                    ['sim', 'SIM', 'select', null, ['Single SIM', 'Dual SIM', 'eSIM', 'Dual SIM + eSIM']],
                    ['network', 'Network', 'select', null, ['3G', '4G', '5G']],
                ],
            ],
            'tv' => [
                'name' => 'TV',
                'icon' => 'tabler-device-tv',
                'fields' => [
                    ['size', 'Size', 'number', 'inch', null, true, true],
                    ['resolution', 'Resolution', 'select', null, ['HD', 'Full HD', '4K UHD', '8K'], false, true],
                    ['smart_tv', 'Smart TV', 'boolean', null, null, false, true],
                    ['hdmi', 'HDMI ports', 'number'],
                    ['usb', 'USB ports', 'number'],
                    ['operating_system', 'Operating system', 'select', null, ['Android TV', 'Google TV', 'webOS', 'Tizen', 'VIDAA', 'Other', 'None']],
                ],
            ],
        ];
    }
}
