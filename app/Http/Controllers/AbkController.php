<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ProfilAbk;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AbkController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir_hari' => 'nullable|integer|min:1|max:31',
            'tanggal_lahir_bulan' => 'nullable|integer|min:1|max:12',
            'tanggal_lahir_tahun' => 'nullable|integer|min:1970|max:' . (date('Y') - 17),
            'alamat' => 'nullable|string',
            'nomor_hp' => 'nullable|string|max:20',
            'pengalaman_kerja' => 'nullable|string',
            'posisi_dilamar' => 'nullable|string|max:100',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'foto_profil.image' => 'Foto harus berupa gambar',
            'foto_profil.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'foto_profil.max' => 'Ukuran gambar maksimal 5MB',
        ]);

        $profil = $user->profilAbk;
        if (!$profil) {
            $profil = new ProfilAbk(['user_id' => $user->id]);
        }

        // Build tanggal_lahir from separate fields
        $tanggalLahir = null;
        if ($request->tanggal_lahir_hari && $request->tanggal_lahir_bulan && $request->tanggal_lahir_tahun) {
            $tanggalLahir = $request->tanggal_lahir_tahun . '-' 
                . str_pad($request->tanggal_lahir_bulan, 2, '0', STR_PAD_LEFT) . '-' 
                . str_pad($request->tanggal_lahir_hari, 2, '0', STR_PAD_LEFT);
        }

        $profil->nama_lengkap = $request->nama_lengkap;
        $profil->tempat_lahir = $request->tempat_lahir;
        $profil->tanggal_lahir = $tanggalLahir;
        $profil->alamat = $request->alamat;
        $profil->nomor_hp = $request->nomor_hp;
        $profil->pengalaman_kerja = $request->pengalaman_kerja;
        $profil->posisi_dilamar = $request->posisi_dilamar;

        if ($request->hasFile('foto_profil')) {
            // Delete old photo if exists
            if ($profil->foto_profil) {
                Storage::disk('public')->delete($profil->foto_profil);
            }
            $path = $request->file('foto_profil')->store('foto_profil', 'public');
            $profil->foto_profil = $path;
        }

        $profil->save();

        // Sync name in users table
        $user->name = $request->nama_lengkap;
        $user->save();

        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => 'Memperbarui profil pribadi calon ABK.',
        ]);

        // Check if profile is complete and documents uploaded to advance stage status
        alert()->success('Berhasil', 'Profil berhasil diperbarui!');
        return redirect()->back();
    }

    public function show($id)
    {
        $candidate = User::findOrFail($id);
        $candidate->load(['profilAbk', 'dokumen', 'medicalCheckups', 'diklat', 'dokumenPelaut', 'jobOrders', 'keberangkatan', 'prosesPendaftaran']);

        $latestProcess = $candidate->prosesPendaftaran->sortByDesc('created_at')->first();
        $currentStage = $latestProcess ? $latestProcess->tahap : 1;

        // Audit Trail for this specific user
        $logs = ActivityLog::where('user_id', $candidate->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('operator.abk_detail', compact('candidate', 'currentStage', 'latestProcess', 'logs'));
    }

    public function updateStage(Request $request, $id)
    {
        $request->validate([
            'tahap' => 'required|integer|min:1|max:6',
            'status' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        $candidate = User::findOrFail($id);

        ProsesPendaftaran::create([
            'user_id' => $candidate->id,
            'tahap' => $request->tahap,
            'status' => $request->status,
            'catatan' => $request->catatan,
            'tanggal_update' => now(),
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Perubahan Progres Tahapan',
            'pesan' => 'Progres rekrutmen Anda diperbarui ke Tahap ' . $request->tahap . ' (' . $request->status . '). Catatan: ' . ($request->catatan ?: '-'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memperbarui progres rekrutmen ' . $candidate->name . ' ke Tahap ' . $request->tahap . ' (' . $request->status . ').',
        ]);

        alert()->success('Berhasil', 'Status tahapan rekrutmen berhasil diperbarui!');
        return redirect()->back();
    }
}
