<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Property;

/**
 * Smart Match — skor kecocokan 0..100 berdasarkan bobot terdokumentasi:
 *  - Anggaran sewa        : 35%
 *  - Jarak ke kampus      : 25% (hanya jika kedua koordinat tersedia)
 *  - Fasilitas wajib      : 20%
 *  - Kamar tersedia       : 10%
 *  - Total biaya bulanan  : 10% (sewa + biaya kost + pengeluaran lain)
 * Komponen yang datanya tidak tersedia dilewati dan bobot dinormalisasi,
 * sehingga kost tanpa koordinat tidak dihukum/diuntungkan semu.
 */
class SmartMatch
{
    public const WEIGHTS = [
        'budget' => 35,
        'distance' => 25,
        'facilities' => 20,
        'availability' => 10,
        'total_cost' => 10,
    ];

    public static function score(Property $p, array $pref): array
    {
        $parts = [];
        $reasons = [];
        $notes = [];

        $budget = max(0, (int) ($pref['budget'] ?? 0));

        if ($budget > 0 && !$p->hasPrice()) {
            $parts['budget'] = 0.25;
            $notes[] = 'Harga sewa belum diketahui, jadi skor anggaran dibuat rendah. Tanyakan harga ke pengelola.';
        } elseif ($budget > 0) {
            if ($p->price <= $budget) {
                $parts['budget'] = 1.0;
                $reasons[] = 'Harga ' . self::rupiah($p->price) . '/bulan masuk anggaran sewa ' . self::rupiah($budget) . '.';
            } elseif ($p->price <= $budget * 1.15) {
                $parts['budget'] = 0.5;
                $reasons[] = 'Harga ' . self::rupiah($p->price) . '/bulan sedikit di atas anggaran (di bawah 15% dari batas).';
            } else {
                $parts['budget'] = 0.0;
            }
        }

        $campus = $pref['campus'] ?? null;
        $prefDistance = max(0.0, (float) ($pref['max_distance_km'] ?? 0));

        $distance = null;
        if ($campus instanceof Campus && $p->latitude !== null && $p->longitude !== null) {
            $distance = Geo::haversine($p->latitude, $p->longitude, $campus->latitude, $campus->longitude);
        }

        if ($distance !== null) {
            $jarak = '±' . Geo::formatKm($distance) . ' garis lurus ke ' . $campus->short_name . ' (' . $campus->name . ')';
            if ($prefDistance > 0) {
                if ($distance <= $prefDistance) {
                    $parts['distance'] = 1.0;
                    $reasons[] = 'Jarak ' . $jarak . ' — sesuai preferensi maksimal ' . Geo::formatKm($prefDistance) . '.';
                } elseif ($distance <= $prefDistance * 2) {
                    $parts['distance'] = max(0.0, 1 - ($distance - $prefDistance) / $prefDistance * 0.5);
                    $reasons[] = 'Jarak ' . $jarak . ' — sedikit melebihi preferensi ' . Geo::formatKm($prefDistance) . '.';
                } else {
                    $parts['distance'] = 0.0;
                }
            } else {
                $parts['distance'] = max(0.0, 1 - $distance / 10);
                $reasons[] = 'Jarak ' . $jarak . '.';
            }
        } elseif ($campus !== null && $p->campus_id !== null && $p->campus_id !== $campus->id) {
            $parts['distance'] = 0.0;
            $notes[] = 'Kost ini tercatat dekat kampus lain (' . ($p->campus?->short_name ?? 'kampus lain') . '), bukan ' . $campus->short_name . '. Koordinatnya belum diisi sehingga jarak pasti belum bisa dihitung.';
        } elseif ($campus !== null) {
            if ($p->campus_id === $campus->id) {
                $reasons[] = 'Tercatat sebagai kost dekat ' . $campus->short_name . '.';
            }
            $notes[] = 'Jarak ke kampus belum bisa dihitung karena koordinat kost ini belum diisi pemilik/admin.';
        }

        $needed = array_values(array_filter((array) ($pref['facilities'] ?? [])));
        if ($needed !== []) {
            $have = array_map('strtolower', (array) ($p->facilities ?? []));
            $matched = array_values(array_filter($needed, fn ($f) => in_array(mb_strtolower(trim($f)), $have, true)));
            $parts['facilities'] = count($matched) / count($needed);

            if ($matched !== []) {
                $reasons[] = 'Fasilitas pilihanmu tersedia: ' . implode(', ', array_map('ucfirst', $matched)) . '.';
            }
        }

        if (!empty($pref['available_only'])) {
            $parts['availability'] = !$p->is_basic_info && $p->available_rooms > 0 ? 1.0 : 0.0;
            if ($p->available_rooms > 0) {
                $reasons[] = 'Ada ' . $p->available_rooms . ' kamar tersedia saat ini.';
            }
        }

        $extras = max(0, (int) ($pref['extras'] ?? 0));
        $totalBudget = max(0, (int) ($pref['total_budget'] ?? 0));

        if ($totalBudget > 0 && !$p->hasPrice()) {
            $parts['total_cost'] = 0.25;
        } elseif ($totalBudget > 0) {
            $estimate = $p->monthlyCost() + $extras;
            if ($estimate <= $totalBudget) {
                $parts['total_cost'] = 1.0;
                $reasons[] = 'Estimasi total biaya bulanan ' . self::rupiah($estimate) . ' masih dalam anggaran total ' . self::rupiah($totalBudget) . '.';
            } elseif ($estimate <= $totalBudget * 1.1) {
                $parts['total_cost'] = 0.5;
                $reasons[] = 'Estimasi total biaya bulanan ' . self::rupiah($estimate) . ' sedikit melebihi anggaran total (di bawah 10% dari batas).';
            } else {
                $parts['total_cost'] = 0.0;
            }
        }

        $maxWeight = 0;
        $earned = 0;
        foreach (self::WEIGHTS as $key => $weight) {
            if (array_key_exists($key, $parts)) {
                $maxWeight += $weight;
                $earned += $weight * $parts[$key];
            }
        }

        $score = $maxWeight > 0 ? (int) round($earned / $maxWeight * 100) : 0;

        return [
            'score' => $score,
            'reasons' => $reasons,
            'notes' => $notes,
            'distance' => $distance,
        ];
    }

    private static function rupiah($value): string
    {
        return 'Rp' . number_format((int) $value, 0, ',', '.');
    }
}
