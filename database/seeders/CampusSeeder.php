<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

/**
 * Koordinat & alamat kampus bersumber dari:
 *  - OpenStreetMap via Nominatim (© OpenStreetMap contributors, ODbL) — diakses 2026-06.
 *  - Alamat resmi dari situs masing-masing kampus (radenfatah.ac.id, ukmc.ac.id, univpgri-palembang.ac.id).
 * Unsri sengaja dipisah menjadi DUA baris: Kampus Bukit (Kota Palembang) dan
 * Kampus Indralaya (Kabupaten Ogan Ilir) agar jarak tidak tertukar.
 */
class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            [
                'name' => 'Universitas Sriwijaya — Kampus Bukit',
                'short_name' => 'Unsri (Bukit)',
                'city' => 'Palembang',
                'address' => 'Jl. Al Ghazali, Bukit Besar, Ilir Barat I, Kota Palembang',
                'latitude' => -2.9855104,
                'longitude' => 104.7334676,
                'notes' => 'Kampus Unsri di Kota Palembang. Jangan tertukar dengan Kampus Indralaya.',
            ],
            [
                'name' => 'Universitas Sriwijaya — Kampus Indralaya',
                'short_name' => 'Unsri (Indralaya)',
                'city' => 'Indralaya',
                'address' => 'Jl. Raya Palembang-Indralaya, Tanjung Pering, Indralaya, Kab. Ogan Ilir',
                'latitude' => -3.2255315,
                'longitude' => 104.6459991,
                'notes' => 'Kampus utama Unsri di Kabupaten Ogan Ilir — SEKITAR 27 KM dari pusat Kota Palembang, bukan di Palembang.',
            ],
            [
                'name' => 'Universitas Islam Negeri Raden Fatah Palembang',
                'short_name' => 'UIN Raden Fatah',
                'city' => 'Palembang',
                'address' => 'Jl. Prof. K.H. Zainal Abidin Fikri KM 3,5 (Kampus Sudirman), Kemuning',
                'latitude' => -2.9645025,
                'longitude' => 104.7487634,
                'notes' => 'UIN juga memiliki kampus Jakabaring (Jl. Pangeran Ratu, 5 Ulu).',
            ],
            [
                'name' => 'Universitas Muhammadiyah Palembang',
                'short_name' => 'Unmuh Palembang',
                'city' => 'Palembang',
                'address' => 'Jl. Jend. Ahmad Yani, Seberang Ulu I',
                'latitude' => -2.9964781,
                'longitude' => 104.7782111,
                'notes' => null,
            ],
            [
                'name' => 'Politeknik Negeri Sriwijaya',
                'short_name' => 'Polsri',
                'city' => 'Palembang',
                'address' => 'Jl. Karya I, Kertapati, Kota Palembang',
                'latitude' => -3.0435843,
                'longitude' => 104.7267526,
                'notes' => null,
            ],
            [
                'name' => 'Universitas Bina Darma',
                'short_name' => 'UBD',
                'city' => 'Palembang',
                'address' => 'Jl. Jend. Ahmad Yani, Seberang Ulu I',
                'latitude' => -2.9995597,
                'longitude' => 104.7705751,
                'notes' => null,
            ],
            [
                'name' => 'Universitas PGRI Palembang',
                'short_name' => 'PGRI Palembang',
                'city' => 'Palembang',
                'address' => 'Jl. Jend. Ahmad Yani, Lorong Gotong Royong, 9/10 Ulu, Seberang Ulu I',
                'latitude' => -2.9948424,
                'longitude' => 104.7764785,
                'notes' => 'Koordinat dari titik OSM area Jl. Gotong Royong (alamat resmi kampus).',
            ],
            [
                'name' => 'Universitas Multi Data Palembang',
                'short_name' => 'MDP',
                'city' => 'Palembang',
                'address' => 'Jl. Jenderal Sudirman, Ilir Barat I',
                'latitude' => -2.9626503,
                'longitude' => 104.7399804,
                'notes' => 'Titik OSM Kampus B; MDP memiliki beberapa gedung di kawasan Sudirman.',
            ],
            [
                'name' => 'Universitas Indo Global Mandiri',
                'short_name' => 'IGM',
                'city' => 'Palembang',
                'address' => 'Jl. Jenderal Sudirman, Ilir Barat I',
                'latitude' => -2.9634196,
                'longitude' => 104.7408102,
                'notes' => null,
            ],
            [
                'name' => 'Universitas Tridinanti Palembang',
                'short_name' => 'Tridinanti',
                'city' => 'Palembang',
                'address' => 'Jl. Kapten Marzuki, Ilir Timur I',
                'latitude' => -2.9714468,
                'longitude' => 104.7460183,
                'notes' => null,
            ],
            [
                'name' => 'Universitas Katolik Musi Charitas',
                'short_name' => 'UKMC',
                'city' => 'Palembang',
                'address' => 'Jl. Bangau No. 60, Lawang Kidul, Ilir Timur II (Kampus Bangau)',
                'latitude' => -2.9674911,
                'longitude' => 104.7665415,
                'notes' => 'UKMC juga memiliki Kampus Burlian di Jl. Kol. H. Burlian No. 204.',
            ],
        ];

        foreach ($campuses as $campus) {
            Campus::firstOrCreate(
                ['name' => $campus['name']],
                $campus
            );
        }
    }
}
