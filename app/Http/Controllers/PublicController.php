<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Property;
use App\Services\Geo;
use App\Services\SmartMatch;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicController extends Controller
{
    public function home()
    {
        $campuses = Campus::where('is_active', true)
            ->withCount('approvedProperties')
            ->orderBy('name')
            ->get();

        $featured = Property::approved()
            ->with(['photos', 'campus'])
            ->orderBy('is_basic_info')
            ->orderByDesc('data_verified_at')
            ->latest()
            ->take(6)
            ->get();

        $totalApproved = Property::approved()->count();

        $mapPoints = Property::approved()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('photos')
            ->orderByDesc('data_verified_at')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'url' => route('detail', $p->id),
            ])
            ->values();

        $mapCampuses = $campuses->map(fn ($c) => [
            'name' => $c->short_name,
            'lat' => (float) $c->latitude,
            'lng' => (float) $c->longitude,
        ])->values();

        return view('welcome', compact('campuses', 'featured', 'totalApproved', 'mapPoints', 'mapCampuses'));
    }

    public function listings(Request $request)
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        $selectedCampus = null;
        if ($request->filled('campus_id')) {
            $selectedCampus = Campus::find((int) $request->campus_id);
        }

        $query = Property::approved()->with(['photos', 'campus']);

        $keyword = trim((string) $request->input('q', $request->input('location', '')));
        if ($keyword !== '') {
            $query->where(function ($w) use ($keyword) {
                $w->where('name', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        if ($selectedCampus) {
            $query->where('campus_id', $selectedCampus->id);
        }

        if ($request->filled('min_price') && $request->min_price !== '') {
            $query->where('price', '>=', max(1, (int) $request->min_price));
        }

        if ($request->filled('max_price') && $request->max_price !== '') {
            $query->where('price', '>', 0)->where('price', '<=', (int) $request->max_price);
        }

        if (in_array($request->type, ['putra', 'putri', 'campur'], true)) {
            $query->where('type', $request->type);
        }

        $facilities = array_slice(array_filter((array) $request->input('facilities', [])), 0, 6);
        foreach ($facilities as $facility) {
            $query->whereJsonContains('facilities', mb_strtolower(trim($facility)));
        }

        if ($request->boolean('available')) {
            $query->where('available_rooms', '>', 0)->where('is_basic_info', false);
        }

        $sort = $request->input('sort', 'latest');

        $decorate = function (Property $p) use ($selectedCampus) {
            $distance = null;
            if ($selectedCampus && $p->latitude !== null && $p->longitude !== null) {
                $distance = Geo::haversine($p->latitude, $p->longitude, $selectedCampus->latitude, $selectedCampus->longitude);
            }
            $p->setAttribute('distance_km', $distance);

            return $p;
        };

        if ($sort === 'distance' && $selectedCampus) {
            $items = $query->get()->map($decorate)
                ->filter(fn ($p) => $p->distance_km !== null)
                ->sortBy(fn ($p) => $p->distance_km)
                ->values();

            $perPage = 12;
            $page = max(1, $request->integer('page', 1));
            $properties = new LengthAwarePaginator(
                $items->slice(($page - 1) * $perPage, $perPage)->values(),
                $items->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            match ($sort) {
                'price_asc' => $query->orderByRaw('price = 0')->orderBy('price'),
                'price_desc' => $query->orderByDesc('price'),
                default => $query->orderBy('is_basic_info')->orderByDesc('data_verified_at')->orderByDesc('created_at'),
            };

            $properties = $query->paginate(12)->withQueryString();
            $properties->getCollection()->transform($decorate);
        }

        return view('listings', [
            'properties' => $properties,
            'campuses' => $campuses,
            'selectedCampus' => $selectedCampus,
            'facilityOptions' => Property::FACILITY_OPTIONS,
        ]);
    }

    public function detail($id)
    {
        $property = Property::approved()
            ->with(['photos', 'campus', 'owner'])
            ->findOrFail($id);

        $campus = null;
        $distances = collect();

        if ($property->latitude !== null && $property->longitude !== null) {
            $distances = Campus::where('is_active', true)->get()
                ->filter(fn (Campus $c) => $property->latitude !== null)
                ->map(fn (Campus $c) => [
                    'campus' => $c,
                    'km' => Geo::haversine($property->latitude, $property->longitude, $c->latitude, $c->longitude),
                ])
                ->sortBy('km')
                ->take(3)
                ->values();

            $campus = $property->campus ?? $distances->first()['campus'] ?? null;
        }

        $isFavorited = auth()->check()
            && auth()->user()->isTenant()
            && auth()->user()->favorites()->where('property_id', $property->id)->exists();

        $inCompare = in_array($property->id, (array) session('compare_ids', []));

        return view('detail', compact('property', 'campus', 'distances', 'isFavorited', 'inCompare'));
    }

    public function recommendations(Request $request)
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $facilityOptions = Property::FACILITY_OPTIONS;

        $hasInput = $request->anyFilled(['budget', 'campus_id', 'type', 'facilities'])
            || $request->boolean('available_only');

        $results = collect();

        if ($hasInput) {
            $selectedCampus = $request->filled('campus_id') ? Campus::find((int) $request->campus_id) : null;

            $pref = [
                'campus' => $selectedCampus,
                'budget' => $request->input('budget'),
                'max_distance_km' => $request->input('max_distance_km'),
                'facilities' => (array) $request->input('facilities', []),
                'available_only' => $request->boolean('available_only'),
                'extras' => $request->input('extras'),
                'total_budget' => $request->input('total_budget'),
            ];

            $results = Property::approved()
                ->with(['photos', 'campus'])
                ->when(in_array($request->type, ['putra', 'putri', 'campur'], true), fn ($q) => $q->where('type', $request->type))
                ->get()
                ->map(function (Property $p) use ($pref) {
                    $match = SmartMatch::score($p, $pref);
                    $p->setAttribute('match', $match['score']);
                    $p->setAttribute('match_reasons', $match['reasons']);
                    $p->setAttribute('match_notes', $match['notes']);
                    $p->setAttribute('match_distance', $match['distance']);

                    return $p;
                })
                ->filter(fn ($p) => $p->match > 0)
                ->sortByDesc('match')
                ->take(12)
                ->values();
        }

        return view('recommendations', [
            'campuses' => $campuses,
            'facilityOptions' => $facilityOptions,
            'results' => $results,
            'hasInput' => $hasInput,
        ]);
    }

    public function compare(Request $request)
    {
        $ids = array_slice(array_filter((array) session('compare_ids', [])), 0, 3);

        $campus = $request->filled('campus_id') ? Campus::find((int) $request->campus_id) : null;

        $properties = Property::approved()
            ->with(['photos', 'campus'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function (Property $p) use ($campus) {
                $distance = null;
                if ($campus && $p->latitude !== null && $p->longitude !== null) {
                    $distance = Geo::haversine($p->latitude, $p->longitude, $campus->latitude, $campus->longitude);
                }
                $p->setAttribute('distance_km', $distance);

                return $p;
            });

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('compare', [
            'properties' => $properties,
            'campuses' => $campuses,
            'selectedCampus' => $campus,
        ]);
    }

    public function addCompare(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
        ]);

        $property = Property::approved()->findOrFail($data['property_id']);
        $ids = array_values((array) session('compare_ids', []));

        if (in_array($property->id, $ids)) {
            return back()->with('error', $property->name . ' sudah ada di daftar perbandingan.');
        }

        if (count($ids) >= 3) {
            return back()->with('error', 'Maksimal 3 kost yang bisa dibandingkan. Hapus salah satu dulu.');
        }

        $ids[] = $property->id;
        session(['compare_ids' => $ids]);

        return back()->with('success', $property->name . ' ditambahkan ke perbandingan.');
    }

    public function removeCompare($id)
    {
        $ids = array_values(array_filter((array) session('compare_ids', []), fn ($v) => (int) $v !== (int) $id));
        session(['compare_ids' => $ids]);

        return redirect()->route('compare')->with('success', 'Kost dihapus dari perbandingan.');
    }

    public function costPlanner(Request $request)
    {
        $kosts = Property::approved()->orderBy('name')->get([
            'id', 'name', 'price', 'electricity_included', 'internet_included',
            'electricity_fee', 'internet_fee', 'campus_id',
        ]);

        $selected = $request->filled('kost_id')
            ? $kosts->firstWhere('id', (int) $request->kost_id)
            : null;

        return view('cost-planner', [
            'kosts' => $kosts,
            'selected' => $selected,
        ]);
    }

    public function help()
    {
        return view('info-page', [
            'type' => 'help',
            'title' => 'Pusat Bantuan',
            'subtitle' => 'Temukan jawaban untuk pertanyaan seputar penggunaan Kostly.',
        ]);
    }

    public function howBooking()
    {
        return view('info-page', [
            'type' => 'booking',
            'title' => 'Cara Booking',
            'subtitle' => 'Ikuti beberapa langkah sederhana untuk mengajukan booking kost.',
        ]);
    }

    public function contact()
    {
        return view('info-page', [
            'type' => 'contact',
            'title' => 'Hubungi Kami',
            'subtitle' => 'Ada pertanyaan atau kendala? Kostly siap membantu.',
        ]);
    }
}
