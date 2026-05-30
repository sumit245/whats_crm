<?php

namespace App\Imports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContactBulkImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function __construct(
        protected int    $tagId,
        protected int    $userId,
        protected int    $phoneCol,
        protected ?int   $nameCol = null,
    ) {}

    public function collection(Collection $rows)
    {
        // Skip header row if first cell is non-numeric
        $startRow = 0;
        if ($rows->isNotEmpty()) {
            $firstCell = (string) ($rows[0][$this->phoneCol] ?? '');
            if (!is_numeric(preg_replace('/[\s+\-()]/', '', $firstCell))) {
                $startRow = 1;
            }
        }

        $batch = [];
        $now   = now()->toDateTimeString();

        for ($i = $startRow; $i < $rows->count(); $i++) {
            $row    = $rows[$i];
            $number = preg_replace('/[\s+\-()]/', '', (string) ($row[$this->phoneCol] ?? ''));

            if (empty($number) || !is_numeric($number)) {
                $this->skipped++;
                continue;
            }

            $name = $this->nameCol !== null ? (string) ($row[$this->nameCol] ?? '') : '';

            $batch[] = [
                'user_id'    => $this->userId,
                'tag_id'     => $this->tagId,
                'number'     => $number,
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->imported++;
        }

        // Bulk insert in chunks of 500, ignoring duplicates
        foreach (array_chunk($batch, 500) as $chunk) {
            try {
                Contact::insertOrIgnore($chunk);
            } catch (\Throwable $e) {
                // Continue on individual duplicate errors
            }
        }
    }
}
