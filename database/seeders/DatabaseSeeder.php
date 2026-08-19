<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Item;
use App\Models\SalesEmployee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $customers = $this->seedCustomers();
        $items = $this->seedItems();
        $salesEmployees = $this->seedSalesEmployees();
        $this->seedInvoices($customers, $items, $salesEmployees);
    }

    protected function seedUsers(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );
    }

    protected function seedCustomers()
    {
        $rows = [
            [
                'customer_code' => 'CC00001',
                'display_name' => 'Walk In Customer - HQ',
                'customer_name' => 'TEST TEST',
                'contact_person' => null,
                'currency' => 'KES',
                'kra_pin' => null,
            ],
            [
                'customer_code' => 'CC00002',
                'display_name' => 'Jomo Kimathi Supplies Ltd',
                'customer_name' => 'JOMO KIMATHI',
                'contact_person' => 'Jomo Kimathi',
                'currency' => 'KES',
                'kra_pin' => 'A012345678B',
            ],
            [
                'customer_code' => 'CC00003',
                'display_name' => 'Coastal Traders Ltd',
                'customer_name' => 'COASTAL TRADERS',
                'contact_person' => 'Amina Hassan',
                'currency' => 'KES',
                'kra_pin' => 'P009876543C',
            ],
            [
                'customer_code' => 'CC00004',
                'display_name' => 'Rift Valley Distributors',
                'customer_name' => 'RIFT VALLEY DIST',
                'contact_person' => 'Peter Kiptoo',
                'currency' => 'KES',
                'kra_pin' => 'A011122233D',
            ],
            [
                'customer_code' => 'CC00005',
                'display_name' => 'Nairobi Retail Hub',
                'customer_name' => 'NAIROBI RETAIL HUB',
                'contact_person' => 'Grace Wanjiru',
                'currency' => 'KES',
                'kra_pin' => 'P004455667E',
            ],
        ];

        $customers = collect();
        foreach ($rows as $row) {
            $customers->push(Customer::firstOrCreate(['customer_code' => $row['customer_code']], $row));
        }

        return $customers;
    }

    protected function seedItems()
    {
        $rows = [
            [
                'item_no' => 'FG00011',
                'item_description' => 'Umi All Purpose Home Baking Flour 2Kg',
                'uom_code' => 'Bales',
                'unit_price' => 1850.000,
                'vat_code' => 'O0',
                'warehouse' => 'FG WHS',
                'qty_in_whse' => 648,
            ],
            [
                'item_no' => 'FG00012',
                'item_description' => 'Umi Wheat Flour 1Kg',
                'uom_code' => 'Bales',
                'unit_price' => 980.000,
                'vat_code' => 'O0',
                'warehouse' => 'FG WHS',
                'qty_in_whse' => 1200,
            ],
            [
                'item_no' => 'FG00013',
                'item_description' => 'Umi Self Raising Flour 2Kg',
                'uom_code' => 'Bales',
                'unit_price' => 1950.000,
                'vat_code' => 'O0',
                'warehouse' => 'FG WHS',
                'qty_in_whse' => 430,
            ],
            [
                'item_no' => 'FG00021',
                'item_description' => 'Umi Cooking Oil 2L',
                'uom_code' => 'Carton',
                'unit_price' => 3200.000,
                'vat_code' => 'S16',
                'warehouse' => 'FG WHS',
                'qty_in_whse' => 300,
            ],
            [
                'item_no' => 'FG00022',
                'item_description' => 'Umi Cooking Oil 5L',
                'uom_code' => 'Carton',
                'unit_price' => 7800.000,
                'vat_code' => 'S16',
                'warehouse' => 'FG WHS',
                'qty_in_whse' => 150,
            ],
            [
                'item_no' => 'RM00001',
                'item_description' => 'Raw Wheat Grain - Bulk',
                'uom_code' => 'Tonnes',
                'unit_price' => 42000.000,
                'vat_code' => 'O0',
                'warehouse' => 'RM WHS',
                'qty_in_whse' => 85,
            ],
        ];

        $items = collect();
        foreach ($rows as $row) {
            $items->push(Item::firstOrCreate(['item_no' => $row['item_no']], $row));
        }

        return $items;
    }

    protected function seedSalesEmployees()
    {
        $rows = [
            ['name' => 'Farouk Abdulrehman Mohamed', 'email' => 'farouk@example.com'],
            ['name' => 'Grace Wanjiru', 'email' => 'grace.wanjiru@example.com'],
            ['name' => 'Peter Kiptoo', 'email' => 'peter.kiptoo@example.com'],
            ['name' => 'Amina Hassan', 'email' => 'amina.hassan@example.com'],
        ];

        $employees = collect();
        foreach ($rows as $row) {
            $employees->push(SalesEmployee::firstOrCreate(['name' => $row['name']], $row));
        }

        return $employees;
    }

    protected function seedInvoices($customers, $items, $salesEmployees): void
    {
        // Skip if invoices already exist, so re-running the seeder is safe.
        if (Invoice::query()->exists()) {
            return;
        }

        $sampleInvoices = [
            [
                'customer' => $customers[0], // CC00001
                'employee' => $salesEmployees[0],
                'remarks' => 'TEST',
                'lines' => [
                    ['item' => $items[0], 'qty' => 20, 'discount' => 5.405405], // matches the screenshot
                ],
            ],
            [
                'customer' => $customers[1], // CC00002
                'employee' => $salesEmployees[1],
                'remarks' => 'Monthly restock order',
                'lines' => [
                    ['item' => $items[1], 'qty' => 50, 'discount' => 0],
                    ['item' => $items[3], 'qty' => 10, 'discount' => 2],
                ],
            ],
            [
                'customer' => $customers[2], // CC00003 — deliberately over 10,000 to trigger approval
                'employee' => $salesEmployees[2],
                'remarks' => 'Large order — needs approval per finance policy',
                'lines' => [
                    ['item' => $items[5], 'qty' => 1, 'discount' => 0], // 42,000
                    ['item' => $items[4], 'qty' => 3, 'discount' => 10],
                ],
            ],
        ];

        foreach ($sampleInvoices as $data) {
            $totalBeforeDiscount = 0;
            $grandTotal = 0;
            $lineData = [];

            foreach ($data['lines'] as $i => $line) {
                $item = $line['item'];
                $qty = $line['qty'];
                $discount = $line['discount'];
                $price = (float) $item->unit_price;

                $priceAfterDiscount = round($price - ($price * $discount / 100), 3);
                $lineTotal = round($priceAfterDiscount * $qty, 3);

                $totalBeforeDiscount += round($price * $qty, 3);
                $grandTotal += $lineTotal;

                $lineData[] = [
                    'item_no' => $item->item_no,
                    'item_description' => $item->item_description,
                    'warehouse' => $item->warehouse,
                    'qty_in_whse' => $item->qty_in_whse,
                    'uom_code' => $item->uom_code,
                    'quantity' => $qty,
                    'price_before_discount' => $price,
                    'discount' => $discount,
                    'price_after_discount' => $priceAfterDiscount,
                    'vat_code' => $item->vat_code,
                    'gross_price_after_discount' => $priceAfterDiscount,
                    'line_total' => $lineTotal,
                    'gross_total' => $lineTotal,
                    'sort_order' => $i,
                ];
            }

            $invoice = Invoice::create([
                'doc_no' => Invoice::nextDocNo(),
                'customer_id' => $data['customer']->id,
                'customer_code' => $data['customer']->customer_code,
                'customer_name' => $data['customer']->customer_name,
                'posting_date' => now(),
                'value_date' => now(),
                'document_date' => now(),
                'sales_employee_id' => $data['employee']->id,
                'owner' => $data['employee']->name,
                'remarks' => $data['remarks'],
                'total_before_discount' => round($totalBeforeDiscount, 3),
                'discount_percent' => 0,
                'total_after_discount' => round($grandTotal, 3),
                'balance_due' => round($grandTotal, 3),
                'needs_approval' => $grandTotal > Invoice::APPROVAL_THRESHOLD,
                'status' => 'Open',
            ]);

            foreach ($lineData as $line) {
                $invoice->lines()->create($line);
            }
        }
    }
}
