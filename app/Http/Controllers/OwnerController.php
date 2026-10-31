<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Campus;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OwnerController extends Controller
{
    private function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    private function scoped()
    {
        return $this->isAdmin() ? Property::query() : auth()->user()->properties();
    }

    private function prefix(): string
    {
        return $this->isAdmin() ? 'admin.kost' : 'owner.properties';
    }

    private function formData(array $extra = []): array
    {
        return [
            'rp' => $this->prefix(),
            'isAdmin' => $this->isAdmin(),
            'campuses' => Campus::where('is_active', true)->orderBy('name')->get(),
            'facilityOptions' => Property::FACILITY_OPTIONS,
            'owners' => $this->isAdmin() ? User::whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN])->orderBy('name')->get(['id', 'name', 'email', 'role']) : collect(),
            ...$extra,
        ];
    }

    public function properties(Request $request)
    {
        $properties = $this->scoped()
            ->with(['photos', 'campus', 'owner'])
            ->withCount(['bookings as pending_bookings' => fn ($q) => $q->where('status', Booking::STATUS_PENDING)])
            ->when($this->isAdmin() && $request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%' . $request->q . '%')->orWhere('address', 'like', '%' . $request->q . '%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('owner-panel', $this->formData(['page' => 'properties', 'properties' => $properties]));
    }

    public function create()
    {
        return view('owner-panel', $this->formData(['page' => 'create']));
    }

    public function store(Request $request)
    {
        $data = $this->validateProperty($request);

        $property = DB::transaction(function () use ($data, $request) {
            $attrs = $this->attributes($data);

            if ($this->isAdmin()) {
                $property = Property::create([
                    ...$attrs,
                    'owner_id' => $data['owner_id'] ?? auth()->id(),
                    'status' => Property::STATUS_APPROVED,
                    'source' => 'admin',
                    'data_verified_at' => $request->boolean('mark_verified') ? now() : null,
                    'verified_by' => $request->boolean('mark_verified') ? auth()->id() : null,
                ]);
            } else {
                $property = auth()->user()->properties()->create([
                    ...$attrs,
                    'status' => Property::STATUS_PENDING,
                    'source' => 'owner',
                ]);
            }

            $this->savePhotos($request, $property);

            return $property;
        });

        return redirect()
            ->route($this->prefix())
            ->with('success', $this->isAdmin()
                ? 'Kost "' . $property->name . '" ditambahkan dan langsung tampil publik.'
                : 'Kost "' . $property->name . '" diajukan dan menunggu verifikasi admin.');
    }

    public function edit($id)
    {
        $property = $this->scoped()->with(['photos', 'campus'])->findOrFail($id);

        return view('owner-panel', $this->formData(['page' => 'edit', 'property' => $property]));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateProperty($request);
        $property = $this->scoped()->findOrFail($id);

        $resubmitted = !$this->isAdmin() && $property->status === Property::STATUS_REJECTED;

        DB::transaction(function () use ($request, $property, $data, $resubmitted) {
            $property->fill($this->attributes($data));

            if ($this->isAdmin()) {
                if (!empty($data['owner_id'])) {
                    $property->owner_id = $data['owner_id'];
                }
                if ($request->boolean('mark_verified') && $property->data_verified_at === null) {
                    $property->data_verified_at = now();
                    $property->verified_by = auth()->id();
                } elseif (!$request->boolean('mark_verified')) {
                    $property->data_verified_at = null;
                    $property->verified_by = null;
                }
            } else {
                $property->is_basic_info = false;
                if ($property->data_verified_at !== null && $property->isDirty(['name', 'price', 'address', 'location', 'latitude', 'longitude', 'type'])) {
                    $property->data_verified_at = null;
                    $property->verified_by = null;
                }
            }

            if ($resubmitted) {
                $property->status = Property::STATUS_PENDING;
                $property->rejection_reason = null;
            }

            $property->save();
            $this->savePhotos($request, $property);
        });

        return redirect()
            ->route($this->prefix())
            ->with('success', $resubmitted
                ? 'Perbaikan dikirim ulang. Kost kembali menunggu verifikasi admin.'
                : 'Kost "' . $property->name . '" berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $property = $this->scoped()->findOrFail($id);

        if ($property->status === Property::STATUS_APPROVED) {
            $property->update(['status' => Property::STATUS_INACTIVE]);

            return back()->with('success', 'Kost "' . $property->name . '" dinonaktifkan dan disembunyikan dari pencarian publik.');
        }

        if ($property->status === Property::STATUS_INACTIVE) {
            $property->update([
                'status' => $this->isAdmin() || $property->data_verified_at !== null
                    ? Property::STATUS_APPROVED
                    : Property::STATUS_PENDING,
            ]);

            return back()->with('success', 'Kost "' . $property->name . '" diaktifkan kembali.');
        }

        return back()->with('error', 'Listing yang sedang menunggu verifikasi atau ditolak tidak bisa dinonaktifkan.');
    }

    public function destroy($id)
    {
        $property = $this->scoped()->findOrFail($id);

        $active = $property->bookings()->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])->exists();
        if ($active) {
            return back()->with('error', 'Kost "' . $property->name . '" masih punya booking aktif. Selesaikan atau tolak booking tersebut dulu, atau gunakan "Nonaktifkan".');
        }

        DB::transaction(function () use ($property) {
            foreach ($property->photos as $photo) {
                if (!str_starts_with($photo->path, 'http')) {
                    Storage::disk('public')->delete($photo->path);
                }
            }
            $property->photos()->delete();
            $property->favorites()->delete();
            $property->bookings()->delete();
            $property->delete();
        });

        return back()->with('success', 'Kost "' . $property->name . '" dihapus.');
    }

    public function destroyPhoto($id, $photoId)
    {
        $property = $this->scoped()->findOrFail($id);
        $photo = $property->photos()->findOrFail($photoId);

        if (!str_starts_with($photo->path, 'http')) {
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();

        return back()->with('success', 'Foto dihapus.');
    }

    private function attributes(array $data): array
    {
        $price = (int) ($data['price'] ?? 0);

        return [
            ...collect($data)->except(['facilities_extra', 'photos', 'owner_id', 'mark_verified'])->all(),
            'price' => $price,
            'rooms' => (int) ($data['rooms'] ?? 0),
            'available_rooms' => min((int) ($data['available_rooms'] ?? 0), (int) ($data['rooms'] ?? 0)),
            'electricity_included' => !empty($data['electricity_included']),
            'internet_included' => !empty($data['internet_included']),
            'electricity_fee' => (int) ($data['electricity_fee'] ?? 0),
            'internet_fee' => (int) ($data['internet_fee'] ?? 0),
            'facilities' => $this->normalizeFacilities($data['facilities'] ?? [], $data['facilities_extra'] ?? null),
            'is_basic_info' => $this->isAdmin() ? ($price === 0 || (int) ($data['rooms'] ?? 0) === 0) : false,
        ];
    }

    public function rooms()
    {
        $properties = auth()->user()->properties()->with('photos')->orderBy('name')->get();

        return view('owner-panel', ['isAdmin' => false, 'rp' => 'owner.properties',
            'page' => 'rooms',
            'properties' => $properties,
        ]);
    }

    public function updateRooms(Request $request, $id)
    {
        $property = auth()->user()->properties()->findOrFail($id);

        $data = $request->validate([
            'available_rooms' => ['required', 'integer', 'min:0', 'max:' . max(0, $property->rooms)],
        ], [
            'available_rooms.required' => 'Jumlah kamar tersedia wajib diisi.',
            'available_rooms.max' => 'Kamar tersedia tidak boleh lebih dari total kamar (' . $property->rooms . ').',
        ]);

        $property->update(['available_rooms' => (int) $data['available_rooms']]);

        return back()->with('success', 'Ketersediaan kamar "' . $property->name . '" diperbarui.');
    }

    public function bookings()
    {
        $bookings = Booking::whereHas('property', fn ($q) => $q->where('owner_id', auth()->id()))
            ->with(['property.photos', 'tenant'])
            ->latest()
            ->get();

        return view('owner-panel', ['isAdmin' => false, 'rp' => 'owner.properties',
            'page' => 'bookings',
            'bookings' => $bookings,
        ]);
    }

    public function acceptBooking($id)
    {
        $result = DB::transaction(function () use ($id) {
            $booking = Booking::whereHas('property', fn ($q) => $q->where('owner_id', auth()->id()))
                ->lockForUpdate()
                ->findOrFail($id);

            if ($booking->status !== Booking::STATUS_PENDING) {
                return 'invalid';
            }

            $property = Property::whereKey($booking->property_id)->lockForUpdate()->first();

            if ($property->available_rooms < 1) {
                return 'full';
            }

            $property->decrement('available_rooms');
            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'responded_at' => now(),
            ]);

            return 'ok';
        });

        if ($result === 'invalid') {
            return back()->with('error', 'Booking ini sudah pernah diproses (bukan status menunggu).');
        }

        if ($result === 'full') {
            return back()->with('error', 'Kamar sudah penuh. Perbarui jumlah kamar tersedia, lalu tolak booking ini bila perlu.');
        }

        return back()->with('success', 'Booking diterima. Satu kamar otomatis dikurangi dari ketersediaan.');
    }

    public function rejectBooking(Request $request, $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Tuliskan alasan penolakan agar penyewa paham.']);

        $booking = Booking::whereHas('property', fn ($q) => $q->where('owner_id', auth()->id()))
            ->findOrFail($id);

        if ($booking->status !== Booking::STATUS_PENDING) {
            return back()->with('error', 'Booking ini sudah pernah diproses (bukan status menunggu).');
        }

        $booking->update([
            'status' => Booking::STATUS_REJECTED,
            'rejection_reason' => $data['reason'],
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Booking ditolak. Penyewa dapat melihat alasan penolakan.');
    }

    public function completeBooking($id)
    {
        $booking = Booking::whereHas('property', fn ($q) => $q->where('owner_id', auth()->id()))
            ->findOrFail($id);

        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            return back()->with('error', 'Hanya booking berstatus "Diterima" yang dapat ditandai selesai.');
        }

        DB::transaction(function () use ($booking) {
            $property = Property::whereKey($booking->property_id)->lockForUpdate()->first();
            $property->available_rooms = min($property->rooms, $property->available_rooms + 1);
            $property->save();

            $booking->update(['status' => Booking::STATUS_COMPLETED]);
        });

        return back()->with('success', 'Booking ditandai selesai. Satu kamar dikembalikan ke ketersediaan.');
    }

    public function statistics()
    {
        $user = auth()->user();

        $properties = $user->properties()->with('photos')->latest()->get();
        $propertyIds = $properties->pluck('id');

        $bookings = Booking::whereIn('property_id', $propertyIds)->with(['property', 'tenant'])->latest()->take(10)->get();

        return view('owner-panel', ['isAdmin' => false, 'rp' => 'owner.properties',
            'page' => 'statistics',
            'properties' => $properties,
            'bookings' => $bookings,
            'bookingStats' => [
                'total' => Booking::whereIn('property_id', $propertyIds)->count(),
                'pending' => Booking::whereIn('property_id', $propertyIds)->where('status', Booking::STATUS_PENDING)->count(),
                'confirmed' => Booking::whereIn('property_id', $propertyIds)->where('status', Booking::STATUS_CONFIRMED)->count(),
                'rejected' => Booking::whereIn('property_id', $propertyIds)->where('status', Booking::STATUS_REJECTED)->count(),
                'completed' => Booking::whereIn('property_id', $propertyIds)->where('status', Booking::STATUS_COMPLETED)->count(),
            ],
        ]);
    }

    private function validateProperty(Request $request): array
    {
        $admin = $this->isAdmin();

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => [$admin ? 'nullable' : 'required', 'integer', 'min:50000', 'max:100000000'],
            'owner_id' => [$admin ? 'nullable' : 'prohibited', 'exists:users,id'],
            'mark_verified' => ['nullable', 'boolean'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'available_rooms' => [$admin ? 'nullable' : 'required', 'integer', 'min:0', 'max:200', ...($request->filled('rooms') ? ['lte:rooms'] : ($admin ? ['in:0'] : []))],
            'location' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'type' => ['required', $admin ? 'in:putra,putri,campur,unknown' : 'in:putra,putri,campur'],
            'rooms' => [$admin ? 'nullable' : 'required', 'integer', $admin ? 'min:0' : 'min:1', 'max:200'],
            'facilities' => [$admin ? 'nullable' : 'required', 'array', $admin ? 'min:0' : 'min:1'],
            'facilities.*' => ['string', 'max:40'],
            'facilities_extra' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:2000'],
            'rules' => ['nullable', 'string', 'max:1000'],
            'electricity_included' => ['nullable', 'boolean'],
            'internet_included' => ['nullable', 'boolean'],
            'electricity_fee' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'internet_fee' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'phone' => ['nullable', 'string', 'max:25'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama kost wajib diisi.',
            'price.required' => 'Harga sewa wajib diisi.',
            'price.min' => 'Harga sewa minimal Rp50.000.',
            'location.required' => 'Kawasan/lokasi wajib diisi.',
            'address.required' => 'Alamat lengkap wajib diisi agar tampil di peta.',
            'type.required' => 'Pilih tipe kost.',
            'type.in' => 'Tipe kost tidak valid.',
            'rooms.required' => 'Jumlah kamar wajib diisi.',
            'facilities.required' => 'Pilih minimal satu fasilitas.',
            'available_rooms.lte' => 'Kamar tersedia tidak boleh lebih dari jumlah kamar.',
            'available_rooms.in' => 'Isi jumlah kamar dulu sebelum mengisi kamar tersedia.',
            'available_rooms.required' => 'Jumlah kamar tersedia wajib diisi.',
            'source_url.url' => 'Link sumber harus berupa URL yang valid.',
            'photos.max' => 'Maksimal 4 foto.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus jpg, jpeg, png, atau webp.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 2 MB.',
            'latitude.between' => 'Latitude harus antara -90 sampai 90.',
            'longitude.between' => 'Longitude harus antara -180 sampai 180.',
        ]);
    }

    private function normalizeFacilities(?array $facilities, ?string $extra): array
    {
        $items = array_map(fn ($f) => mb_strtolower(trim($f)), $facilities ?? []);

        if (!empty($extra)) {
            $items = array_merge($items, preg_split('/[,;]+/', $extra) ?: []);
        }

        return array_values(array_unique(array_filter(array_map(fn ($f) => mb_substr(mb_strtolower(trim($f)), 0, 40), $items))));
    }

    private function savePhotos(Request $request, Property $property): void
    {
        if (!$request->hasFile('photos')) {
            return;
        }

        $currentMax = $property->photos()->max('sort') ?? -1;

        foreach ((array) $request->file('photos', []) as $file) {
            if ($file === null || !$file->isValid()) {
                continue;
            }

            $path = $file->store('properties', 'public');

            PropertyPhoto::create([
                'property_id' => $property->id,
                'path' => $path,
                'sort' => ++$currentMax,
            ]);
        }
    }
}
