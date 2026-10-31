<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Kost nyata dari halaman web publik (direktori lokal). Hanya info dasar: nama, area, alamat, sumber.
 * Harga/fasilitas diisi hanya bila disebut sumbernya. Status: belum diverifikasi.
 */
class WebKostSeeder extends Seeder
{
    private const LOKA_UNSRI = 'https://lokanesia.com/kost-dekat-unsri-bukit-palembang/5/';
    private const ASIK_UNSRI = 'https://tempatasik.id/tempat/12-kost-dekat-unsri-palembang/';
    private const LOKA_IND = 'https://lokanesia.com/info-kost-di-indralaya-utara-ogan-ilir/';
    private const LOKA_IND_PUTRI = 'https://lokanesia.com/kost-putri-dekat-unsri-indralaya/';
    private const LOKA_IND3 = 'https://lokanesia.com/info-kost-di-indralaya-utara-ogan-ilir/3/';
    private const LOKA_UINB = 'https://lokanesia.com/kost-dekat-uin-palembang-kampus-b/';
    private const LOKA_UINA = 'https://lokanesia.com/kost-dekat-uin-raden-fatah-palembang/3/';
    private const LOKA_UM = 'https://lokanesia.com/kost-dekat-um-palembang/';
    private const ASIK_UM = 'https://tempatasik.id/tempat/17-kost-dekat-universitas-muhammadiyah-palembang-murah/';
    private const LOKA_MDP = 'https://lokanesia.com/kost-dekat-mdp-palembang/';
    private const LOKA_POLSRI = 'https://lokanesia.com/kost-dekat-polsri-palembang/5/';

    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        if (!$admin) {
            return;
        }

        $campus = Campus::pluck('id', 'short_name');

