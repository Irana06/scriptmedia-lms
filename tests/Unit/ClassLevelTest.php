<?php

namespace Tests\Unit;

use App\Support\ClassLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassLevelTest extends TestCase
{
    /** @return array<string, array{string, string|null}> */
    public static function names(): array
    {
        return [
            'arabic with letter' => ['7A', '8A'],
            'arabic two digits' => ['10 IPA 2', '11 IPA 2'],
            'kelas prefix' => ['Kelas 9C', 'Kelas 10C'],
            'roman with dash' => ['VIII-B', 'IX-B'],
            'roman with trailing number' => ['X IPA 1', 'XI IPA 1'],
            'roman longest first' => ['XI IPS 3', 'XII IPS 3'],
            'top level has no next' => ['XII IPA 1', null],
            'arabic top level' => ['12 A', null],
            'unparseable' => ['Rombel Khusus', null],
        ];
    }

    #[DataProvider('names')]
    public function test_next_level_name(string $name, ?string $expected): void
    {
        $this->assertSame($expected, ClassLevel::next($name));
    }
}
