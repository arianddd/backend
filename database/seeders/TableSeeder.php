<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['table_number' => '1', 'secret_key' => 'SECRET_MEJA_1'],
            ['table_number' => '2', 'secret_key' => 'SECRET_MEJA_2'],
            ['table_number' => '3', 'secret_key' => 'SECRET_MEJA_3'],
            ['table_number' => '4', 'secret_key' => 'SECRET_MEJA_4'],
            ['table_number' => '5', 'secret_key' => 'SECRET_MEJA_5'],
        ];

        foreach ($tables as $data) {
            Table::updateOrCreate(['table_number' => $data['table_number']], $data);
        }
    }
}