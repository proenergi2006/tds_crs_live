<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $q = Vendor::query();

        if ($s = $request->query('search')) {
            $q->where(function ($x) use ($s) {
                $x->where('nama_vendor', 'like', "%{$s}%")
                  ->orWhere('inisial', 'like', "%{$s}%");
            });
        }

        $perPage = $request->query('per_page', 10);
        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        // Normalisasi: ambil digit saja
        $cleanNpwp  = preg_replace('/\D/', '', (string) $request->input('npwp_number'));
        $cleanNib   = preg_replace('/\D/', '', (string) $request->input('nib_number'));
        $cleanSppkp = preg_replace('/\D/', '', (string) $request->input('sppkp_number'));

        $request->merge([
            'npwp_number'  => $cleanNpwp ?: null,
            'nib_number'   => $cleanNib ?: null,
            'sppkp_number' => $cleanSppkp ?: null,
        ]);

        $data = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'inisial'     => 'required|string|max:10',
            'catatan'     => 'nullable|string',
            'is_active'   => 'sometimes|boolean',

            'npwp_number' => ['nullable','regex:/^\d{16}$/'],
            'npwp_file'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'nib_number'  => ['nullable','digits_between:10,20'],
            'nib_file'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'sppkp_number'=> ['nullable','digits_between:8,20'],
            'sppkp_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'bank_account_letter_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'company_profile_file'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ], [
            'npwp_number.regex' => 'NPWP harus 16 digit angka.',
            'nib_number.digits_between' => 'Nomor NIB/TDP/SIUP harus 10–20 digit.',
            'sppkp_number.digits_between' => 'Nomor SPPKP harus 8–20 digit.',
        ]);

        // simpan file
        if ($request->hasFile('npwp_file'))  $data['npwp_file']  = $request->file('npwp_file')->store('vendors/npwp', 'public');
        if ($request->hasFile('nib_file'))   $data['nib_file']   = $request->file('nib_file')->store('vendors/nib', 'public');
        if ($request->hasFile('sppkp_file')) $data['sppkp_file'] = $request->file('sppkp_file')->store('vendors/sppkp', 'public');
        if ($request->hasFile('bank_account_letter_file'))
            $data['bank_account_letter_file'] = $request->file('bank_account_letter_file')->store('vendors/bank_letter', 'public');
        if ($request->hasFile('company_profile_file'))
            $data['company_profile_file'] = $request->file('company_profile_file')->store('vendors/company_profile', 'public');

        $data['created_time'] = now();
        $data['created_by']   = $request->user()->name ?? 'system';

        $vendor = Vendor::create($data);
        return response()->json($vendor, 201);
    }

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return response()->json($vendor);
    }

    public function update(Request $request, $id)
    {
        $cleanNpwp  = preg_replace('/\D/', '', (string) $request->input('npwp_number'));
        $cleanNib   = preg_replace('/\D/', '', (string) $request->input('nib_number'));
        $cleanSppkp = preg_replace('/\D/', '', (string) $request->input('sppkp_number'));

        $request->merge([
            'npwp_number'  => $cleanNpwp ?: null,
            'nib_number'   => $cleanNib ?: null,
            'sppkp_number' => $cleanSppkp ?: null,
        ]);

        $data = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'inisial'     => 'required|string|max:10',
            'catatan'     => 'nullable|string',
            'is_active'   => 'sometimes|boolean',

            'npwp_number' => ['nullable','regex:/^\d{16}$/'],
            'npwp_file'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'nib_number'  => ['nullable','digits_between:10,20'],
            'nib_file'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'sppkp_number'=> ['nullable','digits_between:8,20'],
            'sppkp_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',

            'bank_account_letter_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'company_profile_file'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ], [
            'npwp_number.regex' => 'NPWP harus 16 digit angka.',
            'nib_number.digits_between' => 'Nomor NIB/TDP/SIUP harus 10–20 digit.',
            'sppkp_number.digits_between' => 'Nomor SPPKP harus 8–20 digit.',
        ]);

        if ($request->hasFile('npwp_file'))  $data['npwp_file']  = $request->file('npwp_file')->store('vendors/npwp', 'public');
        if ($request->hasFile('nib_file'))   $data['nib_file']   = $request->file('nib_file')->store('vendors/nib', 'public');
        if ($request->hasFile('sppkp_file')) $data['sppkp_file'] = $request->file('sppkp_file')->store('vendors/sppkp', 'public');
        if ($request->hasFile('bank_account_letter_file'))
            $data['bank_account_letter_file'] = $request->file('bank_account_letter_file')->store('vendors/bank_letter', 'public');
        if ($request->hasFile('company_profile_file'))
            $data['company_profile_file'] = $request->file('company_profile_file')->store('vendors/company_profile', 'public');

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = $request->user()->name ?? 'system';

        $vendor = Vendor::findOrFail($id);
        $vendor->update($data);

        return response()->json($vendor);
    }

    public function destroy($id)
    {
        Vendor::destroy($id);
        return response()->json(null, 204);
    }
}
