<?php

/* ==========================================
   HELPERS.PHP — Fungsi Global Laravel
   ==========================================

   PENGATURAN HARGA SERVIS
   ========================
   Ubah angka di bawah sesuai tarif Anda.
   Harga sudah dibedakan antara Masjid dan Musholla.
   Format: angka tanpa titik/koma (150000 = Rp 150.000)
*/

const HARGA_MASJID = [
    '1PK' => 150000,
    '2PK' => 200000,
    '5PK' => 350000,
];

const HARGA_MUSHOLLA = [
    '1PK' => 120000,
    '2PK' => 170000,
    '5PK' => 300000,
];

/* ==========================================
   JANGAN UBAH KODE DI BAWAH INI
   ========================================== */

if (!function_exists('getHargaServis')) {
    /**
     * Ambil harga servis berdasarkan tipe lokasi dan PK
     * @param string $tipe  'masjid' atau 'musholla'
     * @param string $pk    '1PK', '2PK', atau '5PK'
     * @return int
     */
    function getHargaServis(string $tipe, string $pk): int
    {
        $daftar = strtolower($tipe) === 'musholla' ? HARGA_MUSHOLLA : HARGA_MASJID;
        return $daftar[$pk] ?? HARGA_MASJID[$pk] ?? 150000;
    }
}

if (!function_exists('terbilang')) {
    /**
     * Konversi angka ke kata dalam Bahasa Indonesia
     * Contoh: 150000 => "seratus lima puluh ribu"
     */
    function terbilang(float $angka): string
    {
        $angka = abs((int) $angka);
        $satuan = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas', 'dua belas', 'tiga belas',
            'empat belas', 'lima belas', 'enam belas',
            'tujuh belas', 'delapan belas', 'sembilan belas',
        ];

        if ($angka === 0)    return 'nol';
        if ($angka < 20)     return $satuan[$angka];
        if ($angka < 100)    return $satuan[(int)($angka / 10)] . ' puluh' . ($angka % 10 ? ' ' . $satuan[$angka % 10] : '');
        if ($angka < 200)    return 'seratus' . ($angka % 100 ? ' ' . terbilang($angka % 100) : '');
        if ($angka < 1000)   return $satuan[(int)($angka / 100)] . ' ratus' . ($angka % 100 ? ' ' . terbilang($angka % 100) : '');
        if ($angka < 2000)   return 'seribu' . ($angka % 1000 ? ' ' . terbilang($angka % 1000) : '');
        if ($angka < 1000000)    return terbilang((int)($angka / 1000)) . ' ribu' . ($angka % 1000 ? ' ' . terbilang($angka % 1000) : '');
        if ($angka < 1000000000) return terbilang((int)($angka / 1000000)) . ' juta' . ($angka % 1000000 ? ' ' . terbilang($angka % 1000000) : '');
        return terbilang((int)($angka / 1000000000)) . ' miliar' . ($angka % 1000000000 ? ' ' . terbilang($angka % 1000000000) : '');
    }
}
