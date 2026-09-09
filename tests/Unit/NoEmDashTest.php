<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class NoEmDashTest extends TestCase
{
    private const EM_DASH = "\u{2014}";

    /**
     * @var array<int, string>
     */
    private const ROOTS = ['app', 'resources', 'routes', 'database/seeders'];

    /**
     * @var array<int, string>
     */
    private const EXTENSIONS = ['php', 'tsx', 'ts', 'jsx', 'js', 'css', 'json'];

    public function test_no_source_file_contains_an_em_dash(): void
    {
        $offenders = [];

        foreach (self::ROOTS as $root) {
            foreach ($this->files(dirname(__DIR__, 2).'/'.$root) as $file) {
                $contents = file_get_contents($file->getPathname());

                if ($contents !== false && str_contains($contents, self::EM_DASH)) {
                    $offenders[] = $root.'/'.$file->getFilename();
                }
            }
        }

        $this->assertSame([], $offenders, 'Em-dash ditemukan di: '.implode(', ', $offenders));
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function files(string $directory): iterable
    {
        if (! is_dir($directory)) {
            return;
        }

        $walker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($walker as $file) {
            if ($file instanceof SplFileInfo
                && $file->isFile()
                && in_array($file->getExtension(), self::EXTENSIONS, true)) {
                yield $file;
            }
        }
    }
}
