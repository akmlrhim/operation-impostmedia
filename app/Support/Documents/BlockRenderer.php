<?php

namespace App\Support\Documents;

use App\Models\Contract;
use Illuminate\Support\Collection;

class BlockRenderer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><sup><sub>';

    /**
     * Meterai dan tanda tangan berdiri berdampingan, bukan bertumpuk: coretan
     * yang menimpa e-meterai menutupi kode QR-nya dan bikin verifikasinya gagal.
     *
     * @var array<string, string>
     */
    private const MARK_ROW = ['height' => '96px', 'text-align' => 'center', 'white-space' => 'nowrap'];

    /** @var array<string, string> */
    private array $variables = [];

    /** @var array<string, mixed> */
    private array $settings = [];

    private ?Contract $contract = null;

    /** @var array<string, string|null> */
    private array $images = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $clauses = [];

    private bool $preview = false;

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $variables
     * @param  array<string, string|null>  $images  logo, signature, stamp sebagai data URI
     * @param  array<string, array<int, string>>  $clauses  isi pasal AI per id blok
     * @param  bool  $preview  gambar sebagai satu halaman memanjang untuk editor
     */
    public function render(
        array $blocks,
        array $settings,
        array $variables,
        array $images = [],
        ?Contract $contract = null,
        bool $preview = false,
        array $clauses = [],
    ): string {
        $this->prepare($settings, $variables, $images, $contract, $preview, $clauses);

        return $this->document($this->blocksHtml($blocks));
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $variables
     * @param  array<string, string|null>  $images
     * @param  array<string, array<int, string>>  $clauses
     */
    public function body(
        array $blocks,
        array $settings,
        array $variables,
        array $images = [],
        ?Contract $contract = null,
        array $clauses = [],
    ): string {
        $this->prepare($settings, $variables, $images, $contract, false, $clauses);

        return $this->blocksHtml($blocks);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $variables
     * @param  array<string, string|null>  $images
     */
    public function page(
        string $body,
        array $settings,
        array $variables,
        array $images = [],
        bool $preview = false,
    ): string {
        $this->prepare($settings, $variables, $images, null, $preview, []);

        return $this->document($body);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $variables
     * @param  array<string, string|null>  $images
     * @param  array<string, array<int, string>>  $clauses
     */
    private function prepare(
        array $settings,
        array $variables,
        array $images,
        ?Contract $contract,
        bool $preview,
        array $clauses,
    ): void {
        $this->variables = $variables;
        $this->settings = BlockSchema::mergeSettings($settings);
        $this->images = $images;
        $this->contract = $contract;
        $this->preview = $preview;
        $this->clauses = $clauses;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function blocksHtml(array $blocks): string
    {
        return Collection::make(BlockSchema::normalize($blocks))
            ->map(fn (array $block) => $this->block($block))
            ->implode('');
    }

    private function document(string $body): string
    {
        $document = $this->watermark()
            .$this->header()
            .$this->footer()
            .'<div class="doc-body">'.$body.'</div>';

        return $this->stylesheet().($this->preview
            ? '<div class="doc-page">'.$document.'</div>'
            : $document);
    }

    /** @param array<string, mixed> $block */
    private function block(array $block): string
    {
        $inner = match ($block['type']) {
            BlockSchema::HEADING => $this->heading($block),
            BlockSchema::PARAGRAPH => $this->paragraph($block),
            BlockSchema::LIST => $this->list($block),
            BlockSchema::DEFINITION => $this->definition($block),
            BlockSchema::AI_CLAUSE => $this->aiClause($block),
            BlockSchema::ITEMS_TABLE => $this->itemsTable($block),
            BlockSchema::TABLE => $this->table($block),
            BlockSchema::SIGNATURE => $this->signature($block),
            BlockSchema::SPACER => $this->spacer($block),
            BlockSchema::PAGEBREAK => '<div class="doc-break"></div>',
            default => '',
        };

        if ($inner === '' || $block['type'] === BlockSchema::PAGEBREAK) {
            return $inner;
        }

        $style = array_merge($this->spacing($block), $this->indent($block));

        return '<div'.$this->style($style).'>'.$inner.'</div>';
    }

    /** @param array<string, mixed> $block */
    private function heading(array $block): string
    {
        $text = $this->substitute((string) $block['text'], escape: false);

        if ($block['uppercase']) {
            $text = mb_strtoupper($text);
        }

        $text = nl2br(e($text));

        $level = max(1, min(3, (int) $block['level']));
        $size = [1 => 13, 2 => 11.5, 3 => 11][$level];

        return '<div'.$this->style([
            'font-size' => $size.'pt',
            'font-weight' => 'bold',
            'text-align' => $this->align($block['align']),
        ]).'>'.$text.'</div>';
    }

    /** @param array<string, mixed> $block */
    private function paragraph(array $block): string
    {
        $html = $this->rich((string) $block['html']);

        if (trim(strip_tags($html)) === '') {
            return '';
        }

        return '<div'.$this->style(['text-align' => $this->align($block['align'])]).'>'.$html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function list(array $block): string
    {
        $items = array_values(array_filter(
            (array) $block['items'],
            fn ($item) => is_string($item) && trim(strip_tags($item)) !== '',
        ));

        if ($items === []) {
            return '';
        }

        $align = $this->align($block['align']);
        $start = max(1, (int) ($block['start'] ?? 1));
        $rows = '';

        foreach ($items as $index => $item) {
            $rows .= '<tr>'
                .'<td'.$this->style(['width' => '24px', 'vertical-align' => 'top', 'padding' => '0 6px 4px 0']).'>'
                .$this->marker((string) $block['style'], $start + $index)
                .'</td>'
                .'<td'.$this->style(['vertical-align' => 'top', 'padding' => '0 0 4px 0', 'text-align' => $align]).'>'
                .$this->rich($item)
                .'</td></tr>';
        }

        return '<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'>'.$rows.'</table>';
    }

    private function marker(string $style, int $number): string
    {
        return match ($style) {
            'decimal' => $number.'.',
            'lower-alpha' => chr(96 + (($number - 1) % 26) + 1).'.',
            'upper-alpha' => chr(64 + (($number - 1) % 26) + 1).'.',
            'lower-roman' => $this->roman($number).'.',
            default => '&bull;',
        };
    }

    private function roman(int $number): string
    {
        $map = ['x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1];
        $result = '';

        foreach ($map as $numeral => $value) {
            while ($number >= $value) {
                $result .= $numeral;
                $number -= $value;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function aiClause(array $block): string
    {
        $points = $this->clauses[(string) $block['id']] ?? [];

        if ($points === []) {
            $fallback = $this->contract === null
                ? '[Isi pasal "'.$block['topic'].'" ditulis AI mengikuti layanan di MoU]'
                : (string) $block['fallback'];

            return trim($fallback) === '' ? '' : $this->paragraph([
                'html' => '<p>'.e($fallback).'</p>',
                'align' => $block['align'],
            ]);
        }

        $escaped = array_map(fn (string $point): string => e($point), $points);

        if ($block['format'] === 'paragraph') {
            return $this->paragraph([
                'html' => '<p>'.implode('</p><p>', $escaped).'</p>',
                'align' => $block['align'],
            ]);
        }

        return $this->list([
            'items' => $escaped,
            'style' => $block['style'],
            'align' => $block['align'],
            'start' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function definition(array $block): string
    {
        $rows = '';
        $labelWidth = max(10, min(60, (int) $block['labelWidth']));

        foreach ((array) $block['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows .= '<tr>'
                .'<td'.$this->style(['width' => $labelWidth.'%', 'vertical-align' => 'top', 'padding' => '0 0 3px 0']).'>'
                .$this->plain((string) ($row['label'] ?? '')).'</td>'
                .'<td'.$this->style(['width' => '12px', 'vertical-align' => 'top', 'padding' => '0 0 3px 0']).'>:</td>'
                .'<td'.$this->style(['vertical-align' => 'top', 'padding' => '0 0 3px 0']).'>'
                .$this->plain((string) ($row['value'] ?? '')).'</td></tr>';
        }

        if ($rows === '') {
            return '';
        }

        $title = trim((string) $block['title']);
        $heading = $title === ''
            ? ''
            : '<div'.$this->style(['margin-bottom' => '4px']).'>'.$this->plain($title).'</div>';

        return $heading.'<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'>'.$rows.'</table>';
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function itemsTable(array $block): string
    {
        if ($this->contract === null) {
            return $this->placeholder('Tabel rincian terisi dari item MoU.');
        }

        $columns = array_values(array_intersect(
            (array) $block['columns'],
            ['no', 'name', 'quantity', 'unit_price', 'amount'],
        ));

        if ($columns === []) {
            return '';
        }

        $labels = [
            'no' => 'No',
            'name' => 'Uraian Pekerjaan',
            'quantity' => 'Volume',
            'unit_price' => 'Harga Satuan',
            'amount' => 'Jumlah',
        ];

        $head = '';
        foreach ($columns as $column) {
            $head .= '<th'.$this->cell(['background-color' => '#f2f2f2', 'font-weight' => 'bold']).'>'.$labels[$column].'</th>';
        }

        $body = '';
        foreach ($this->contract->items as $index => $item) {
            $body .= '<tr>';

            foreach ($columns as $column) {
                $numeric = in_array($column, ['unit_price', 'amount'], true);

                $value = match ($column) {
                    'no' => (string) ($index + 1),
                    'name' => e($item->name).($item->description
                        ? '<br><span style="font-size: 9pt;">'.nl2br(e($item->description)).'</span>'
                        : ''),
                    'quantity' => e(rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',').' '.$item->unit),
                    'unit_price' => e(DocumentVariables::rupiah($item->unit_price)),
                    'amount' => e(DocumentVariables::rupiah($item->amount)),
                    default => '',
                };

                $body .= '<td'.$this->cell([
                    'text-align' => $numeric ? 'right' : ($column === 'no' ? 'center' : 'left'),
                    'width' => $column === 'no' ? '32px' : 'auto',
                ]).'>'.$value.'</td>';
            }

            $body .= '</tr>';
        }

        if ($body === '') {
            $body = '<tr><td'.$this->cell(['text-align' => 'center']).' colspan="'.count($columns).'">Belum ada item.</td></tr>';
        }

        $span = max(1, count($columns) - 1);
        $foot = '';

        if ($block['showSubtotal']) {
            $foot .= $this->totalRow($span, 'Subtotal', DocumentVariables::rupiah($this->contract->subtotal));
        }

        if ($block['showDiscount'] && (float) $this->contract->discount_amount > 0) {
            $foot .= $this->totalRow($span, 'Diskon', '-'.DocumentVariables::rupiah($this->contract->discount_amount));
        }

        if ($block['showTax'] && (float) $this->contract->tax_amount > 0) {
            $foot .= $this->totalRow($span, 'PPN '.(int) $this->contract->tax_percent.'%', DocumentVariables::rupiah($this->contract->tax_amount));
        }

        if ($block['showTotal']) {
            $foot .= $this->totalRow($span, 'Nilai Pekerjaan', DocumentVariables::rupiah($this->contract->value), bold: true);
        }

        return '<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'>'
            .'<thead><tr>'.$head.'</tr></thead>'
            .'<tbody>'.$body.'</tbody>'
            .($foot === '' ? '' : '<tfoot>'.$foot.'</tfoot>')
            .'</table>';
    }

    private function totalRow(int $span, string $label, string $value, bool $bold = false): string
    {
        $weight = $bold ? 'bold' : 'normal';

        return '<tr>'
            .'<td colspan="'.$span.'"'.$this->cell(['text-align' => 'right', 'font-weight' => $weight]).'>'.e($label).'</td>'
            .'<td'.$this->cell(['text-align' => 'right', 'font-weight' => $weight]).'>'.e($value).'</td>'
            .'</tr>';
    }

    /** @param array<string, mixed> $block */
    private function table(array $block): string
    {
        $head = array_values(array_filter((array) $block['head'], 'is_string'));
        $widths = array_values((array) $block['widths']);
        $bordered = (bool) $block['bordered'];

        $headHtml = '';
        foreach ($head as $index => $label) {
            $headHtml .= '<th'.$this->cell([
                'background-color' => '#f2f2f2',
                'font-weight' => 'bold',
                'width' => isset($widths[$index]) ? ((float) $widths[$index]).'%' : 'auto',
            ], $bordered).'>'.$this->plain($label).'</th>';
        }

        $bodyHtml = '';
        foreach ((array) $block['rows'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $bodyHtml .= '<tr>';

            for ($index = 0; $index < max(1, count($head)); $index++) {
                $bodyHtml .= '<td'.$this->cell([], $bordered).'>'.$this->plain((string) ($row[$index] ?? '')).'</td>';
            }

            $bodyHtml .= '</tr>';
        }

        return '<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'>'
            .($headHtml === '' ? '' : '<thead><tr>'.$headHtml.'</tr></thead>')
            .'<tbody>'.$bodyHtml.'</tbody></table>';
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function signature(array $block): string
    {
        $columns = array_values(array_filter((array) $block['columns'], 'is_array'));

        if ($columns === []) {
            return '';
        }

        $width = round(100 / count($columns), 4);
        $gap = (int) $block['gap'];
        $last = count($columns) - 1;

        $identity = '';
        $captions = '';
        $signs = '';

        foreach ($columns as $index => $column) {
            $base = [
                'width' => $width.'%',
                'padding-right' => ($index === $last ? 0 : $gap).'px',
            ];

            $rows = '';

            foreach ((array) ($column['rows'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $rows .= '<div'.$this->style(['margin-top' => '14px']).'>'
                    .'<strong>'.$this->plain((string) ($row['label'] ?? '')).'</strong><br>'
                    .'<span'.$this->style(['text-decoration' => 'underline']).'>'.$this->plain((string) ($row['value'] ?? '')).'</span>'
                    .'</div>';
            }

            $caption = trim((string) ($column['caption'] ?? ''));
            $name = trim((string) ($column['name'] ?? ''));

            $stamp = ($column['stamp'] ?? false) ? $this->image('stamp') : null;
            $signature = $this->mark($column['signature'] ?? false, 'signature');

            $marks = match (true) {
                $stamp !== null && $signature !== null => '<div'.$this->style(self::MARK_ROW).'>'
                    .'<img src="'.$stamp.'"'.$this->style(['width' => '90px', 'vertical-align' => 'middle']).'>'
                    .'<img src="'.$signature.'"'.$this->style(['width' => '120px', 'vertical-align' => 'middle', 'margin-left' => '24px']).'>'
                    .'</div>',
                $stamp !== null => '<div'.$this->style(self::MARK_ROW).'><img src="'.$stamp.'"'.$this->style(['width' => '90px', 'vertical-align' => 'middle']).'></div>',
                $signature !== null => '<div'.$this->style(self::MARK_ROW).'><img src="'.$signature.'"'.$this->style(['width' => '120px', 'vertical-align' => 'middle']).'></div>',
                default => '<div'.$this->style(['height' => '96px']).'></div>',
            };

            $identity .= '<td'.$this->style([...$base, 'vertical-align' => 'top']).'>'
                .'<div'.$this->style(['font-weight' => 'bold']).'>'.$this->plain((string) ($column['title'] ?? '')).'</div>'
                .$rows
                .'</td>';

            $captions .= '<td'.$this->style([
                ...$base,
                'vertical-align' => 'bottom',
                'padding-top' => '24px',
                'font-weight' => 'bold',
                'text-align' => 'center',
            ]).'>'.($caption === '' ? '' : $this->plain($caption)).'</td>';

            $signs .= '<td'.$this->style([...$base, 'vertical-align' => 'top']).'>'
                .$marks
                .($name === '' ? '' : '<div'.$this->style(['font-weight' => 'bold', 'text-align' => 'center']).'>'.mb_strtoupper($this->plain($name)).'</div>')
                .'</td>';
        }

        return '<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'>'
            .'<tr>'.$identity.'</tr>'
            .'<tr>'.$captions.'</tr>'
            .'<tr>'.$signs.'</tr>'
            .'</table>';
    }

    /** @param array<string, mixed> $block */
    private function spacer(array $block): string
    {
        return '<div'.$this->style(['height' => max(0, min(200, (int) $block['height'])).'px']).'></div>';
    }

    private function placeholder(string $text): string
    {
        return '<div'.$this->style([
            'border' => '1px dashed #bbb',
            'padding' => '8px',
            'color' => '#777',
            'font-size' => '9pt',
            'text-align' => 'center',
        ]).'>'.e($text).'</div>';
    }

    private function header(): string
    {
        $header = $this->settings['header'];

        if (! $header['enabled']) {
            return '';
        }

        $logo = $header['logo'] ? $this->image('logo') : null;
        $lines = '';

        foreach ($header['lines'] as $line) {
            $lines .= '<div'.$this->style(['font-size' => '10pt']).'>'.$this->plain((string) $line).'</div>';
        }

        $text = '<div'.$this->style(['font-size' => '11pt', 'font-weight' => 'bold']).'>'
            .mb_strtoupper($this->plain((string) $header['title']))
            .'</div>'.$lines;

        $inner = $logo === null
            ? '<div'.$this->style(['text-align' => 'center']).'>'.$text.'</div>'
            : '<table'.$this->style(['width' => '100%', 'border-collapse' => 'collapse']).'><tr>'
                .'<td'.$this->style(['width' => ((int) $header['logoWidth']).'mm', 'vertical-align' => 'middle']).'>'
                .'<img src="'.$logo.'"'.$this->style(['width' => ((int) $header['logoWidth']).'mm']).'>'
                .'</td>'
                .'<td'.$this->style(['text-align' => 'center', 'vertical-align' => 'middle']).'>'.$text.'</td>'
                .'<td'.$this->style(['width' => ((int) $header['logoWidth']).'mm']).'></td>'
                .'</tr></table>';

        $rule = $header['rule']
            ? '<div'.$this->style(['border-bottom' => '3px double #000', 'margin-top' => '4px']).'></div>'
            : '';

        $running = $this->settings['runningTitle'];
        $title = $running['enabled'] && trim((string) $running['text']) !== ''
            ? '<div'.$this->style(['text-align' => 'center', 'font-weight' => 'bold', 'font-size' => '12pt', 'margin-top' => '8px']).'>'
                .$this->plain((string) $running['text']).'</div>'
            : '';

        return '<div class="doc-header">'.$inner.$rule.$title.'</div>';
    }

    private function footer(): string
    {
        $footer = $this->settings['footer'];

        if (! $footer['enabled']) {
            return '';
        }

        $text = trim((string) $footer['text']);

        $page = $footer['pageNumber'] ? '<span class="doc-pageno"></span>' : '';

        return '<div class="doc-footer">'
            .($text === '' ? '' : '<span>'.$this->plain($text).'</span>')
            .$page
            .'</div>';
    }

    private function watermark(): string
    {
        $watermark = $this->settings['watermark'];
        $logo = $this->image('logo');

        if (! $watermark['enabled'] || $logo === null) {
            return '';
        }

        return '<div class="doc-watermark"><img src="'.$logo.'"'.$this->style([
            'width' => ((int) $watermark['width']).'mm',
            'opacity' => (string) round((float) $watermark['opacity'], 3),
        ]).'></div>';
    }

    private function stylesheet(): string
    {
        $page = $this->settings['page'];
        $font = $this->settings['font'];

        $family = match ($font['family']) {
            'sans' => 'Helvetica, Arial, sans-serif',
            default => "'Times New Roman', Times, serif",
        };

        $top = max(10, (int) $page['marginTop']);
        $bottom = max(10, (int) $page['marginBottom']);

        $headerTop = -($top - 8);
        $footerBottom = -($bottom - 6);

        $common = '.doc-body, .doc-header, .doc-footer { font-family: '.$family.'; font-size: '.((float) $font['size']).'pt; line-height: '.((float) $font['lineHeight']).'; color: #111; }'
            .'.doc-body p { margin: 0 0 6px 0; }'
            .'.doc-body p:last-child { margin-bottom: 0; }';

        if ($this->preview) {
            return '<style>'
                .$common
                .'body { margin: 0; background: #fff; }'
                .'.doc-page { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto; box-sizing: border-box;'
                .' padding: '.$top.'mm '.((int) $page['marginRight']).'mm '.$bottom.'mm '.((int) $page['marginLeft']).'mm; }'
                .'.doc-header { position: absolute; left: '.((int) $page['marginLeft']).'mm; right: '.((int) $page['marginRight']).'mm; top: 8mm; }'
                .'.doc-footer { display: none; }'
                .'.doc-watermark { position: absolute; left: 0; right: 0; top: 120mm; text-align: center; z-index: 0; }'
                .'.doc-body { position: relative; z-index: 1; }'
                .'.doc-break { border-top: 1px dashed #c00; margin: 18px 0; }'
                .'</style>';
        }

        return '<style>'
            .'@page { margin: '.$top.'mm '.((int) $page['marginRight']).'mm '.$bottom.'mm '.((int) $page['marginLeft']).'mm; }'
            .$common
            .'.doc-header { position: fixed; left: 0; right: 0; top: '.$headerTop.'mm; }'
            .'.doc-footer { position: fixed; left: 0; right: 0; bottom: '.$footerBottom.'mm; font-size: 9pt; color: #555; text-align: center; }'
            .'.doc-watermark { position: fixed; left: 0; right: 0; top: 38%; text-align: center; z-index: -1; }'
            .'.doc-break { page-break-after: always; }'
            .'.doc-body table { page-break-inside: auto; }'
            .'.doc-body tr { page-break-inside: avoid; }'
            .'</style>';
    }

    private function plain(string $raw): string
    {
        return nl2br(e($this->substitute($raw, escape: false)));
    }

    private function rich(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);
        $clean = preg_replace('/<\s*([a-z0-9]+)(\s[^>]*)?>/i', '<$1>', $clean) ?? '';

        return $this->substitute($clean, escape: true);
    }

    private function substitute(string $text, bool $escape): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            function (array $match) use ($escape): string {
                $value = $this->variables[strtolower($match[1])] ?? null;

                if ($value === null) {
                    return $match[0];
                }

                return $escape ? e($value) : $value;
            },
            $text,
        ) ?? $text;
    }

    /**
     * Kolom tanda tangan boleh menunjuk gambar lain, misalnya spesimen klien
     * yang diunggah per MoU, dengan mengisi nama kuncinya alih-alih true.
     */
    private function mark(mixed $value, string $fallback): ?string
    {
        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return $this->image(is_string($value) ? $value : $fallback);
    }

    private function image(string $key): ?string
    {
        $value = $this->images[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function align(mixed $align): string
    {
        return in_array($align, ['left', 'center', 'right', 'justify'], true) ? $align : 'left';
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, string>
     */
    private function spacing(array $block): array
    {
        return [
            'margin-top' => max(0, min(120, (int) ($block['spaceBefore'] ?? 0))).'px',
            'margin-bottom' => max(0, min(120, (int) ($block['spaceAfter'] ?? 0))).'px',
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, string>
     */
    private function indent(array $block): array
    {
        $indent = max(0, min(4, (int) ($block['indent'] ?? 0)));

        return $indent === 0 ? [] : ['margin-left' => ($indent * 24).'px'];
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function cell(array $extra, bool $bordered = true): string
    {
        return $this->style(array_merge([
            'border' => $bordered ? '1px solid #999' : 'none',
            'padding' => '5px 7px',
            'vertical-align' => 'top',
        ], $extra));
    }

    /** @param array<string, string> $declarations */
    private function style(array $declarations): string
    {
        $declarations = array_filter($declarations, fn (string $value) => $value !== '' && $value !== 'auto');

        if ($declarations === []) {
            return '';
        }

        $css = '';
        foreach ($declarations as $property => $value) {
            $css .= $property.': '.$value.'; ';
        }

        return ' style="'.e(trim($css)).'"';
    }
}
