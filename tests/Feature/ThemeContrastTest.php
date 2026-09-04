<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ThemeContrastTest extends TestCase
{
    public function test_html_layouts_do_not_force_dark_mode(): void
    {
        foreach ($this->bladeFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString(
                'class="dark"',
                $contents,
                "Dark mode is forced in {$file->getPathname()}",
            );
        }
    }

    public function test_blade_classes_do_not_use_identical_solid_background_and_text_colors(): void
    {
        $palette = ['white', 'offwhite', 'navy', 'tosca', 'tosca-tint', 'orange', 'ink', 'ink-soft'];

        foreach ($this->bladeFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            preg_match_all('/class="([^"]+)"/', $contents, $matches);

            foreach ($matches[1] as $classes) {
                foreach ($palette as $color) {
                    $hasBackground = preg_match('/(?:^|\s)bg-'.preg_quote($color, '/').'(?:\s|$)/', $classes) === 1;
                    $hasText = preg_match('/(?:^|\s)text-'.preg_quote($color, '/').'(?:\s|$)/', $classes) === 1;

                    $this->assertFalse(
                        $hasBackground && $hasText,
                        "Identical text and background color '{$color}' in {$file->getPathname()}",
                    );
                }
            }
        }
    }

    /** @return list<SplFileInfo> */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
