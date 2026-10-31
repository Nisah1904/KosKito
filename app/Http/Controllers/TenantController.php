<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function favorites()
    {
        $favorites = auth()->user()->favorites()
            ->with(['photos', 'campus'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('favorites', compact('favorites'));
    }

    public function toggleFavorite($id)
    {
        $property = Property::approved()->findOrFail($id);
        $user = auth()->user();

        $exists = $user->favorites()->where('property_id', $property->id)->exists();

        if ($exists) {
            $user->favorites()->detach($property->id);

            return back()->with('success', $property->name . ' dihapus dari favorit.');
        }

        $user->favorites()->attach($property->id);

        return back()->with('success', $property->name . ' ditambahkan ke favorit.');
    }

    public function showBooking($id)
    {
        $property = Property::approved()->with(['photos', 'campus'])->findOrFail($id);

        return view('booking', compact('property'));
    }

    public function storeBooking(Request $request, $id)
    {
        $data = $request->validate([
            'duration' => ['required', 'integer', 'in:1,3,6,12'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'duration.required' => 'Pilih durasi sewa.',
            'duration.in' => 'Durasi sewa tidak valid.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'note.max' => 'Catatan maksimal 500 karakter.',
        ]);

        $result = DB::transaction(function () use ($data, $id) {
            $property = Property::whereKey($id)
                ->where('status', Property::STATUS_APPROVED)
                ->lockForUpdate()
                ->first();

            if (!$property) {
                return 'not_found';
            }

            if ($property->available_rooms < 1) {
                return 'full';
            }

            $activeExists = Booking::where('tenant_id', auth()->id())
                ->where('property_id', $property->id)
                ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
                ->lockForUpdate()
                ->exists();

            if ($activeExists) {
                return 'duplicate';
            }

            Booking::create([
                'property_id' => $property->id,
                'tenant_id' => auth()->id(),
                'duration' => (int) $data['duration'],
                'start_date' => $data['start_date'],
                'note' => $data['note'] ?? null,
                'status' => Booking::STATUS_PENDING,
                'code' => 'KST-' . strtoupper(Str::random(6)),
            ]);

            return 'ok';
        });

        if ($result === 'not_found') {
            abort(404);
        }

        if ($result === 'full') {
            return back()->with('error', 'Kamar pada kost ini sedang penuh. Ketersediaan bisa berubah — cek lagi nanti.');
        }

        if ($result === 'duplicate') {
            return back()->with('error', 'Kamu masih punya booking aktif di kost ini. Pantau statusnya di riwayat booking.');
        }

        return redirect()
            ->route('booking.history')
            ->with('success', 'Booking berhasil diajukan. Statusnya menunggu konfirmasi pemilik.');
    }

    public function bookingHistory()
    {
        $bookings = auth()->user()->bookings()
            ->with(['property.photos', 'property.campus'])
            ->latest()
            ->get();

        return view('booking-history', compact('bookings'));
    }

    public function cancelBooking($id)
    {
        $booking = auth()->user()->bookings()->findOrFail($id);

        if ($booking->status !== Booking::STATUS_PENDING) {
            return back()->with('error', 'Hanya booking yang masih menunggu konfirmasi pemilik yang dapat dibatalkan.');
        }

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Booking ' . $booking->code . ' dibatalkan.');
    }
}
