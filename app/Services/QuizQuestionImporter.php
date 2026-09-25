<?php

namespace App\Services;

use App\Imports\SpreadsheetRowsImport;
use App\Models\Quiz;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Impor bank soal dari Excel. Kolom: pertanyaan, tipe (PG/esai), pilihan_a
 * sampai pilihan_d, kunci (A-D). Baris yang salah dilewati dan dilaporkan,
 * baris lain tetap masuk — guru tidak perlu mengulang seluruh berkas.
 */
class QuizQuestionImporter
{
    /** @return array{imported: int, failures: list<string>} */
    public function import(Quiz $quiz, UploadedFile $file): array
    {
        $reader = new SpreadsheetRowsImport;
        Excel::import($reader, $file);

        $imported = 0;
        $failures = [];

        DB::transaction(function () use ($reader, $quiz, &$imported, &$failures): void {
            foreach ($reader->rows as $index => $row) {
                $rowNumber = $index + 2;
                $question = $this->text($row['pertanyaan'] ?? $row['soal'] ?? null);

                if ($question === '') {
                    $failures[] = "Baris {$rowNumber}: pertanyaan kosong.";

                    continue;
                }

                $type = $this->type($this->text($row['tipe'] ?? 'pg'));

                if ($type === null) {
                    $failures[] = "Baris {$rowNumber}: tipe harus PG atau esai.";

                    continue;
                }

                if ($type === 'essay') {
                    $quiz->questions()->create(['question' => $question, 'type' => 'essay']);
                    $imported++;

                    continue;
                }

                $choices = array_map(fn (string $letter): string => $this->text($row["pilihan_{$letter}"] ?? null), ['a', 'b', 'c', 'd']);
                $key = array_search(Str::upper($this->text($row['kunci'] ?? null)), ['A', 'B', 'C', 'D'], true);

                if (in_array('', $choices, true)) {
                    $failures[] = "Baris {$rowNumber}: pilihan A sampai D harus terisi.";

                    continue;
                }

                if (count(array_unique($choices)) !== 4) {
                    $failures[] = "Baris {$rowNumber}: ada pilihan jawaban yang sama.";

                    continue;
                }

                if ($key === false) {
                    $failures[] = "Baris {$rowNumber}: kunci jawaban harus A, B, C, atau D.";

                    continue;
                }

                $created = $quiz->questions()->create(['question' => $question, 'type' => 'mc']);
                foreach ($choices as $choiceIndex => $label) {
                    $created->choices()->create(['label' => $label, 'is_correct' => $choiceIndex === $key]);
                }
                $imported++;
            }
        });

        return ['imported' => $imported, 'failures' => $failures];
    }

    private function type(string $value): ?string
    {
        return match (Str::lower($value)) {
            '', 'pg', 'pilihan ganda', 'pilihan_ganda', 'mc' => 'mc',
            'esai', 'essay', 'uraian' => 'essay',
            default => null,
        };
    }

    private function text(mixed $value): string
    {
        return trim(is_scalar($value) ? (string) $value : '');
    }
}
