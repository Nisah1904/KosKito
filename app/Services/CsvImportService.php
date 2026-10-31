<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Impor data kost dari CSV milik admin (data berizin / data pemilik).
 * Baris tidak pernah langsung berstatus "approved" — semua masuk ke
 * antrean verifikasi admin.
 */
class CsvImportService
{
    public const REQUIRED_COLUMNS = ['name', 'price', 'address', 'type'];

    public function importFromCsv(string $path, int $userId): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['error' => 'File tidak dapat dibaca.'];
        }

        $header = fgetcsv($handle, 0, ',');

        if ($header === false) {
            fclose($handle);
            return ['error' => 'CSV kosong atau tidak memiliki baris header.'];
        }

        $header = array_map(fn ($h) => strtolower(preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $h))), $header);

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            fclose($handle);
            return ['error' => 'Kolom wajib tidak ditemukan di header: ' . implode(', ', $missing) . '. Header terbaca: ' . implode(', ', $header) . '.'];
        }

        $report = ['total' => 0, 'success' => 0, 'failed' => 0, 'duplicate' => 0, 'errors' => [], 'created' => []];
        $seenInFile = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));

            if (implode('', array_map(fn ($v) => trim((string) $v), $data)) === '') {
                continue;
            }

            $report['total']++;

            $errors = $this->validateRow($data);

            if ($errors !== []) {
                $report['failed']++;
                $report['errors'][] = "Baris {$rowNumber}: " . implode(' ', $errors);
                continue;
            }

            $name = trim((string) $data['name']);
            $address = trim((string) $data['address']);
            $latitude = $this->toFloat($data['latitude'] ?? null);
            $longitude = $this->toFloat($data['longitude'] ?? null);
            $fingerprint = mb_strtolower($name) . '|' . mb_strtolower($address);

            if (isset($seenInFile[$fingerprint])) {
                $report['duplicate']++;
                $report['errors'][] = "Baris {$rowNumber}: duplikat dari baris {$seenInFile[$fingerprint]} dalam file yang sama (nama & alamat sama).";
                continue;
            }

            $duplicate = $this->findDuplicate($name, $address, $latitude, $longitude);

            if ($duplicate !== null) {
                $report['duplicate']++;
                $report['errors'][] = "Baris {$rowNumber}: kemungkinan duplikat dengan listing existing #{$duplicate->id} \"{$duplicate->name}\".";
                continue;
            }

            $property = Property::create([
                'owner_id' => $userId,
                'name' => $name,
                'price' => $this->normalizePrice($data['price']),
                'location' => mb_substr($address, 0, 150),
                'address' => $address,
                'type' => $this->normalizeType($data['type']),
                'rooms' => max(1, $this->toInt($data['rooms'] ?? 1) ?: 1),
                'available_rooms' => max(1, $this->toInt($data['rooms'] ?? 1) ?: 1),
                'facilities' => $this->normalizeFacilities($data['facilities'] ?? null),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'campus_id' => $this->resolveCampusId($data['campus_id'] ?? null),
                'phone' => $this->cleanPhone($data['phone'] ?? null),
                'description' => 'Diimpor melalui fitur impor data Kostly. Perlu verifikasi admin.',
                'status' => Property::STATUS_PENDING,
                'source' => 'import',
                'source_url' => trim((string) ($data['source_url'] ?? '')) ?: null,
                'is_demo' => false,
            ]);

            $seenInFile[$fingerprint] = $rowNumber;
            $report['success']++;
            $report['created'][] = $property->id;
        }

        fclose($handle);

        return $report;
    }

    private function validateRow(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'kolom "name" wajib diisi.';
        }

        if ($this->normalizePrice($data['price'] ?? null) === null) {
            $errors[] = 'kolom "price" tidak valid (harus angka, mis. 850000 atau 850.000).';
        }

        if (trim((string) ($data['address'] ?? '')) === '') {
            $errors[] = 'kolom "address" wajib diisi.';
        }

        if ($this->normalizeType($data['type'] ?? null) === null) {
            $errors[] = 'kolom "type" harus putra/putri/campur.';
        }

        foreach (['latitude' => [-90, 90], 'longitude' => [-180, 180]] as $field => [$min, $max]) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) {
                $errors[] = "kolom \"{$field}\" harus angka antara {$min} sampai {$max}.";
            }
        }

        $campusId = trim((string) ($data['campus_id'] ?? ''));
        if ($campusId !== '' && Campus::find((int) $campusId) === null) {
            $errors[] = "kolom \"campus_id\" {$campusId} tidak ada di database.";
        }

        return $errors;
    }

    private function normalizePrice($value): ?int
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        if ($digits === '' || $digits === null) {
            return null;
        }

        $price = (int) $digits;

        return $price >= 50000 && $price <= 100000000 ? $price : null;
    }

    private function normalizeType($value): ?string
    {
        $type = mb_strtolower(trim((string) $value));

        return in_array($type, ['putra', 'putri', 'campur'], true) ? $type : null;
    }

    private function normalizeFacilities($value): array
    {
        $items = preg_split('/[,;]+/', (string) $value) ?: [];

        return array_values(array_filter(array_map(fn ($f) => mb_strtolower(trim($f)), $items)));
    }

    private function toFloat($value): ?float
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function toInt($value): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function resolveCampusId($value): ?int
    {
        $value = trim((string) $value);

        return $value !== '' ? (int) $value : null;
    }

    private function cleanPhone($value): ?string
    {
        $phone = trim((string) $value);

        return $phone !== '' ? $phone : null;
    }

    private function findDuplicate(string $name, string $address, ?float $lat, ?float $lng): ?Property
    {
        $query = Property::where(DB::raw('LOWER(name)'), mb_strtolower($name));

        $candidate = (clone $query)->where(DB::raw('LOWER(address)'), mb_strtolower($address))->first();
        if ($candidate) {
            return $candidate;
        }

        if ($lat !== null && $lng !== null) {
            $candidates = $query->whereNotNull('latitude')->whereNotNull('longitude')->get();

            foreach ($candidates as $property) {
                $distance = Geo::haversine($lat, $lng, $property->latitude, $property->longitude);

                if ($distance < 0.05) {
                    return $property;
                }
            }
        }

        return null;
    }
}
