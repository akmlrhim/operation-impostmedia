<?php

namespace App\Support\Documents;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlockSchema
{
    public const HEADING = 'heading';

    public const PARAGRAPH = 'paragraph';

    public const LIST = 'list';

    public const DEFINITION = 'definition';

    public const AI_CLAUSE = 'ai_clause';

    public const ITEMS_TABLE = 'items_table';

    public const TABLE = 'table';

    public const SIGNATURE = 'signature';

    public const SPACER = 'spacer';

    public const PAGEBREAK = 'pagebreak';

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function catalog(): array
    {
        return [
            ['value' => self::HEADING, 'label' => 'Judul', 'description' => 'PASAL 1, RUANG LINGKUP KERJASAMA'],
            ['value' => self::PARAGRAPH, 'label' => 'Paragraf', 'description' => 'Teks mengalir, rata kiri-kanan'],
            ['value' => self::LIST, 'label' => 'Daftar', 'description' => 'Bullet, angka, huruf, atau romawi'],
            ['value' => self::DEFINITION, 'label' => 'Blok Pihak', 'description' => 'Nama : ... / Alamat : ... / No. Telp : ...'],
            ['value' => self::AI_CLAUSE, 'label' => 'Pasal AI', 'description' => 'Isi pasal ditulis AI mengikuti layanan di MoU'],
            ['value' => self::ITEMS_TABLE, 'label' => 'Tabel Rincian', 'description' => 'Otomatis dari item MoU beserta subtotal'],
            ['value' => self::TABLE, 'label' => 'Tabel Manual', 'description' => 'Baris dan kolom yang diisi sendiri'],
            ['value' => self::SIGNATURE, 'label' => 'Tanda Tangan', 'description' => 'Dua kolom, meterai, dan tanda tangan'],
            ['value' => self::SPACER, 'label' => 'Jarak', 'description' => 'Ruang kosong vertikal'],
            ['value' => self::PAGEBREAK, 'label' => 'Ganti Halaman', 'description' => 'Paksa isi berikutnya mulai di halaman baru'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function typeValues(): array
    {
        return array_column(self::catalog(), 'value');
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        return match ($type) {
            self::HEADING => [
                'text' => 'PASAL 1',
                'level' => 2,
                'align' => 'center',
                'uppercase' => true,
                'spaceBefore' => 12,
                'spaceAfter' => 6,
            ],
            self::PARAGRAPH => [
                'html' => '<p>Tulis isi pasal di sini.</p>',
                'align' => 'justify',
                'indent' => 0,
                'spaceBefore' => 0,
                'spaceAfter' => 6,
            ],
            self::LIST => [
                'style' => 'disc',
                'items' => ['Butir pertama'],
                'align' => 'justify',
                'indent' => 1,
                'start' => 1,
                'spaceBefore' => 0,
                'spaceAfter' => 6,
            ],
            self::DEFINITION => [
                'title' => 'Pihak Pertama :',
                'rows' => [
                    ['label' => 'Nama', 'value' => '{{ klien.perusahaan }}'],
                    ['label' => 'Alamat', 'value' => '{{ klien.alamat }}'],
                    ['label' => 'No. Telp', 'value' => '{{ klien.telepon }}'],
                ],
                'labelWidth' => 30,
                'indent' => 1,
                'spaceBefore' => 0,
                'spaceAfter' => 6,
            ],
            self::AI_CLAUSE => [
                'topic' => 'Hak dan Kewajiban Para Pihak',
                'instruction' => '',
                'format' => 'list',
                'style' => 'decimal',
                'count' => 5,
                'fallback' => 'Hak dan kewajiban PARA PIHAK dilaksanakan sesuai ruang lingkup pekerjaan yang disepakati dalam dokumen ini.',
                'align' => 'justify',
                'indent' => 1,
                'spaceBefore' => 0,
                'spaceAfter' => 6,
            ],
            self::ITEMS_TABLE => [
                'columns' => ['no', 'name', 'quantity', 'unit_price', 'amount'],
                'showSubtotal' => true,
                'showTax' => true,
                'showTotal' => true,
                'spaceBefore' => 6,
                'spaceAfter' => 6,
            ],
            self::TABLE => [
                'head' => ['Kolom 1', 'Kolom 2'],
                'rows' => [['', '']],
                'widths' => [50, 50],
                'bordered' => true,
                'spaceBefore' => 6,
                'spaceAfter' => 6,
            ],
            self::SIGNATURE => [
                'columns' => [
                    [
                        'title' => 'CLIENT',
                        'rows' => [
                            ['label' => 'NAME :', 'value' => '{{ klien.kontak }}'],
                            ['label' => 'COMPANY', 'value' => '{{ klien.perusahaan }}'],
                            ['label' => 'DATE :', 'value' => '{{ dokumen.tanggal_panjang }}'],
                        ],
                        'caption' => 'CEO {{ klien.perusahaan }}',
                        'name' => '{{ klien.kontak }}',
                        'stamp' => true,
                        'signature' => false,
                    ],
                    [
                        'title' => 'THE COMPANY',
                        'rows' => [
                            ['label' => 'NAME :', 'value' => '{{ perusahaan.penandatangan }}'],
                            ['label' => 'COMPANY', 'value' => '{{ perusahaan.nama }}'],
                            ['label' => 'DATE :', 'value' => '{{ dokumen.tanggal_panjang }}'],
                        ],
                        'caption' => '{{ perusahaan.jabatan_penandatangan }}',
                        'name' => '{{ perusahaan.penandatangan }}',
                        'stamp' => true,
                        'signature' => true,
                    ],
                ],
                'gap' => 32,
                'spaceBefore' => 12,
                'spaceAfter' => 0,
            ],
            self::SPACER => ['height' => 12],
            self::PAGEBREAK => [],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function settingDefaults(): array
    {
        return [
            'page' => [
                'marginTop' => 42,
                'marginRight' => 18,
                'marginBottom' => 18,
                'marginLeft' => 18,
            ],
            'font' => [
                'family' => 'serif',
                'size' => 11,
                'lineHeight' => 1.6,
            ],
            'header' => [
                'enabled' => true,
                'logo' => true,
                'logoWidth' => 26,
                'title' => '{{ perusahaan.nama }}',
                'lines' => ['{{ perusahaan.alamat }}', 'Email : {{ perusahaan.email }}'],
                'rule' => true,
            ],
            'runningTitle' => [
                'enabled' => true,
                'text' => 'Perjanjian Kerja Sama',
            ],
            'watermark' => [
                'enabled' => true,
                'width' => 110,
                'opacity' => 0.08,
            ],
            'footer' => [
                'enabled' => false,
                'text' => '',
                'pageNumber' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function mergeSettings(array $settings): array
    {
        $merged = self::settingDefaults();

        foreach ($merged as $group => $defaults) {
            $given = Arr::get($settings, $group);

            if (is_array($given)) {
                $merged[$group] = array_merge($defaults, array_intersect_key($given, $defaults));
            }
        }

        $merged['header']['lines'] = array_values(array_filter(
            array_map(fn ($line) => is_string($line) ? $line : '', (array) $merged['header']['lines']),
            fn (string $line) => $line !== '',
        ));

        return $merged;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $blocks): array
    {
        $known = self::typeValues();
        $result = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;

            if (! is_string($type) || ! in_array($type, $known, true)) {
                continue;
            }

            $defaults = self::defaults($type);

            $result[] = array_merge(
                ['id' => is_string($block['id'] ?? null) ? $block['id'] : (string) Str::uuid()],
                $defaults,
                array_intersect_key($block, $defaults),
                ['type' => $type],
            );
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<string, array<string, mixed>>
     */
    public static function aiClauses(array $blocks): array
    {
        $clauses = [];

        foreach (self::normalize($blocks) as $block) {
            if ($block['type'] === self::AI_CLAUSE) {
                $clauses[(string) $block['id']] = $block;
            }
        }

        return $clauses;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function starterBlocks(): array
    {
        return self::normalize([
            ['type' => self::HEADING, 'text' => 'Perjanjian Kerja Sama', 'level' => 1, 'uppercase' => false, 'spaceBefore' => 0],
            ['type' => self::PARAGRAPH, 'html' => '<p>Nomor: <strong>{{ dokumen.nomor }}</strong></p>', 'align' => 'left'],
            ['type' => self::PARAGRAPH, 'html' => '<p>Pada hari ini, {{ dokumen.hari }}, tanggal {{ dokumen.tanggal_panjang }} dibuat dan ditandatangani Perjanjian Kerja Sama oleh pihak-pihak sebagai berikut:</p>'],
            ['type' => self::DEFINITION, 'title' => '1. Pihak Pertama :'],
            ['type' => self::DEFINITION, 'title' => '2. Pihak Kedua :', 'rows' => [
                ['label' => 'Nama', 'value' => '{{ perusahaan.nama }}'],
                ['label' => 'Alamat', 'value' => '{{ perusahaan.alamat }}'],
                ['label' => 'No. Telp', 'value' => '{{ perusahaan.telepon }}'],
            ]],
            ['type' => self::HEADING, 'text' => 'PASAL 1'],
            ['type' => self::HEADING, 'text' => 'RUANG LINGKUP KERJASAMA', 'spaceBefore' => 0],
            ['type' => self::ITEMS_TABLE],
            ['type' => self::HEADING, 'text' => 'PASAL 2'],
            ['type' => self::HEADING, 'text' => 'HAK DAN KEWAJIBAN PARA PIHAK', 'spaceBefore' => 0],
            ['type' => self::AI_CLAUSE],
            ['type' => self::PAGEBREAK],
            ['type' => self::SIGNATURE],
        ]);
    }
}
