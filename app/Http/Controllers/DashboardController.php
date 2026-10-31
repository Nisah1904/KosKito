<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Campus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isOwner()) {
            return $this->ownerDashboard($user);
        }

        return $this->tenantDashboard($user);
    }

    private function adminDashboard()
    {
        $stats = [
            'users' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'owners' => User::where('role', User::ROLE_OWNER)->count(),
            'tenants' => User::where('role', User::ROLE_TENANT)->count(),
            'properties' => Property::count(),
            'approved' => Property::approved()->count(),
            'verified' => Property::approved()->whereNotNull('data_verified_at')->count(),
            'pending' => Property::where('status', Property::STATUS_PENDING)->count(),
            'bookings' => Booking::count(),
            'pendingBookings' => Booking::where('status', Booking::STATUS_PENDING)->count(),
            'campuses' => Campus::where('is_active', true)->count(),
        ];

        $pendingProperties = Property::with('owner')->where('status', Property::STATUS_PENDING)->latest()->take(5)->get();
        $recentBookings = Booking::with(['property', 'tenant'])->latest()->take(5)->get();

        return view('dashboard', [
            'role' => User::ROLE_ADMIN,
            'stats' => $stats,
            'pendingProperties' => $pendingProperties,
            'recentBookings' => $recentBookings,
        ]);
    }

    private function ownerDashboard(User $user)
    {
        $properties = $user->properties()->with('photos')->latest()->get();

        $stats = [
            'total' => $properties->count(),
            'approved' => $properties->where('status', Property::STATUS_APPROVED)->count(),
            'pending' => $properties->where('status', Property::STATUS_PENDING)->count(),
            'rejected' => $properties->where('status', Property::STATUS_REJECTED)->count(),
            'rooms' => $properties->sum('rooms'),
            'availableRooms' => $properties->sum('available_rooms'),
        ];

        $incoming = Booking::whereHas('property', fn ($q) => $q->where('owner_id', $user->id))
            ->with(['property', 'tenant'])
            ->latest()
            ->take(5)
            ->get();

        $stats['pendingBookings'] = Booking::whereHas('property', fn ($q) => $q->where('owner_id', $user->id))
            ->where('status', Booking::STATUS_PENDING)
            ->count();

        return view('dashboard', [
            'role' => User::ROLE_OWNER,
            'stats' => $stats,
            'properties' => $properties,
            'incoming' => $incoming,
        ]);
    }

    private function tenantDashboard(User $user)
    {
        $stats = [
            'favorites' => $user->favorites()->count(),
            'bookings' => $user->bookings()->count(),
            'pendingBookings' => $user->bookings()->where('status', Booking::STATUS_PENDING)->count(),
            'confirmedBookings' => $user->bookings()->where('status', Booking::STATUS_CONFIRMED)->count(),
        ];

        $bookings = $user->bookings()->with(['property.photos'])->latest()->take(5)->get();
        $favorites = $user->favorites()->with('photos')->latest('favorites.created_at')->take(3)->get();

        return view('dashboard', [
            'role' => User::ROLE_TENANT,
            'stats' => $stats,
            'bookings' => $bookings,
            'favorites' => $favorites,
        ]);
    }
}
