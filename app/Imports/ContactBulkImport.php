<?php

namespace App\Imports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContactBulkImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped  = 0;

    private const FIELD_COLS = [
        'name'         => 'nameCol',
        'company'      => 'companyCol',
        'email'        => 'emailCol',
        'address'      => 'addressCol',
        'linkedin_url' => 'linkedinCol',
        'facebook_url' => 'facebookCol',
        'website'      => 'websiteCol',
        'source'       => 'sourceCol',
        'status'       => 'statusCol',
        'remarks'      => 'remarksCol',
    ];

    public function __construct(
        protected int    $tagId,
        protected int    $userId,
        protected int    $phoneCol,
        protected ?int   $nameCol = null,
        protected ?int   $companyCol = null,
        protected ?int   $emailCol = null,
        protected ?int   $addressCol = null,
        protected ?int   $linkedinCol = null,
        protected ?int   $facebookCol = null,
        protected ?int   $websiteCol = null,
        protected ?int   $sourceCol = null,
        protected ?int   $statusCol = null,
        protected ?int   $remarksCol = null,
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

        for ($i = $startRow; $i < $rows->count(); $i++) {
            $row    = $rows[$i];
            $number = preg_replace('/[\s+\-()]/', '', (string) ($row[$this->phoneCol] ?? ''));

            if (empty($number) || !is_numeric($number)) {
                $this->skipped++;
                continue;
            }

            $fields = [];
            foreach (self::FIELD_COLS as $column => $colProperty) {
                $col = $this->{$colProperty};
                if ($col !== null && isset($row[$col]) && $row[$col] !== '') {
                    $fields[$column] = (string) $row[$col];
                }
            }

            if (isset($fields['status'])) {
                $match = collect(\App\Models\Contact::STATUSES)
                    ->first(fn ($s) => strcasecmp($s, trim($fields['status'])) === 0);
                if ($match) $fields['status'] = $match;
            }

            $contact = Contact::firstOrNew([
                'user_id' => $this->userId,
                'number'  => $number,
            ]);

            // Only fill blank fields on an existing contact — don't clobber
            // manually-entered data with a re-import of a sparser sheet.
            foreach ($fields as $column => $value) {
                if (!$contact->exists || empty($contact->{$column})) {
                    $contact->{$column} = $value;
                }
            }
            $contact->save();
            $contact->tags()->syncWithoutDetaching([$this->tagId]);

            $this->imported++;
        }
    }
}
