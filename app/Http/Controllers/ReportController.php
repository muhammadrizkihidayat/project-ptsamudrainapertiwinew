<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\ProsesPendaftaran;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function exportCsv(Request $request)
    {
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=laporan_abk_" . date('Ymd_His') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $candidates = User::role('abk')->with(['profilAbk', 'prosesPendaftaran', 'jobOrders', 'keberangkatan'])->get();

        $callback = function() use($candidates) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Column Headers
            fputcsv($file, [
                'No', 
                'Nama Lengkap', 
                'Email', 
                'No. HP', 
                'Tempat, Tanggal Lahir', 
                'Alamat', 
                'Posisi Dilamar', 
                'Pengalaman Kerja', 
                'Tahap Rekrutmen', 
                'Status Tahap', 
                'Kapal Penempatan', 
                'Negara Tujuan', 
                'Maskapai / Penerbangan', 
                'Jadwal Berangkat'
            ]);

            foreach ($candidates as $key => $candidate) {
                $profile = $candidate->profilAbk;
                $latestProcess = $candidate->prosesPendaftaran->sortByDesc('created_at')->first();
                $job = $candidate->jobOrders->first();
                $flight = $candidate->keberangkatan->first();

                $tahapMapping = [
                    1 => 'Tahap 1 - Registrasi & Dokumen',
                    2 => 'Tahap 2 - Medical Check-Up',
                    3 => 'Tahap 3 - Diklat',
                    4 => 'Tahap 4 - Dokumen Pelaut',
                    5 => 'Tahap 5 - Waiting List Job',
                    6 => 'Tahap 6 - Keberangkatan'
                ];

                fputcsv($file, [
                    $key + 1,
                    $profile ? $profile->nama_lengkap : $candidate->name,
                    $candidate->email,
                    $profile ? $profile->nomor_hp : '-',
                    $profile ? ($profile->tempat_lahir . ', ' . ($profile->tanggal_lahir ? $profile->tanggal_lahir->format('d-m-Y') : '')) : '-',
                    $profile ? $profile->alamat : '-',
                    $profile ? $profile->posisi_dilamar : '-',
                    $profile ? $profil->pengalaman_kerja ?? '-' : '-',
                    $latestProcess ? ($tahapMapping[$latestProcess->tahap] ?? 'Tahap ' . $latestProcess->tahap) : 'Tahap 1',
                    $latestProcess ? $latestProcess->status : 'Dalam Proses',
                    $job ? ($job->nama_kapal ?: '-') : '-',
                    $job ? ($job->negara_tujuan ?: '-') : '-',
                    $flight ? (($flight->maskapai . ' / ' . $flight->nomor_penerbangan) ?: '-') : '-',
                    $flight ? ($flight->tanggal_berangkat ? $flight->tanggal_berangkat->format('d-m-Y H:i') : '-') : '-'
                ]);
            }

            fclose($file);
        };

        // Log Activity
        ActivityLog::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Melakukan ekspor laporan data ABK format CSV/Excel.',
        ]);

        return Response::stream($callback, 200, $headers);
    }

    public function printPdf(Request $request)
    {
        $candidates = User::role('abk')->with(['profilAbk', 'prosesPendaftaran', 'jobOrders', 'keberangkatan'])->get();
        
        $stats = [
            'total_abk' => $candidates->count(),
            'abk_onboard' => $candidates->filter(function($c) {
                return $c->keberangkatan->first() && $c->keberangkatan->first()->status === 'On Board';
            })->count(),
            'abk_berangkat' => $candidates->filter(function($c) {
                return $c->keberangkatan->first() && $c->keberangkatan->first()->status !== 'On Board';
            })->count(),
            'waiting_list' => $candidates->filter(function($c) {
                return $c->jobOrders->first() && $c->jobOrders->first()->status_job === 'Waiting List';
            })->count(),
        ];
        
        $tahapMapping = [
            1 => 'Registrasi & Dokumen',
            2 => 'Medical Check-Up',
            3 => 'Diklat',
            4 => 'Dokumen Pelaut',
            5 => 'Waiting List Job',
            6 => 'Keberangkatan'
        ];

        // Log Activity
        ActivityLog::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Mencetak laporan PDF rekrutmen dan penempatan calon ABK.',
        ]);

        return view('reports.print_pdf', compact('candidates', 'tahapMapping', 'stats'));
    }
}