        $rows = [
            ['Kost Putri Melly', 'putri', 'Lorok Pakjo, Ilir Barat I', 'Lorok Pakjo, Ilir Barat I, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Kost NiTiRu', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Sungai Sahang No. 5341, Lorok Pakjo, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Kost Mama Togi', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Lrg. Kerjasama, Lorok Pakjo, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Abu Exclusive Kost', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Bank Raya V No. 5, Lorok Pakjo, Palembang', 'Polsri', self::LOKA_POLSRI],
            ['Kost Khadijah', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Bank Raya 9 No. 1, Lorok Pakjo, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Bidar Kost', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Bidar No. 12 B, Lorok Pakjo, Palembang', 'Polsri', self::LOKA_POLSRI],
            ['Helvan Kost', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Demang IV No. 4787, Lorok Pakjo, Palembang', 'Polsri', self::LOKA_POLSRI],
            ['Royale Kost Demang', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Demang III, Lorok Pakjo, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Kost Bukit 168 Co-Living', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Sungai Sahang No. 5341, Lorok Pakjo, Palembang', 'Unsri (Bukit)', self::ASIK_UNSRI],
            ['Bougenville Kost', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Jl. Lunjuk Jaya No. 75, Lorok Pakjo, Palembang', 'Polsri', self::LOKA_POLSRI],
            ['Nabila Kost', 'unknown', 'Lorok Pakjo, Ilir Barat I', 'Lorong Anggrek No. 1, Palembang', 'Polsri', self::LOKA_POLSRI],
            ["Razkha's Kost", 'unknown', 'Bukit Lama, Ilir Barat I', 'Jl. Srijaya Negara, Lrg. Hasanah No. 176, Bukit Lama, Palembang', 'Unsri (Bukit)', 'https://muriabisnis.com/produk/9282/kost-dekat-unsri-bukit-palembang-razkha-kost'],
            ['Kosan 7 Saudara', 'unknown', 'Bukit Besar, Ilir Barat I', 'Jl. Timor No. 176, Bukit Besar, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Bunda Kost', 'unknown', 'Bukit Lama, Ilir Barat I', 'A1 Perumahan Dosen Unsri, Jl. Masjid Al Gazali, Bukit Lama, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Kosan UNI', 'unknown', 'Bukit Lama, Ilir Barat I', 'Jl. Masjid Al Gazali, Bukit Lama, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],
            ['Kost Gardena', 'putri', 'Bukit Lama, Ilir Barat I', 'Lrg. Hasanah No. 05/183 (seberang Halte Unsri), Bukit Lama, Palembang', 'Unsri (Bukit)', self::ASIK_UNSRI],
            ['OKEY Kost', 'unknown', 'Bukit Lama, Ilir Barat I', 'Lorong Siguntang No. 007, Bukit Lama, Palembang', 'Unsri (Bukit)', self::ASIK_UNSRI],
            ['Dilla Kost', 'unknown', 'Puncak Sekuning, Ilir Barat I', 'Jl. Puncak Sekuning, Palembang', 'Unsri (Bukit)', self::LOKA_UNSRI],

            ['Wisma Amanah Inderalaya', 'unknown', 'Timbangan, Indralaya Utara', 'Jl. Raya Lintas Timur Palembang–Prabumulih KM 32, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ['Gg. Austin Kost Putri', 'putri', 'Timbangan, Indralaya Utara', 'Gg. Austin, Timbangan, Indralaya Utara, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ['Kost Putri HB (H. Bahri)', 'putri', 'Timbangan, Indralaya Utara', 'Jl. Lintas Sumatra No. 468, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ['Pondokan Putri Balqis', 'putri', 'Timbangan, Indralaya Utara', 'Gg. Lampung, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ['Kost Tiga Putri', 'putri', 'Timbangan, Indralaya Utara', 'Gg. Lampung No. 2, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ["Pegagan Family's Kost", 'unknown', 'Timbangan, Indralaya Utara', 'Timbangan, Indralaya Utara, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND],
            ['Asrama Putri Muslimah 2', 'putri', 'Timbangan, Indralaya Utara', 'Jl. Nusantara Gg. Buntu RT 02 LK I, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND_PUTRI],
            ['Barakallah Kost', 'unknown', 'Timbangan, Indralaya Utara', 'Jl. Nusantara, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND3],
            ['Kost Nina', 'unknown', 'Tanjung Pering, Indralaya Utara', 'Jl. Lintas Tengah, Tanjung Pering, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND3],
            ['Kost Mahkota Mutiara 2', 'unknown', 'Timbangan, Indralaya Utara', 'Jl. Lintas Timur (AH25), Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND3],
            ['Kost Pondok 56', 'unknown', 'Timbangan, Indralaya Utara', 'Komplek Perumahan Griya Sejahtera Blok AB.2 No. 1, Jl. Lintas Palembang–Prabumulih, Timbangan, Ogan Ilir', 'Unsri (Indralaya)', self::LOKA_IND3],

            ['Villa Kost', 'unknown', 'Madang, Kemuning', 'Jl. Madang No. 43 (sebelah FK Unsri Madang), Kemuning, Palembang', 'UIN Raden Fatah', 'https://villakost.com/kost-harian-dekat-uin-palembang/', 650000, ['wifi', 'parkir motor', 'dapur'], 'Situs resmi pengelola menyebut sewa bulanan mulai Rp650.000–Rp750.000, juga tersedia harian dan mingguan.'],
            ["T'daon Kost", 'unknown', 'Madang, Kemuning', 'Jl. Madang Dalam II, Kemuning, Palembang', 'UIN Raden Fatah', self::LOKA_UINA],
            ['Amerta Co-living', 'unknown', 'Madang, Kemuning', 'Jl. Madang Dalam II, Kemuning, Palembang', 'UIN Raden Fatah', self::LOKA_UINA],
            ['Kostanisa 2.0', 'unknown', 'Jakabaring, Seberang Ulu I', 'Jl. Panca Usaha No. 2334, Palembang', 'UIN Raden Fatah', self::LOKA_UINB],
            ['Kost Nabila Putri', 'putri', '8 Ulu, Seberang Ulu I', 'Jl. Gub. H. Bastari, 8 Ulu, Palembang', 'UIN Raden Fatah', self::LOKA_UINB],
            ['Kostan 3 Behadeng', 'unknown', 'Seberang Ulu I', 'Lrg. Usaha I No. 2149, Palembang', 'UIN Raden Fatah', self::LOKA_UINB],

            ['Kost Dinda', 'unknown', '13 Ulu, Plaju', 'Jl. Jend. A. Yani Lrg. Gotong Royong No. 567, Palembang', 'Unmuh Palembang', self::LOKA_UM],
            ['Kost Nita', 'unknown', '13 Ulu, Seberang Ulu II', 'Jl. Abdul Kadir No. 586, 13 Ulu, Palembang', 'Unmuh Palembang', self::LOKA_UM],
            ['Star Kost Plaju', 'unknown', 'Plaju', 'Perumahan Komplek Palem, Jl. KH. Balqi No. B15, Plaju, Palembang', 'Unmuh Palembang', self::ASIK_UM],
            ['Edo Kost', 'unknown', 'Plaju', 'Jl. Tangga Takat No. 872, Palembang', 'Unmuh Palembang', self::ASIK_UM],
            ['Kost Tria Putri', 'putri', '16 Ulu, Seberang Ulu II', 'Jl. KH. Balqi, 16 Ulu, Palembang', 'Unmuh Palembang', self::LOKA_UM],
            ['Arzaq Kost Putri', 'putri', 'Plaju', 'Jl. Rukun 1, Palembang', 'Unmuh Palembang', self::LOKA_UM],
            ['Kost F18 IBA', 'unknown', '9 Ilir, Ilir Timur II', 'Jl. Perwari, 9 Ilir, Palembang', 'UBD', 'https://www.boskoss.com/cari-kos/detail-kos/16', 0, ['wifi', 'ac', 'kamar mandi dalam', 'parkir motor'], 'Sumber menyebut fasilitas WiFi, AC, kamar mandi dalam, dan parkir motor.'],

            ['Arindy Kost Putri', 'putri', '9 Ilir, Ilir Timur II', 'Jl. Sikatan No. 21, Palembang', 'MDP', self::LOKA_MDP],
            ['Enter Kost', 'unknown', '9 Ilir, Ilir Timur II', 'Jl. Kenari I No. 1187, Palembang', 'MDP', self::LOKA_MDP],
            ['Kost Sikatan', 'unknown', '9 Ilir, Ilir Timur II', 'Jl. Sikatan No. 15, Palembang', 'MDP', self::LOKA_MDP],
            ['Pondok Sejahtera', 'unknown', '9 Ilir, Ilir Timur II', 'Jl. Bangau No. 1073, Palembang', 'MDP', self::LOKA_MDP],
        ];

        foreach ($rows as $r) {
            [$name, $type, $location, $address, $campusName, $url] = $r;
            $price = $r[6] ?? 0;
            $note = $r[8] ?? null;
            $domain = parse_url($url, PHP_URL_HOST);

            Property::firstOrCreate(
                ['name' => $name, 'address' => $address],
                [
                    'owner_id' => $admin->id,
                    'type' => $type,
                    'location' => $location,
                    'campus_id' => $campus[$campusName] ?? null,
                    'price' => $price,
                    'rooms' => 0,
                    'available_rooms' => 0,
                    'facilities' => $r[7] ?? [],
                    'description' => trim(($note ? $note . ' ' : '') . 'Informasi dasar dikumpulkan dari halaman publik ' . $domain . '. Harga, fasilitas, dan ketersediaan kamar belum diverifikasi Kostly — konfirmasi langsung ke pengelola sebelum survei atau membayar.'),
                    'status' => Property::STATUS_APPROVED,
                    'source' => 'web',
                    'source_url' => $url,
                    'is_basic_info' => true,
                    'is_demo' => false,
                ]
            );
        }
    }
}
