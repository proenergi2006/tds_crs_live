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
        $this->assertNotImpersonating($request);

        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak cocok.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * Ubah nama dan nomor telepon user yang sedang login.
     */
    public function updateProfile(Request $request)
    {
        $this->assertNotImpersonating($request);

        $data = $request->validate([
            'name'       => 'required|string|min:2|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $user = $request->user();

        $user->name = $data['name'];
        $user->no_telepon = $data['no_telepon'] ?? null;
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user->fresh(),
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

        if ($user->face_image_path) {
            Storage::disk('public')->delete($user->face_image_path);
        }

        $path = $request->file('face_image')->store('faces', 'public');

        $user->update([
            'face_image_path' => $path,
            'face_descriptor' => json_decode($data['face_descriptor'], true),
            'liveness_passed' => $data['liveness_passed'],
        ]);

        return response()->json([
            'message' => 'Face data berhasil disimpan',
            'user'    => $user->only('id','face_image_path','liveness_passed'),
        ]);
    }

    // ImpersonationToken::decode() null = token normal, non-null = lagi impersonate, blok aksinya
    private function assertNotImpersonating(Request $request): void
    {
        $tokenName = $request->user()->currentAccessToken()->name;

        if (\App\Support\ImpersonationToken::decode($tokenName) !== null) {
            abort(403, 'Tidak bisa mengubah profil saat impersonation aktif.');
        }
    }
}
