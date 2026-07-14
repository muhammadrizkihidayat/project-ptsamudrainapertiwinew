<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dokumen;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|in:ktp,kartu_keluarga,skck,akta_kelahiran,ijazah',
            'file_dokumen' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'file_dokumen.required' => 'File dokumen wajib diunggah',
            'file_dokumen.mimes' => 'Format berkas harus PDF, JPG, JPEG, atau PNG',
            'file_dokumen.max' => 'Ukuran berkas maksimal 5MB',
        ]);

        $user = Auth::user();
        $jenis = $request->jenis_dokumen;

        // Check if there is already a document of this type
        $dokumen = Dokumen::where('user_id', $user->id)
            ->where('jenis_dokumen', $jenis)
            ->first();

        if (!$dokumen) {
            $dokumen = new Dokumen([
                'user_id' => $user->id,
                'jenis_dokumen' => $jenis,
            ]);
        } else {
            // Delete old file if exists
            if ($dokumen->file_path) {
                Storage::disk('public')->delete($dokumen->file_path);
            }
        }

        $path = $request->file('file_dokumen')->store('dokumen', 'public');
        $dokumen->file_path = $path;
        $dokumen->status = 'Menunggu Verifikasi';
        $dokumen->catatan_operator = null;
        $dokumen->tanggal_upload = now();
        $dokumen->save();

        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => 'Mengunggah dokumen persyaratan: ' . strtoupper(str_replace('_', ' ', $jenis)) . '.',
        ]);

        alert()->success('Berhasil', 'Dokumen berhasil diunggah dan sedang menunggu verifikasi!');
        return redirect()->back();
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Disetujui,Ditolak',
            'catatan_operator' => 'nullable|string',
        ]);

        $dokumen = Dokumen::findOrFail($id);
        $candidate = $dokumen->user;

        $dokumen->status = $request->status;
        $dokumen->catatan_operator = $request->catatan_operator;
        $dokumen->save();

        // Log Activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memverifikasi dokumen (' . strtoupper($dokumen->jenis_dokumen) . ') milik ' . $candidate->name . ' dengan status: ' . $request->status . '.',
        ]);

        // Send Notification to Candidate
        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Verifikasi Dokumen: ' . strtoupper(str_replace('_', ' ', $dokumen->jenis_dokumen)),
            'pesan' => 'Dokumen Anda telah dinyatakan ' . $request->status . '. ' . ($request->catatan_operator ? 'Catatan: ' . $request->catatan_operator : ''),
        ]);

        // Check if all 5 required documents are approved
        $requiredDocs = ['ktp', 'kartu_keluarga', 'skck', 'akta_kelahiran', 'ijazah'];
        $approvedCount = Dokumen::where('user_id', $candidate->id)
            ->whereIn('jenis_dokumen', $requiredDocs)
            ->where('status', 'Disetujui')
            ->count();

        if ($approvedCount == 5) {
            // Auto advance candidate to Stage 2 - Medical Check-Up
            // Check if user is already at or past Stage 2
            $latestProcess = ProsesPendaftaran::where('user_id', $candidate->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $currentStage = $latestProcess ? $latestProcess->tahap : 1;

            if ($currentStage < 2) {
                ProsesPendaftaran::create([
                    'user_id' => $candidate->id,
                    'tahap' => 2,
                    'status' => 'Dalam Proses',
                    'catatan' => 'Semua dokumen wajib telah disetujui. Silakan lakukan Medical Check-Up mandiri.',
                    'tanggal_update' => now(),
                ]);

                // Create initial empty medical checkup row if it doesn't exist
                \App\Models\MedicalCheckup::firstOrCreate(
                    ['user_id' => $candidate->id],
                    ['status_mcu' => 'Menunggu Upload Hasil MCU']
                );

                Notification::create([
                    'user_id' => $candidate->id,
                    'judul' => 'Mulai Tahap 2: Medical Check-Up',
                    'pesan' => 'Semua berkas administrasi telah disetujui! Anda telah diarahkan ke Tahap 2 untuk melakukan Medical Check-Up (MCU).',
                ]);
            }
        } else {
            // If any document is rejected, we can set the latest process stage status to Revision/Revisi
            if ($request->status === 'Ditolak') {
                $latestProcess = ProsesPendaftaran::where('user_id', $candidate->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestProcess && $latestProcess->tahap == 1) {
                    ProsesPendaftaran::create([
                        'user_id' => $candidate->id,
                        'tahap' => 1,
                        'status' => 'Revisi',
                        'catatan' => 'Beberapa dokumen ditolak. Silakan upload ulang sesuai catatan revisi.',
                        'tanggal_update' => now(),
                    ]);
                }
            }
        }

        alert()->success('Berhasil', 'Dokumen berhasil diverifikasi!');
        return redirect()->back();
    }
}
