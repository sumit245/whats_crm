<?php

namespace App\Http\Controllers;

use App\Imports\ContactBulkImport;
use App\Models\Tag;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactImportController extends Controller
{
    public function index(Request $request)
    {
        $phonebooks = $request->user()->phonebooks()->withCount('contacts')->latest()->get();
        return view('theme::pages.contacts.import', compact('phonebooks'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            $rows = Excel::toArray(new \stdClass(), $request->file('file'));
            $sheet = $rows[0] ?? [];

            if (empty($sheet)) {
                return response()->json(['error' => true, 'message' => __('File is empty')], 422);
            }

            // Return first 5 rows and headers (first row as headers)
            return response()->json([
                'error'   => false,
                'headers' => array_values($sheet[0] ?? []),
                'rows'    => array_slice(array_map('array_values', $sheet), 1, 5),
                'total'   => count($sheet) - 1,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'      => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'phone_col' => 'required|integer|min:0',
        ]);

        // Resolve or create phonebook
        if ($request->new_phonebook_name) {
            $tag = $request->user()->phonebooks()->create(['name' => $request->new_phonebook_name]);
        } else {
            $request->validate(['phonebook_id' => 'required|exists:tags,id']);
            $tag = $request->user()->phonebooks()->findOrFail($request->phonebook_id);
        }

        $importer = new ContactBulkImport(
            tagId:    $tag->id,
            userId:   $request->user()->id,
            phoneCol: (int) $request->phone_col,
            nameCol:  $request->name_col !== null ? (int) $request->name_col : null,
        );

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => __('Import failed: ') . $e->getMessage()], 422);
        }

        return response()->json([
            'error'     => false,
            'message'   => __('Import complete: :imported contacts imported, :skipped skipped.', [
                'imported' => $importer->imported,
                'skipped'  => $importer->skipped,
            ]),
            'imported'  => $importer->imported,
            'skipped'   => $importer->skipped,
            'phonebook' => $tag->name,
        ]);
    }
}
