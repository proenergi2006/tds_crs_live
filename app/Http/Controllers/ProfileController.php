<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Ubah password user yang sedang login.
     */
    public function updatePassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        // Cek current password
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak cocok.'],
            ]);
        }

        // Update password baru
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diubah',
        ]);
    }

    public function updateFace(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'face_image'      => 'required|image|max:2048',
            'face_descriptor' => 'required|json',
            'liveness_passed' => 'required|boolean',
        ]);

        // Hapus foto lama
        if ($user->face_image_path) {
            Storage::disk('public')->delete($user->face_image_path);
        }

        // Simpan foto baru
        $path = $request->file('face_image')->store('faces', 'public');

        $user->update([
            'face_image_path' => $path,
            // decode JSON descriptor ke array
            'face_descriptor' => json_decode($data['face_descriptor'], true),
            'liveness_passed' => $data['liveness_passed'],
        ]);

        return response()->json([
            'message' => 'Face data berhasil disimpan',
            'user'    => $user->only('id','face_image_path','liveness_passed'),
        ]);
    }

}
