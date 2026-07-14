<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MedicalCheckup;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class McuController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file_hasil_mcu' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'file_hasil_mcu.required' => 'Berkas hasil MCU wajib diunggah',
            'file_hasil_mcu.mimes' => 'Format berkas harus PDF, JPG, JPEG, atau PNG',
            'file_hasil_mcu.max' => 'Ukuran berkas maksimal 5MB',
        ]);

        $user = Auth::user();

        $mcu = MedicalCheckup::where('user_id', $user->id)->first();
        if (!$mcu) {
            $mcu = new MedicalCheckup(['user_id' => $user->id]);
        } else {
            // Delete old file
            if ($mcu->file_hasil_mcu) {
                Storage::disk('public')->delete($mcu->file_hasil_mcu);
            }
        }

        $path = $request->file('file_hasil_mcu')->store('mcu', 'public');
        $mcu->file_hasil_mcu = $path;
        $mcu->status_mcu = 'Menunggu Verifikasi MCU';
        $mcu->catatan_operator = null;
        $mcu->tanggal_upload = now();
        $mcu->save();

        // Update Proses Pendaftaran status
        $latest = ProsesPendaftaran::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
        if ($latest && $latest->tahap == 2) {
            ProsesPendaftaran::create([
                'user_id' => $user->id,
                'tahap' => 2,
                'status' => 'Dalam Proses',
                'catatan' => 'Hasil MCU telah diunggah, menunggu pemeriksaan operator.',
                'tanggal_update' => now(),
            ]);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => 'Mengunggah file hasil Medical Check-Up (MCU).',
        ]);

        alert()->success('Berhasil', 'Hasil MCU berhasil diunggah!');
        return redirect()->back();
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status_mcu' => 'required|string|in:Lulus MCU,Pending MCU,Tidak Lulus MCU',
            'catatan_operator' => 'nullable|string',
        ]);

        $mcu = MedicalCheckup::findOrFail($id);
        $candidate = $mcu->user;

        $mcu->status_mcu = $request->status_mcu;
        $mcu->catatan_operator = $request->catatan_operator;
        $mcu->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memverifikasi status MCU ' . $candidate->name . ' dengan status: ' . $request->status_mcu . '.',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Hasil Medical Check-Up: ' . $request->status_mcu,
            'pesan' => 'Hasil pemeriksaan medis Anda dinyatakan ' . $request->status_mcu . '. Catatan: ' . ($request->catatan_operator ?: '-'),
        ]);

        if ($request->status_mcu === 'Lulus MCU') {
            // Auto advance candidate to Stage 3 - Diklat
            ProsesPendaftaran::create([
                'user_id' => $candidate->id,
                'tahap' => 3,
                'status' => 'Dalam Proses',
                'catatan' => 'Dinyatakan Lulus MCU. Menunggu penjadwalan Diklat offline di Pemalang.',
                'tanggal_update' => now(),
            ]);

            // Create initial empty diklat entry
            \App\Models\Diklat::firstOrCreate(
                ['user_id' => $candidate->id],
                ['status' => 'Menunggu Jadwal Diklat', 'lokasi' => 'Pemalang']
            );

            Notification::create([
                'user_id' => $candidate->id,
                'judul' => 'Jadwal Diklat Akan Diterbitkan',
                'pesan' => 'Selamat! Anda dinyatakan Lulus MCU dan berhak mengikuti Diklat offline 7 hari di Pemalang. Jadwal batch akan terbit dalam waktu dekat.',
            ]);
        } else {
            // MCU is Pending or Failed
            $statusTahap = $request->status_mcu === 'Pending MCU' ? 'Pending' : 'Ditolak';
            ProsesPendaftaran::create([
                'user_id' => $candidate->id,
                'tahap' => 2,
                'status' => $statusTahap,
                'catatan' => 'Status pemeriksaan medis: ' . $request->status_mcu . '. Catatan: ' . $request->catatan_operator,
                'tanggal_update' => now(),
            ]);
        }

        alert()->success('Berhasil', 'Status Medical Check-Up berhasil diperbarui!');
        return redirect()->back();
    }
}
