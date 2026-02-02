<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PersonnelController extends Controller
{
    public function index()
    {
        return Personnel::with('transportir')->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('personnels/photos', 'public');
        }

        if ($request->hasFile('lampiran')) {
            $data['lampiran'] = $request->file('lampiran')->store('personnels/lampiran', 'public');
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return Personnel::create($data);
    }

    public function show($id)
    {
        return Personnel::with('transportir')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $personnel = Personnel::findOrFail($id);
        $data = $this->validateData($request);

        if ($request->hasFile('photo')) {
            if ($personnel->photo) Storage::disk('public')->delete($personnel->photo);
            $data['photo'] = $request->file('photo')->store('personnels/photos', 'public');
        }

        if ($request->hasFile('lampiran')) {
            if ($personnel->lampiran) Storage::disk('public')->delete($personnel->lampiran);
            $data['lampiran'] = $request->file('lampiran')->store('personnels/lampiran', 'public');
        }

        $data['updated_by'] = Auth::id();

        $personnel->update($data);
        return $personnel;
    }

    public function destroy($id)
    {
        $personnel = Personnel::findOrFail($id);

        if ($personnel->photo) Storage::disk('public')->delete($personnel->photo);
        if ($personnel->lampiran) Storage::disk('public')->delete($personnel->lampiran);

        $personnel->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function validateData(Request $request)
    {
        return $request->validate([
            'id_transportir' => 'required|exists:transportirs,id',
            'nama' => 'required|string|max:255',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'nama_dokumen' => 'nullable|string|max:255',
            'masa_berlaku' => 'nullable|date',
            'lampiran' => 'nullable|file|mimes:pdf|max:2048',
            'is_active' => 'required|boolean',
        ]);
    }
}
