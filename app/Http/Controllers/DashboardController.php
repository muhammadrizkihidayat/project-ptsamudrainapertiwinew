<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Dokumen;
use App\Models\MedicalCheckup;
use App\Models\Diklat;
use App\Models\JobOrder;
use App\Models\Keberangkatan;
use App\Models\ActivityLog;
use App\Models\ProsesPendaftaran;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('super_admin')) {
            return redirect('/dashboard/admin');
        } elseif ($user->hasRole('operator')) {
            return redirect('/dashboard/operator');
        } elseif ($user->hasRole('abk')) {
            return redirect('/dashboard/abk');
        }

        return redirect('/');
    }

    public function admin()
    {
        $stats = $this->getStats();
        
        // Activity logs (last 20)
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Stats for charts
        $monthlyRegistrations = User::where('role', 'abk')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Fill empty months
        $registrationsChartData = [];
        for ($m = 1; $m <= 12; $m++) {
            $registrationsChartData[] = $monthlyRegistrations[$m] ?? 0;
        }

        // MCU stats
        $mcuStats = [
            'Lulus' => MedicalCheckup::where('status_mcu', 'Lulus MCU')->count(),
            'Pending' => MedicalCheckup::where('status_mcu', 'Pending MCU')->count(),
            'Gagal' => MedicalCheckup::where('status_mcu', 'Tidak Lulus MCU')->count(),
        ];

        // Diklat stats
        $diklatStats = [
            'Lulus' => Diklat::where('status', 'Lulus Diklat')->count(),
            'Gagal' => Diklat::where('status', 'Tidak Lulus Diklat')->count(),
            'Aktif' => Diklat::whereIn('status', ['Terjadwal', 'Sedang Mengikuti Diklat'])->count(),
        ];

        // Placement by country
        $placements = JobOrder::whereNotNull('negara_tujuan')
            ->where('negara_tujuan', '!=', '')
            ->selectRaw('negara_tujuan, COUNT(*) as total')
            ->groupBy('negara_tujuan')
            ->pluck('total', 'negara_tujuan')
            ->toArray();

        return view('dashboard.admin', compact('stats', 'logs', 'registrationsChartData', 'mcuStats', 'diklatStats', 'placements'));
    }

    public function operator()
    {
        $stats = $this->getStats();
        
        // List of all candidates with their current stage
        $candidates = User::role('abk')
            ->with(['profilAbk', 'prosesPendaftaran'])
            ->get()
            ->map(function ($u) {
                $latestProcess = $u->prosesPendaftaran->sortByDesc('updated_at')->first();
                $u->current_tahap = $latestProcess ? $latestProcess->tahap : 1;
                $u->tahap_status = $latestProcess ? $latestProcess->status : 'Dalam Proses';
                return $u;
            });

        return view('dashboard.operator', compact('stats', 'candidates'));
    }

    public function abk()
    {
        $user = Auth::user();
        
        // Ensure user is loaded with necessary relations
        $user->load(['profilAbk', 'dokumen', 'medicalCheckups', 'diklat', 'dokumenPelaut', 'jobOrders', 'keberangkatan', 'prosesPendaftaran', 'notifications']);

        // Get latest stage
        $proses = $user->prosesPendaftaran->sortByDesc('created_at')->first();
        $tahapAktif = $proses ? $proses->tahap : 1;
        $statusTahap = $proses ? $proses->status : 'Dalam Proses';
        $catatanTahap = $proses ? $proses->catatan : '';

        // Calculate document completion percentage (Stage 1 requires 5 files: ktp, kartu_keluarga, skck, akta_kelahiran, ijazah)
        $requiredDocs = ['ktp', 'kartu_keluarga', 'skck', 'akta_kelahiran', 'ijazah'];
        $uploadedCount = $user->dokumen->whereIn('jenis_dokumen', $requiredDocs)->count();
        $approvedCount = $user->dokumen->whereIn('jenis_dokumen', $requiredDocs)->where('status', 'Disetujui')->count();
        $docPercent = count($requiredDocs) > 0 ? round(($uploadedCount / count($requiredDocs)) * 100) : 0;

        // Get notifications
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();

        return view('dashboard.abk', compact('user', 'tahapAktif', 'statusTahap', 'catatanTahap', 'docPercent', 'approvedCount', 'notifications'));
    }

    private function getStats()
    {
        return [
            'total_abk' => User::role('abk')->count(),
            'total_operator' => User::role('operator')->count(),
            'dokumen_pending' => Dokumen::where('status', 'Menunggu Verifikasi')->count(),
            'dokumen_approved' => Dokumen::where('status', 'Disetujui')->count(),
            'dokumen_rejected' => Dokumen::where('status', 'Ditolak')->count(),
            'mcu_pending' => MedicalCheckup::where('status_mcu', 'Menunggu Verifikasi MCU')->count(),
            'mcu_hold' => MedicalCheckup::where('status_mcu', 'Pending MCU')->count(),
            'diklat_aktif' => Diklat::whereIn('status', ['Terjadwal', 'Sedang Mengikuti Diklat'])->count(),
            'waiting_list' => JobOrder::where('status_job', 'Waiting List')->count(),
            'abk_berangkat' => Keberangkatan::where('status', 'Berangkat')->count(),
            'abk_onboard' => Keberangkatan::where('status', 'On Board')->count(),
        ];
    }
}
