<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas perusahaan
    |--------------------------------------------------------------------------
    |
    | Dipakai sebagai kop surat MoU dan invoice, dan sebagai PIHAK KEDUA pada
    | setiap MoU. Tidak disimpan di database: ubah berkas ini, lalu jalankan
    | "php artisan config:clear" kalau konfigurasi sedang di-cache.
    |
    */

    'name' => 'CV. Impost Media Indonesia',

    'address' => 'Gedung Ruko Nomor 3. Jl. Kawamara, Landasan Ulin Tengah, Kec. Liang Anggang, Kota Banjar Baru, Kalimantan Selatan, 70724',

    'city' => 'Banjarbaru',

    'phone' => '+62 831-4780-2761',

    'email' => 'adm.impostmedia@gmail.com',

    'signatory_name' => 'Achmad Fadel Samudera',

    'signatory_position' => 'CEO',

    /*
    | Berkas logo, relatif terhadap public/.
    */

    'logo' => 'pdf/logo.png',

    /*
    |--------------------------------------------------------------------------
    | Isi cetakan invoice
    |--------------------------------------------------------------------------
    |
    | Dicetak apa adanya di bawah tabel invoice. Baris baru dan baris kosong
    | dipertahankan, jadi susunannya di sini persis seperti yang keluar di PDF.
    |
    */

    'invoice_notes' => <<<'TEXT'
        Kami mengucapkan terima kasih atas kepercayaan Anda. Semoga layanan yang kami berikan dapat mendukung keberhasilan dan pertumbuhan bisnis Anda.

        Jika ada pertanyaan terkait invoice ini, silakan hubungi kami di
        adm.impostmedia@gmail.com
        (+62) 831-4780-2761
        TEXT,

    'terms' => <<<'TEXT'
        Jatuh Tempo Pembayaran: 1 hari kalender sejak tanggal invoice.
        Metode Pembayaran: Transfer
        Bank BRI – No. Rek. 727101009145536
        a.n. ACHMAD FADEL SAMUDERA

        Konfirmasi Pembayaran: Mohon kirim bukti transfer melalui WhatsApp/email setelah melakukan pembayaran.

        Kebijakan Layanan: Semua layanan yang telah diberikan tidak dapat dibatalkan dan biaya tidak dapat dikembalikan
        TEXT,

];
