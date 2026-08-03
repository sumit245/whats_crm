<?php

namespace App\Http\Controllers;

use App\Models\SuppressionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuppressionController extends Controller
{
    public function index(Request $request)
    {
        $entries = SuppressionEntry::where('user_id', $request->user()->id)
            ->when($request->search, fn ($q) => $q->where('number', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20);

        return view('theme::pages.suppression.index', compact('entries'));
    }

    public function store(Request $request)
    {
        $request->validate(['number' => 'required|string|max:20']);

        try {
            SuppressionEntry::suppress(
                $request->user()->id,
                $request->number,
                'manual',
                $request->note
            );
            return response()->json(['error' => false, 'message' => __('Number suppressed.')]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => __('Something went wrong.')]);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            SuppressionEntry::where('user_id', $request->user()->id)->findOrFail($id)->delete();
            return response()->json(['error' => false, 'message' => __('Number removed from suppression list.')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => __('Something went wrong.')]);
        }
    }

    /**
     * Bulk-import phone numbers from a CSV file.
     * Expected format: one number per row, first column is the phone number.
     * Header row is auto-detected if non-numeric.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $userId   = $request->user()->id;
        $imported = 0;
        $skipped  = 0;
        $chunk    = [];

        try {
            $path   = $request->file('csv_file')->getRealPath();
            $handle = fopen($path, 'r');

            if (!$handle) {
                return response()->json(['error' => true, 'message' => __('Could not read uploaded file.')], 422);
            }

            $firstRow = true;
            while (($row = fgetcsv($handle)) !== false) {
                $raw = trim($row[0] ?? '');

                // Skip header row if first cell is non-numeric
                if ($firstRow && !is_numeric($raw)) {
                    $firstRow = false;
                    continue;
                }
                $firstRow = false;

                // Strip leading + and non-digit chars except digits
                $number = preg_replace('/[^\d]/', '', ltrim($raw, '+'));

                if (strlen($number) < 7 || strlen($number) > 20) {
                    $skipped++;
                    continue;
                }

                $chunk[] = $number;

                if (count($chunk) >= 500) {
                    [$imp, $skip] = $this->insertChunk($userId, $chunk);
                    $imported += $imp;
                    $skipped  += $skip;
                    $chunk     = [];
                }
            }

            fclose($handle);

            // Flush remainder
            if (!empty($chunk)) {
                [$imp, $skip] = $this->insertChunk($userId, $chunk);
                $imported += $imp;
                $skipped  += $skip;
            }

            return response()->json([
                'error'    => false,
                'message'  => __(':n numbers imported, :s skipped (duplicates or invalid).', ['n' => $imported, 's' => $skipped]),
                'imported' => $imported,
                'skipped'  => $skipped,
            ]);

        } catch (\Throwable $e) {
            Log::error('SuppressionController::import — ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => __('Import failed: ') . $e->getMessage()], 500);
        }
    }

    private function insertChunk(int $userId, array $numbers): array
    {
        $imported = 0;
        $skipped  = 0;

        // Get existing numbers to detect duplicates in one query
        $existing = SuppressionEntry::where('user_id', $userId)
            ->whereIn('number', $numbers)
            ->pluck('number')
            ->flip();

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($numbers as $number) {
            if ($existing->has($number)) {
                $skipped++;
                continue;
            }
            $rows[] = [
                'user_id'    => $userId,
                'number'     => $number,
                'reason'     => 'manual',
                'note'       => 'Bulk CSV import',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $imported++;
        }

        if (!empty($rows)) {
            SuppressionEntry::insert($rows);
        }

        return [$imported, $skipped];
    }
}
