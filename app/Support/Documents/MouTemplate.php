<?php

namespace App\Support\Documents;

use App\Models\Contract;

/**
 * Satu-satunya susunan pasal MoU yang dipakai aplikasi. Ruang lingkup dan
 * hak & kewajiban ditulis AI mengikuti paket yang dipilih; skema biaya,
 * ketentuan visit, dan definisi KPI disunting manual sesuai paket klien
 * lewat editor dokumen per kontrak; sisanya pasal hukum yang tetap.
 */
class MouTemplate
{
    /**
     * Pasal 4 (Ketentuan Visit & Produksi Tambahan) cuma relevan untuk MoU yang
     * salah satu paketnya ditandai butuh kunjungan lokasi. Kalau tidak ada
     * $contract (mis. dipakai WriteContractClauses buat mendata pasal AI),
     * pasal ini tetap disertakan supaya perilakunya tidak berubah.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function blocks(?Contract $contract = null): array
    {
        $showVisitClause = $contract === null || $contract->requiresVisitClause();

        $sections = array_values(array_filter([
            fn (int $number): array => self::pasal1($number),
            fn (int $number): array => self::pasal2($number),
            fn (int $number): array => self::pasal3($number),
            $showVisitClause ? fn (int $number): array => self::pasal4($number) : null,
            fn (int $number): array => self::pasal5($number),
            fn (int $number): array => self::pasal6($number),
            fn (int $number): array => self::pasal7($number),
            fn (int $number): array => self::pasal8($number),
            fn (int $number): array => self::pasal9($number),
            fn (int $number): array => self::pasal10($number),
            fn (int $number): array => self::pasal11($number),
            fn (int $number): array => self::pasal12($number),
            fn (int $number): array => self::pasal13($number),
            fn (int $number): array => self::pasal14($number),
        ]));

        $pasal = [];

        foreach ($sections as $number => $section) {
            $pasal = array_merge($pasal, $section($number + 1));
        }

        return BlockSchema::normalize(array_merge(
            self::opening(),
            $pasal,
            self::signature(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return BlockSchema::mergeSettings([
            'page' => ['marginTop' => 46, 'marginRight' => 18, 'marginBottom' => 18, 'marginLeft' => 18],
            'font' => ['family' => 'serif', 'size' => 11, 'lineHeight' => 1.6],
            'header' => [
                'enabled' => true,
                'logo' => true,
                'logoWidth' => 26,
                'title' => '{{ perusahaan.nama }}',
                'lines' => ['{{ perusahaan.alamat }}', 'Email : {{ perusahaan.email }}'],
                'rule' => true,
            ],
            'runningTitle' => ['enabled' => true, 'text' => 'Perjanjian Kerja Sama'],
            'watermark' => ['enabled' => true, 'width' => 110, 'opacity' => 0.08],
            'footer' => ['enabled' => false, 'text' => '', 'pageNumber' => true],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function opening(): array
    {
        return [
            self::paragraph('<p>Nomor: <strong>{{ dokumen.nomor }}</strong></p>', align: 'left', spaceBefore: 6),
            self::paragraph(
                '<p>Pada hari ini, {{ dokumen.tanggal_panjang }} dibuat dan ditandatangani Perjanjian Kerja Sama '
                .'(selanjutnya disebut &ldquo;Perjanjian&rdquo;) oleh pihak-pihak sebagai berikut:</p>',
            ),
            [
                'type' => BlockSchema::DEFINITION,
                'title' => '1. Pihak Pertama :',
                'rows' => [
                    ['label' => 'Nama', 'value' => '{{ klien.perusahaan }}'],
                    ['label' => 'Alamat', 'value' => '{{ klien.alamat }}'],
                    ['label' => 'No. Telp', 'value' => '{{ klien.telepon }}'],
                ],
                'labelWidth' => 14,
                'indent' => 1,
                'spaceAfter' => 8,
            ],
            [
                'type' => BlockSchema::DEFINITION,
                'title' => '2. Pihak Kedua :',
                'rows' => [
                    ['label' => 'Nama', 'value' => '{{ perusahaan.nama }}'],
                    ['label' => 'Alamat', 'value' => '{{ perusahaan.alamat }}'],
                    ['label' => 'No. Telp', 'value' => '{{ perusahaan.telepon }}'],
                ],
                'labelWidth' => 14,
                'indent' => 1,
                'spaceAfter' => 12,
            ],
            self::paragraph(
                '<p>Menyatakan sepakat untuk melakukan kerja sama sebagaimana diatur dalam perjanjian ini '
                .'dengan ketentuan sebagai berikut:</p>',
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal1(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'RUANG LINGKUP KERJASAMA'),
            [
                self::list('lower-alpha', ['Pihak Pertama adalah pemilik usaha yang menyediakan:']),
                self::aiClause(
                    id: 'pasal-ruang-lingkup-pihak-pertama',
                    topic: 'Data, akses, dan dukungan yang disediakan PIHAK PERTAMA (klien) agar pekerjaan '
                        .'PIHAK KEDUA bisa berjalan',
                    instruction: 'Sebutkan data, akses, atau materi yang disediakan klien sesuai bidang usahanya, '
                        .'bukan pekerjaan yang dikerjakan PIHAK KEDUA. Tulis sebagai kalimat perintah tanpa subjek.',
                    count: 5,
                    indent: 2,
                    fallback: 'Menyediakan data, akses, dan materi pendukung yang dibutuhkan sesuai ruang lingkup '
                        .'pekerjaan pada Perjanjian ini.',
                ),
                self::list('lower-alpha', [
                    'Pihak Kedua memberikan layanan untuk mendukung branding dan penjualan paket layanan '
                    .'Pihak Pertama, meliputi:',
                ], start: 2),
                self::aiClause(
                    id: 'pasal-ruang-lingkup-pihak-kedua',
                    topic: 'Layanan yang dikerjakan PIHAK KEDUA sesuai paket yang dibeli',
                    instruction: 'Jabarkan pekerjaan yang benar-benar dikerjakan sesuai paket yang dibeli, '
                        .'satu poin per bidang pekerjaan. Jangan menyebut harga, jumlah pembayaran, atau '
                        .'nama perusahaan.',
                    count: 6,
                    indent: 2,
                    fallback: 'Pekerjaan dilaksanakan sesuai paket layanan dan rincian yang tercantum pada '
                        .'Pasal 3 Perjanjian ini.',
                ),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal2(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'STATUS HUBUNGAN HUKUM'),
            [
                self::list('lower-alpha', [
                    'Para pihak sepakat bahwa Perjanjian yang dibuat <strong>bukan</strong> merupakan perjanjian '
                    .'hubungan kerja dan Pihak Kedua bukan merupakan karyawan, tetapi para pihak merupakan '
                    .'<strong>Mitra Kerja</strong> yang sejajar dan mandiri untuk melakukan tindakan hukum sesuai '
                    .'yang telah disepakati antara Para Pihak sejauh tidak melanggar ketentuan pasal 9 dan 10 serta '
                    .'peraturan perundang-undangan yang berlaku.',
                    'Oleh karena itu, masing-masing adalah pihak yang mandiri maka dengan sendiri menyatakan '
                    .'Perjanjian ini tunduk pada ketentuan Kitab Undang-Undang Hukum Perdata.',
                    'Atas Pelaksanaan tugas yang dilakukan oleh Pihak Kedua, maka Pihak Pertama wajib untuk '
                    .'memberikan kompensasi tertentu kepada Pihak Kedua sesuai kesepakatan atas hasil jasa yang '
                    .'telah dilakukannya.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal3(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'SKEMA BIAYA & BENEFIT PAKET'),
            [
                self::list('lower-alpha', ['<strong>Rincian Biaya</strong>']),
                [
                    'type' => BlockSchema::ITEMS_TABLE,
                    'columns' => ['no', 'name', 'quantity', 'unit_price', 'amount'],
                    'showSubtotal' => true,
                    'showTax' => true,
                    'showTotal' => true,
                    'spaceAfter' => 8,
                ],
                self::paragraph(
                    '<p>Terbilang: <em>{{ nilai.terbilang }}</em>.</p>',
                    align: 'left',
                    spaceAfter: 8,
                ),
                self::paragraph('<p>{{ dokumen.pembayaran }}</p>', spaceAfter: 10),
                self::list('lower-alpha', ['<strong>Syarat &amp; Ketentuan</strong>'], start: 2),
                self::list('disc', [
                    'Masa kontrak berlangsung selama {{ dokumen.durasi }} sejak tanggal penandatanganan Perjanjian.',
                    'Pihak Pertama bebas melakukan revisi selama masa kontrak dengan garansi aktif.',
                    'Upgrade paket dilakukan melalui persetujuan tertulis kedua belah pihak.',
                    'Biaya pihak ketiga di luar layanan yang termuat dalam perjanjian ini menjadi tanggung jawab Pihak Pertama.',
                    'Ketentuan visit tambahan dan produksi di luar kuota paket diatur pada Pasal 4.',
                ], indent: 2),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal4(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'KETENTUAN VISIT & PRODUKSI TAMBAHAN'),
            [
                self::list('lower-alpha', [
                    'Paket sudah termasuk 2x visit/bulan.',
                    'Jika diperlukan visit tambahan (operasional) sesuai request dari Pihak Pertama, maka '
                    .'dikenakan biaya:',
                ]),
                self::list('disc', [
                    'Area Banjarbaru &amp; Landasan Ulin: Rp300.000/visit (tim 3 orang)',
                    'Area Banjarmasin &amp; Martapura: Rp450.000/visit',
                ], indent: 2),
                self::paragraph(
                    '<p>Konten yang dihasilkan tetap masuk ke kuota paket utama (tidak menambah jumlah konten).</p>',
                    indent: 1,
                ),
                self::list('lower-alpha', [
                    'Jika diperlukan produksi konten tambahan di luar kuota paket, maka dikenakan biaya '
                    .'Rp1.500.000 per konten tambahan. Output: 1 video reels atau 1 dokumentasi proyek premium '
                    .'(terpisah dari paket utama). Biaya sudah termasuk tim kreatif, editing, dan revisi.',
                    'Biaya add-on visit, baik operasional maupun produksi tambahan, dibayarkan penuh sebelum '
                    .'visit dijadwalkan.',
                ], start: 3),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal5(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'DEFINISI OMZET & METODE PERHITUNGAN KPI'),
            [
                self::list('lower-alpha', [
                    'Data omzet dan capaian Pihak Pertama diverifikasi melalui sistem pencatatan/transaksi '
                    .'yang digunakan Pihak Pertama beserta laporan transaksi pendukung.',
                    '<strong>Target KPI tercapai apabila</strong> terjadi pertumbuhan branding, reach media '
                    .'sosial, dan/atau rata-rata akuisisi klien sesuai kesepakatan Para Pihak selama periode '
                    .'evaluasi sebagaimana diatur pada Pasal 7.',
                    'Target sebagaimana dimaksud pada huruf b dihitung sebagai <strong>rata-rata per '
                    .'bulan</strong> selama periode evaluasi.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal6(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'MEKANISME VERIFIKASI & SENGKETA DATA'),
            [
                self::list('lower-alpha', [
                    'Seluruh laporan omzet wajib dapat diverifikasi melalui sistem pencatatan transaksi yang '
                    .'digunakan Pihak Pertama beserta laporan transaksi pendukung.',
                    'Perhitungan lead/prospek didasarkan pada data komunikasi masuk melalui kanal yang '
                    .'digunakan Pihak Pertama untuk menerima pelanggan.',
                    'Apabila terjadi perselisihan atau perbedaan data, Para Pihak sepakat untuk menunjuk '
                    .'auditor independen paling lambat dalam waktu 14 (empat belas) hari kerja sejak '
                    .'sengketa dinyatakan.',
                    'Hasil audit yang dilakukan oleh auditor independen bersifat final dan mengikat Para Pihak.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal7(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'TERMINASI BERDASARKAN KPI'),
            [
                self::list('lower-alpha', [
                    'Jika target KPI sebagaimana dimaksud Pasal 5 belum tercapai dalam periode evaluasi yang '
                    .'disepakati, Pihak Pertama berhak:',
                ]),
                self::list('disc', [
                    'Melanjutkan kontrak secara bulanan dengan kesepakatan tertulis terpisah dari Perjanjian ini; atau',
                    'Melakukan pengakhiran kontrak (terminasi) sesuai ketentuan Pasal ini.',
                ], indent: 2),
                self::list('lower-alpha', ['Opsi Terminasi:'], start: 2),
                self::list('decimal', [
                    'Pengakhiran dapat dilakukan sesuai ketentuan durasi dan pemberitahuan sebagaimana '
                    .'diatur dalam Perjanjian ini, tanpa mengaitkannya dengan target omzet atau akuisisi pasien.',
                    'Evaluasi dapat dilakukan secara berkala sesuai kesepakatan Para Pihak untuk menilai '
                    .'pencapaian KPI branding, reach, engagement, dan performa konten.',
                    'Pengakhiran berlaku sesuai dengan tanggal berakhirnya periode kerja sama yang disepakati '
                    .'Para Pihak, kecuali terdapat perpanjangan tertulis.',
                ], indent: 2),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal8(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'HAK DAN KEWAJIBAN'),
            [
                self::list('lower-alpha', ['<strong>Pihak Pertama</strong>']),
                self::aiClause(
                    id: 'pasal-kewajiban-pihak-pertama',
                    topic: 'Kewajiban PIHAK PERTAMA (klien)',
                    instruction: 'Sebutkan apa saja yang harus disediakan atau disetujui klien supaya '
                        .'pekerjaan ini bisa berjalan. Tulis sebagai kalimat perintah tanpa subjek, '
                        .'mis. "Menyediakan logo perusahaan." Jangan menyinggung kewajiban PIHAK KEDUA.',
                    count: 5,
                    indent: 2,
                    fallback: 'Menyediakan logo, profil perusahaan, data layanan, serta materi pendukung '
                        .'yang dibutuhkan, dan memberikan persetujuan atas draft yang diajukan.',
                ),
                self::list('lower-alpha', ['<strong>Pihak Kedua</strong>'], start: 2),
                self::aiClause(
                    id: 'pasal-kewajiban-pihak-kedua',
                    topic: 'Kewajiban dan batasan tanggung jawab PIHAK KEDUA (penyedia jasa)',
                    instruction: 'Sebutkan kewajiban pelaksanaan pekerjaan, lalu tutup dengan batasan '
                        .'tanggung jawab atas hal-hal di luar kendali penyedia jasa untuk jenis layanan '
                        .'ini -- mis. algoritma platform, jangkauan organik, atau hasil penjualan. '
                        .'Tulis kewajibannya sebagai kalimat perintah tanpa subjek.',
                    count: 7,
                    indent: 2,
                    fallback: 'Melaksanakan pekerjaan sesuai ruang lingkup yang disepakati, menjaga '
                        .'kerahasiaan data PIHAK PERTAMA, memberikan update progres, dan menyerahkan '
                        .'hasil sesuai spesifikasi. PIHAK KEDUA tidak menjamin capaian angka tertentu '
                        .'yang dipengaruhi faktor di luar kendalinya.',
                ),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal9(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'KERAHASIAAN'),
            [
                self::list('lower-alpha', [
                    'Pihak Kedua wajib menjaga kerahasiaan semua informasi dan data yang diberikan oleh Pihak '
                    .'Pertama, baik berupa informasi perusahaan, laporan keuangan, produk, dan materi terkait '
                    .'lainnya, yang dapat merugikan Pihak Pertama jika dibocorkan kepada pihak ketiga.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal10(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'PENYELESAIAN & PERSELISIHAN'),
            [
                self::list('lower-alpha', [
                    'Apabila terjadi perselisihan dalam pelaksanaan perjanjian ini, maka kedua belah pihak sepakat '
                    .'untuk menyelesaikan melalui musyawarah untuk mufakat.',
                    'Kedua pihak bisa sepakat menunjuk auditor independent untuk verifikasi dalam 14 hari kerja '
                    .'untuk menyelesaikan persengketaan apabila masih tidak bisa diselesaikan.',
                    'Jika penyelesaian melalui musyawarah dan auditor independent tidak dapat tercapai, maka '
                    .'perselisihan akan diselesaikan melalui jalur hukum sesuai dengan peraturan perundang-undangan '
                    .'yang berlaku di Indonesia.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal11(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'PERUBAHAN PERJANJIAN'),
            [
                self::list('lower-alpha', [
                    'Setiap perubahan, penambahan, atau pengurangan terhadap ketentuan dalam Perjanjian ini '
                    .'hanya sah apabila dibuat secara tertulis dan disetujui oleh Para Pihak dalam bentuk adendum.',
                    'Adendum yang telah disepakati merupakan satu kesatuan dan bagian yang tidak terpisahkan '
                    .'dari Perjanjian ini.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal12(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'FORCE MAJEURE'),
            [
                self::list('lower-alpha', [
                    'Kedua belah pihak tidak akan bertanggung jawab atas keterlambatan atau kegagalan dalam '
                    .'melaksanakan kewajibannya jika disebabkan oleh keadaan yang tidak dapat dikendalikan oleh '
                    .'salah satu pihak, seperti bencana alam, perang, kerusuhan, atau keadaan darurat lainnya.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal13(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'PENGAKHIRAN'),
            [
                self::list('lower-alpha', [
                    'Para Pihak sepakat untuk menjalankan kontrak ini selama {{ dokumen.durasi }} dengan opsi '
                    .'pembayaran sesuai Pasal 3 serta opsi terminasi sesuai Pasal 7.',
                    'Apabila target KPI pada periode evaluasi pertama tercapai, kontrak dilanjutkan sesuai '
                    .'kesepakatan Para Pihak dengan tetap mengacu pada ketentuan terminasi khusus pada masa '
                    .'lanjutan sebagaimana diatur dalam Pasal 7.',
                    'Tidak ada opsi terminasi dan pengakhiran kontrak selain yang telah diatur secara tegas '
                    .'dalam Pasal 7 Perjanjian ini.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasal14(int $number): array
    {
        return array_merge(
            self::pasalTitle($number, 'PENUTUP'),
            [
                self::list('lower-alpha', [
                    'MoU ini dibuat rangkap 2, masing-masing bermaterai, dan memiliki kekuatan hukum yang sama.',
                    'Berlaku efektif sejak tanggal penandatanganan kedua belah pihak.',
                    'Dengan ditandatanganinya MoU ini, Para Pihak sepakat untuk mematuhi seluruh ketentuan di dalamnya.',
                ]),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function signature(): array
    {
        return [
            ['type' => BlockSchema::PAGEBREAK],
            [
                'type' => BlockSchema::SIGNATURE,
                'columns' => [
                    [
                        'title' => 'CLIENT',
                        'rows' => [
                            ['label' => 'NAME :', 'value' => '{{ pihak1.nama }}'],
                            ['label' => 'COMPANY', 'value' => '{{ klien.perusahaan }}'],
                            ['label' => 'DATE :', 'value' => '{{ dokumen.tanggal_panjang }}'],
                        ],
                        'caption' => "{{ pihak1.jabatan }}\n{{ klien.perusahaan }}",
                        'name' => '{{ pihak1.nama }}',
                        'stamp' => true,
                        'signature' => false,
                    ],
                    [
                        'title' => 'THE COMPANY',
                        'rows' => [
                            ['label' => 'NAME :', 'value' => '{{ pihak2.nama }}'],
                            ['label' => 'COMPANY', 'value' => '{{ perusahaan.nama }}'],
                            ['label' => 'DATE :', 'value' => '{{ dokumen.tanggal_panjang }}'],
                        ],
                        'caption' => "{{ pihak2.jabatan }}\n{{ perusahaan.nama }}",
                        'name' => '{{ pihak2.nama }}',
                        'stamp' => true,
                        'signature' => true,
                    ],
                ],
                'gap' => 32,
                'spaceBefore' => 24,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pasalTitle(int $number, string $title): array
    {
        return [
            [
                'type' => BlockSchema::HEADING,
                'text' => 'PASAL '.$number,
                'level' => 2,
                'align' => 'center',
                'spaceBefore' => 18,
                'spaceAfter' => 0,
            ],
            [
                'type' => BlockSchema::HEADING,
                'text' => $title,
                'level' => 2,
                'align' => 'center',
                'spaceBefore' => 0,
                'spaceAfter' => 10,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function paragraph(
        string $html,
        string $align = 'justify',
        int $spaceBefore = 0,
        int $spaceAfter = 6,
        int $indent = 0,
    ): array {
        return [
            'type' => BlockSchema::PARAGRAPH,
            'html' => $html,
            'align' => $align,
            'indent' => $indent,
            'spaceBefore' => $spaceBefore,
            'spaceAfter' => $spaceAfter,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function aiClause(
        string $id,
        string $topic,
        string $instruction,
        int $count,
        string $fallback,
        int $indent = 1,
    ): array {
        return [
            'id' => $id,
            'type' => BlockSchema::AI_CLAUSE,
            'topic' => $topic,
            'instruction' => $instruction,
            'format' => 'list',
            'style' => 'decimal',
            'count' => $count,
            'fallback' => $fallback,
            'align' => 'justify',
            'indent' => $indent,
            'spaceAfter' => 6,
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private static function list(string $style, array $items, int $indent = 1, int $start = 1): array
    {
        return [
            'type' => BlockSchema::LIST,
            'style' => $style,
            'items' => $items,
            'align' => 'justify',
            'indent' => $indent,
            'start' => $start,
            'spaceAfter' => 6,
        ];
    }
}
