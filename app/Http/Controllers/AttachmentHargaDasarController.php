<?php

namespace App\Http\Controllers;

use App\Models\AttachmentHargaDasar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentHargaDasarController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);

        $q = AttachmentHargaDasar::query()
            ->orderByDesc('id_attachment_harga_dasar'); // opsional: urut terbaru dulu

        if ($s = $request->query('search')) {
            $q->where('catatan', 'like', "%{$s}%");
        }

        return response()->json($q->paginate($perPage));
    }

    public function show(AttachmentHargaDasar $attachmentHargaDasar)
    {
        return response()->json($attachmentHargaDasar);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periode_awal'  => ['required', 'date'],
            'periode_akhir' => ['required', 'date', 'after_or_equal:periode_awal'],
            'catatan'       => ['nullable', 'string'],
            'lampiran'      => ['required', 'file', 'mimes:pdf', 'max:2048'], // 2MB
        ]);

        // simpan PDF di storage/app/public/attachment_harga_dasar
        $path = $request->file('lampiran')->store('attachment_harga_dasar', 'public');

        $data['lampiran']     = $path;
        $data['created_time'] = now();
        // fallback jika tidak ada auth user
        $data['created_by']   = optional($request->user())->name ?? $request->input('created_by', 'system');

        $model = AttachmentHargaDasar::create($data);

        return response()->json($model, 201);
    }

    public function update(Request $request, AttachmentHargaDasar $attachmentHargaDasar)
    {
        $data = $request->validate([
            'periode_awal'  => ['required', 'date'],
            'periode_akhir' => ['required', 'date', 'after_or_equal:periode_awal'],
            'catatan'       => ['nullable', 'string'],
            'lampiran'      => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
        ]);

        if ($request->hasFile('lampiran')) {
            // hapus file lama jika ada
            if ($attachmentHargaDasar->lampiran && Storage::disk('public')->exists($attachmentHargaDasar->lampiran)) {
                Storage::disk('public')->delete($attachmentHargaDasar->lampiran);
            }

            $data['lampiran'] = $request->file('lampiran')->store('attachment_harga_dasar', 'public');
        }

        $data['lastupdate_time'] = now();
        $data['lastupdate_by']   = optional($request->user())->name ?? $request->input('lastupdate_by', 'system');

        $attachmentHargaDasar->update($data);

        return response()->json($attachmentHargaDasar);
    }

    public function destroy(AttachmentHargaDasar $attachmentHargaDasar)
    {
        // hapus file fisik jika ada
        if ($attachmentHargaDasar->lampiran && Storage::disk('public')->exists($attachmentHargaDasar->lampiran)) {
            Storage::disk('public')->delete($attachmentHargaDasar->lampiran);
        }

        $attachmentHargaDasar->delete();

        return response()->json(null, 204);
    }
}
