<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DokumenPelaut;
use App\Models\ProsesPendaftaran;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DokumenPelautController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'jenis_dokumen' => 'required|string|in:paspor,bst,buku_pelaut',
            'nomor_dokumen' => 'required|string|max:100',
            'tanggal_terbit' => 'required|date',
            'tanggal_expired' => 'required|date|after:tanggal_terbit',
            'file_dokumen' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'nomor_dokumen.required' => 'Nomor dokumen wajib diisi',
            'tanggal_terbit.required' => 'Tanggal terbit wajib diisi',
            'tanggal_expired.required' => 'Tanggal expired wajib diisi',
            'tanggal_expired.after' => 'Tanggal expired harus setelah tanggal terbit',
            'file_dokumen.required' => 'File berkas wajib diunggah',
            'file_dokumen.mimes' => 'Format berkas harus PDF, JPG, JPEG, atau PNG',
            'file_dokumen.max' => 'Ukuran berkas maksimal 5MB',
        ]);

        $user = Auth::user();
        $jenis = $request->jenis_dokumen;

        $doc = DokumenPelaut::where('user_id', $user->id)
            ->where('jenis_dokumen', $jenis)
            ->first();

        if (!$doc) {
            $doc = new DokumenPelaut([
                'user_id' => $user->id,
                'jenis_dokumen' => $jenis,
            ]);
        } else {
            // Delete old file
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $path = $request->file('file_dokumen')->store('dokumen_pelaut', 'public');
        $doc->nomor_dokumen = $request->nomor_dokumen;
        $doc->tanggal_terbit = $request->tanggal_terbit;
        $doc->tanggal_expired = $request->tanggal_expired;
        $doc->file_path = $path;
        $doc->status_verifikasi = 'Menunggu Verifikasi';
        $doc->save();

        // Update Proses Pendaftaran status
        $latest = ProsesPendaftaran::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
        if ($latest && $latest->tahap == 4) {
            ProsesPendaftaran::create([
                'user_id' => $user->id,
                'tahap' => 4,
                'status' => 'Dalam Proses',
                'catatan' => 'Berkas ' . strtoupper($jenis) . ' telah diunggah, menunggu verifikasi operator.',
                'tanggal_update' => now(),
            ]);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'aktivitas' => 'Mengunggah bukti dokumen pelaut: ' . strtoupper($jenis) . '.',
        ]);

        alert()->success('Berhasil', 'Dokumen pelaut berhasil diunggah untuk verifikasi!');
        return redirect()->back();
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status_verifikasi' => 'required|string|in:Disetujui,Ditolak',
        ]);

        $doc = DokumenPelaut::findOrFail($id);
        $candidate = $doc->user;

        $doc->status_verifikasi = $request->status_verifikasi;
        $doc->save();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memverifikasi dokumen pelaut (' . strtoupper($doc->jenis_dokumen) . ') milik ' . $candidate->name . ' dengan status: ' . $request->status_verifikasi . '.',
        ]);

        Notification::create([
            'user_id' => $candidate->id,
            'judul' => 'Verifikasi Dokumen Pelaut: ' . strtoupper($doc->jenis_dokumen),
            'pesan' => 'Dokumen pelaut ' . strtoupper($doc->jenis_dokumen) . ' Anda telah dinyatakan ' . $request->status_verifikasi . '.',
        ]);

        // Check if all 3 marine documents are approved
        $required = ['paspor', 'bst', 'buku_pelaut'];
        $approvedCount = DokumenPelaut::where('user_id', $candidate->id)
            ->whereIn('jenis_dokumen', $required)
            ->where('status_verifikasi', 'Disetujui')
            ->count();

        if ($approvedCount == 3) {
            // Auto advance candidate to Stage 5 - Waiting List Job
            $latest = ProsesPendaftaran::where('user_id', $candidate->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $currentStage = $latest ? $latest->tahap : 1;

            if ($currentStage < 5) {
                ProsesPendaftaran::create([
                    'user_id' => $candidate->id,
                    'tahap' => 5,
                    'status' => 'Dalam Proses',
                    'catatan' => 'Seluruh dokumen lengkap. Anda telah dimasukkan ke dalam daftar tunggu (Waiting List) penempatan kapal.',
                    'tanggal_update' => now(),
                ]);

                // Create default job order entry
                \App\Models\JobOrder::firstOrCreate(
                    ['user_id' => $candidate->id],
                    ['status_job' => 'Waiting List']
                );

                Notification::create([
                    'user_id' => $candidate->id,
                    'judul' => 'Mulai Tahap 5: Waiting List',
                    'pesan' => 'Dokumen pelaut Anda lengkap dan disetujui! Anda resmi terdaftar dalam Waiting List PT. Samudra Ina Pertiwi. Operator sedang mencarikan job order kapal yang sesuai.',
                ]);
            }
        } else {
            if ($request->status_verifikasi === 'Ditolak') {
                $latest = ProsesPendaftaran::where('user_id', $candidate->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latest && $latest->tahap == 4) {
                    ProsesPendaftaran::create([
                        'user_id' => $candidate->id,
                        'tahap' => 4,
                        'status' => 'Revisi',
                        'catatan' => 'Beberapa dokumen pelaut ditolak. Silakan upload ulang.',
                        'tanggal_update' => now(),
                    ]);
                }
            }
        }

        alert()->success('Berhasil', 'Verifikasi dokumen pelaut berhasil disimpan!');
        return redirect()->back();
    }
}
