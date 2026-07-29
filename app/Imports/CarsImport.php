<?php

namespace App\Imports;

use App\Models\Car;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CarsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    public function model(array $row)
    {
        return new Car([
            'organization_id' => auth()->user()->organization_id,
            'brand' => $row['brand'] ?? null,
            'model' => $row['model'] ?? null,
            'version' => $row['version'] ?? null,
            'year' => $row['year'] ?? null,
            'mileage' => $row['mileage'] ?? 0,
            'fuel' => $row['fuel'] ?? null,
            'transmission' => $row['transmission'] ?? null,
            'cv' => $row['cv'] ?? 0,
            'co2' => $row['co2'] ?? 0,
            'purchase_price' => $row['purchase_price'] ?? 0,
            'new_price' => $row['new_price'] ?? 0,
            'manual_tax_base' => $row['manual_tax_base'] ?? 0,
            'transport' => $row['transport'] ?? 0,
            'itv_fee' => $row['itv_fee'] ?? 0,
            'coc_fee' => $row['coc_fee'] ?? 0,
            'dgt_fees' => $row['dgt_fees'] ?? 0,
            'professional_fees' => $row['professional_fees'] ?? 0,
            'vin' => $row['vin'] ?? null,
            'city' => $row['city'] ?? null,
            'status' => $row['status'] ?? 'Located',
            'traffic_light' => $row['traffic_light'] ?? 'neutral',
            'notes' => $row['notes'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|string',
            'fuel' => 'required|string',
            'transmission' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
