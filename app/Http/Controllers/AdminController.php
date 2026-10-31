<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Campus;
use App\Models\DataImport;
use App\Models\Property;
use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function verification()
    {
        $properties = Property::with(['owner', 'photos', 'campus'])
            ->where('status', Property::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        return view('admin-panel', [
            'page' => 'verification',
            'properties' => $properties,
        ]);
    }

    public function approve($id)
    {
        $property = Property::findOrFail($id);

        $property->update([
            'status' => Property::STATUS_APPROVED,
            'data_verified_at' => now(),
            'verified_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Kost "' . $property->name . '" disetujui dan kini tampil sebagai listing terverifikasi.');
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Tuliskan alasan penolakan agar pemilik tahu apa yang harus diperbaiki.']);

        $property = Property::findOrFail($id);

        $property->update([
            'status' => Property::STATUS_REJECTED,
            'rejection_reason' => $data['reason'],
        ]);

        return back()->with('success', 'Kost "' . $property->name . '" ditolak. Pemilik dapat memperbaiki lalu mengirim ulang.');
    }

    public function users()
    {
        $users = User::withCount(['properties', 'bookings'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin-panel', [
            'page' => 'users',
            'users' => $users,
            'roleCounts' => [
                'admin' => User::where('role', User::ROLE_ADMIN)->count(),
                'pemilik' => User::where('role', User::ROLE_OWNER)->count(),
                'penyewa' => User::where('role', User::ROLE_TENANT)->count(),
                'total' => User::count(),
            ],
        ]);
    }

    public function bookings()
    {
        $bookings = Booking::with(['property.owner', 'tenant'])->latest()->get();

        return view('admin-panel', [
            'page' => 'bookings',
            'bookings' => $bookings,
        ]);
    }

    public function campuses()
    {
        return view('admin-panel', [
            'page' => 'campuses',
            'campuses' => Campus::withCount('properties')->orderBy('name')->get(),
        ]);
    }

    public function storeCampus(Request $request)
    {
        $data = $this->validateCampus($request);

        Campus::create($data);

        return back()->with('success', 'Kampus "' . $data['name'] . '" ditambahkan.');
    }

    public function updateCampus(Request $request, $id)
    {
        $campus = Campus::findOrFail($id);
        $data = $this->validateCampus($request);

        $campus->update($data);

        return back()->with('success', 'Kampus "' . $data['name'] . '" diperbarui.');
    }

    public function destroyCampus($id)
    {
        $campus = Campus::findOrFail($id);

        if ($campus->properties()->exists()) {
            return back()->with('error', 'Kampus masih dipakai oleh listing kost. Pindahkan listing ke kampus lain atau nonaktifkan kampus saja.');
        }

        $campus->delete();

        return back()->with('success', 'Kampus "' . $campus->name . '" dihapus.');
    }

    public function import()
    {
        return view('admin-panel', [
            'page' => 'import',
            'imports' => DataImport::with('user')->latest()->take(10)->get(),
        ]);
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ], [
            'file.required' => 'Pilih file CSV terlebih dahulu.',
            'file.mimes' => 'Berkas harus berformat .csv atau .txt.',
            'file.max' => 'Ukuran file maksimal 4 MB.',
        ]);

        $file = $request->file('file');
        $report = app(CsvImportService::class)->importFromCsv($file->getRealPath(), auth()->id());

        if (isset($report['error'])) {
            return back()->with('error', 'Impor gagal: ' . $report['error']);
        }

        DataImport::create([
            'user_id' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'source' => 'csv',
            'total_rows' => $report['total'],
            'success_rows' => $report['success'],
            'failed_rows' => $report['failed'],
            'duplicate_rows' => $report['duplicate'],
            'errors' => array_slice($report['errors'], 0, 100),
        ]);

        $summary = "Impor selesai: {$report['success']} baris berhasil, {$report['failed']} gagal, {$report['duplicate']} duplikat dari {$report['total']} baris. Semua listing hasil impor berstatus MENUNGGU VERIFIKASI — tidak otomatis terverifikasi.";

        return back()
            ->with('success', $summary)
            ->with('import_report', $report);
    }

    public function statistics()
    {
        return view('admin-panel', [
            'page' => 'statistics',
            'stats' => [
                'users' => User::count(),
                'owners' => User::where('role', User::ROLE_OWNER)->count(),
                'tenants' => User::where('role', User::ROLE_TENANT)->count(),
                'properties' => Property::count(),
                'approved' => Property::approved()->count(),
                'pending' => Property::where('status', Property::STATUS_PENDING)->count(),
                'rejected' => Property::where('status', Property::STATUS_REJECTED)->count(),
                'web' => Property::where('is_basic_info', true)->count(),
                'bookings' => Booking::count(),
                'campuses' => Campus::count(),
                'imports' => DataImport::count(),
            ],
        ]);
    }

    private function validateCampus(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama kampus wajib diisi.',
            'latitude.required' => 'Koordinat latitude wajib diisi agar jarak bisa dihitung.',
            'longitude.required' => 'Koordinat longitude wajib diisi agar jarak bisa dihitung.',
            'latitude.between' => 'Latitude harus antara -90 sampai 90.',
            'longitude.between' => 'Longitude harus antara -180 sampai 180.',
        ]);
    }
}
