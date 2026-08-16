<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Imports\ContactImport;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    private function validationRules(Request $request, ?Contact $contact = null): array
    {
        return [
            'number'       => [
                'required', 'min:8', 'max:20',
                Rule::unique('contacts', 'number')
                    ->where('user_id', $request->user()->id)
                    ->ignore($contact?->id),
            ],
            'name'         => ['nullable', 'string', 'max:191'],
            'company'      => ['nullable', 'string', 'max:191'],
            'email'        => ['nullable', 'email', 'max:191'],
            'address'      => ['nullable', 'string'],
            'linkedin_url' => ['nullable', 'url', 'max:191'],
            'facebook_url' => ['nullable', 'url', 'max:191'],
            'website'      => ['nullable', 'url', 'max:191'],
            'source'       => ['nullable', 'string', 'max:191'],
            'status'       => ['nullable', Rule::in(Contact::STATUSES)],
            'remarks'      => ['nullable', 'string'],
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['exists:tags,id'],
        ];
    }

    public function getContactByTagId($id, Request $request)
    {
        $contacts = Contact::whereHas('tags', fn ($q) => $q->where('tags.id', $id))
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('number', 'like', '%' . $request->search . '%');
            })->whereUserId($request->user()->id)->latest()->paginate(15);
        $start_page = $contacts->currentPage();
        $last_page = $contacts->lastPage();
        $html = view('theme::pages.phonebook.datacontact', compact('contacts'))->render();
        return response()->json(['html' => $html, 'last_page' => $last_page, 'start_page' => $start_page]);
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        $contacts = $request->user()->contacts()
            ->when($q, fn ($query) => $query
                ->where('name',   'like', '%' . $q . '%')
                ->orWhere('number', 'like', '%' . $q . '%'))
            ->with('tags:id,name')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'number']);

        return response()->json($contacts->map(fn ($c) => [
            'name'      => $c->name,
            'number'    => $c->number,
            'phonebook' => $c->tags->pluck('name')->implode(', '),
        ]));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate($this->validationRules($request));
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);

            $contact = $request->user()->contacts()->create($data);
            if ($tagIds) {
                $contact->tags()->attach($tagIds);
            }
            return response()->json(['error' => false, 'msg' => __('Success add contact!')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => true, 'msg' => collect($e->errors())->flatten()->first()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'msg' => __('Something errors!')]);
        }
    }

    public function update(Request $request, Contact $contact)
    {
        abort_unless($contact->user_id === $request->user()->id, 403);
        try {
            $data = $request->validate($this->validationRules($request, $contact));
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $contact->update($data);
            if ($tagIds !== null) {
                $contact->tags()->sync($tagIds);
            }
            return response()->json(['error' => false, 'msg' => __('Success update contact!')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => true, 'msg' => collect($e->errors())->flatten()->first()]);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'msg' => __('Something errors!')]);
        }
    }

    public function attachTag(Request $request, Contact $contact, Tag $tag)
    {
        abort_unless($contact->user_id === $request->user()->id && $tag->user_id === $request->user()->id, 403);
        $contact->tags()->syncWithoutDetaching([$tag->id]);
        return response()->json(['error' => false, 'msg' => __('Added to phonebook.')]);
    }

    public function unlinkFromTag(Request $request, Contact $contact, Tag $tag)
    {
        abort_unless($contact->user_id === $request->user()->id && $tag->user_id === $request->user()->id, 403);
        $contact->tags()->detach($tag->id);
        return response()->json(['error' => false, 'msg' => __('Removed from phonebook.')]);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(['error' => false, 'msg' => __('Success delete contact!')]);
    }

    public function destroyAll(Request $request, $id)
    {
        try {
            // "Delete all in phonebook" now unlinks rather than destroying the
            // underlying CRM record, since a contact can belong to other
            // phonebooks too — mirrors TagController::destroy's cascade change.
            $tag = $request->user()->phonebooks()->findOrFail($id);
            $tag->contacts()->detach();
            return response()->json(['error' => false, 'msg' => __('Success delete all contact!')]);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'msg' => __('Something errors!')]);
        }
    }


    public function import(Request $request)
    {
        try {
            Excel::import(new ContactImport($request->phonebook_id), $request->file('fileContacts')->store('temp'));
            return response()->json(['error' => false, 'msg' => __('Success import contact!')]);
        } catch (\Throwable $th) {
            return response()->json(['error' => true, 'msg' => $th->getMessage()]);
        }
    }
    public function export(Request $request, $id)
    {

        try {
            //code...
            $tag = Tag::find($id);
            $name = $tag->name . '.xlsx';
            // Clean the output buffer
            if (ob_get_length()) {
                ob_end_clean();
            }
            return Excel::download(new ContactsExport($tag->id, $request->user()->id), $name);
        } catch (\Throwable $th) {
            return __('something errors');
        }
    }
}
