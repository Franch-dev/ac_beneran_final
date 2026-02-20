<?php

if (!function_exists('terbilang')) {
    function terbilang(float $angka): string
    {
        $angka = abs((int) $angka);
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
            'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];

        if ($angka === 0) return 'nol';
        if ($angka < 20) return $satuan[$angka];
        if ($angka < 100) return $satuan[(int)($angka / 10)] . ' puluh' . ($angka % 10 ? ' ' . $satuan[$angka % 10] : '');
        if ($angka < 200) return 'seratus' . ($angka % 100 ? ' ' . terbilang($angka % 100) : '');
        if ($angka < 1000) return $satuan[(int)($angka / 100)] . ' ratus' . ($angka % 100 ? ' ' . terbilang($angka % 100) : '');
        if ($angka < 2000) return 'seribu' . ($angka % 1000 ? ' ' . terbilang($angka % 1000) : '');
        if ($angka < 1000000) return terbilang((int)($angka / 1000)) . ' ribu' . ($angka % 1000 ? ' ' . terbilang($angka % 1000) : '');
        if ($angka < 1000000000) return terbilang((int)($angka / 1000000)) . ' juta' . ($angka % 1000000 ? ' ' . terbilang($angka % 1000000) : '');
        return terbilang((int)($angka / 1000000000)) . ' miliar' . ($angka % 1000000000 ? ' ' . terbilang($angka % 1000000000) : '');
    }
}
