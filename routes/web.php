<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC — tidak perlu login
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/cari-kost', [PublicController::class, 'listings'])->name('listings');
Route::get('/kost/{id}', [PublicController::class, 'detail'])->name('detail');
Route::get('/rekomendasi', [PublicController::class, 'recommendations'])->name('recommendations');
Route::get('/compare', [PublicController::class, 'compare'])->name('compare');
Route::get('/hitung-biaya', [PublicController::class, 'costPlanner'])->name('cost-planner');
Route::get('/bantuan', [PublicController::class, 'help'])->name('help');
Route::get('/cara-booking', [PublicController::class, 'howBooking'])->name('how-booking');
Route::get('/hubungi-kami', [PublicController::class, 'contact'])->name('contact');

Route::post('/compare/tambah', [PublicController::class, 'addCompare'])->name('compare.add');
Route::post('/compare/{id}/hapus', [PublicController::class, 'removeCompare'])->name('compare.remove');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process')->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.process');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::get('/dashboard/{role}', function () {
    return redirect()->route('dashboard');
})->middleware('auth')->whereIn('role', ['admin', 'pemilik', 'penyewa']);

/*
|--------------------------------------------------------------------------
| PENYEWA — fitur personal
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::view('/profil', 'profile')->name('profile');
    Route::get('/asisten', [AiController::class, 'index'])->name('assistant');
    Route::post('/asisten/kirim', [AiController::class, 'send'])->name('assistant.send');
    Route::delete('/asisten/{id}', [AiController::class, 'destroy'])->name('assistant.destroy');
    Route::post('/rekomendasi/jelaskan/{id}', [AiController::class, 'explain'])->name('recommendations.explain');
});

Route::middleware(['auth', 'role:penyewa'])->group(function () {
    Route::get('/favorit', [TenantController::class, 'favorites'])->name('favorites');
    Route::post('/favorit/{id}', [TenantController::class, 'toggleFavorite'])->name('favorites.toggle');

    Route::get('/booking/{id}', [TenantController::class, 'showBooking'])->name('booking');
    Route::post('/booking/{id}', [TenantController::class, 'storeBooking'])->name('booking.store');
    Route::post('/booking/{id}/batal', [TenantController::class, 'cancelBooking'])->name('booking.cancel');

    Route::get('/riwayat-booking', [TenantController::class, 'bookingHistory'])->name('booking.history');
});

/*
|--------------------------------------------------------------------------
| PEMILIK KOST
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pemilik'])->group(function () {
    Route::get('/pemilik/kost', [OwnerController::class, 'properties'])->name('owner.properties');
    Route::get('/pemilik/kost/tambah', [OwnerController::class, 'create'])->name('owner.properties.create');
    Route::post('/pemilik/kost', [OwnerController::class, 'store'])->name('owner.properties.store');
    Route::get('/pemilik/kost/{id}/edit', [OwnerController::class, 'edit'])->name('owner.properties.edit');
    Route::post('/pemilik/kost/{id}/update', [OwnerController::class, 'update'])->name('owner.properties.update');
    Route::post('/pemilik/kost/{id}/status', [OwnerController::class, 'toggleStatus'])->name('owner.properties.toggle');
    Route::post('/pemilik/kost/{id}/hapus', [OwnerController::class, 'destroy'])->name('owner.properties.destroy');
    Route::post('/pemilik/kost/{id}/foto/{photo}/hapus', [OwnerController::class, 'destroyPhoto'])->name('owner.properties.photo.destroy');

    Route::get('/pemilik/kamar', [OwnerController::class, 'rooms'])->name('owner.rooms');
    Route::post('/pemilik/kamar/{id}', [OwnerController::class, 'updateRooms'])->name('owner.rooms.update');

    Route::get('/pemilik/booking', [OwnerController::class, 'bookings'])->name('owner.bookings');
    Route::post('/pemilik/booking/{id}/terima', [OwnerController::class, 'acceptBooking'])->name('owner.booking.accept');
    Route::post('/pemilik/booking/{id}/tolak', [OwnerController::class, 'rejectBooking'])->name('owner.booking.reject');
    Route::post('/pemilik/booking/{id}/selesaikan', [OwnerController::class, 'completeBooking'])->name('owner.booking.complete');

    Route::get('/pemilik/statistik', [OwnerController::class, 'statistics'])->name('owner.statistics');
});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/verifikasi', [AdminController::class, 'verification'])->name('admin.verification');
    Route::post('/admin/kost/{id}/setuju', [AdminController::class, 'approve'])->name('admin.property.approve');
    Route::post('/admin/kost/{id}/tolak', [AdminController::class, 'reject'])->name('admin.property.reject');

    Route::get('/admin/kost', [OwnerController::class, 'properties'])->name('admin.kost');
    Route::get('/admin/kost/tambah', [OwnerController::class, 'create'])->name('admin.kost.create');
    Route::post('/admin/kost', [OwnerController::class, 'store'])->name('admin.kost.store');
    Route::get('/admin/kost/{id}/edit', [OwnerController::class, 'edit'])->name('admin.kost.edit');
    Route::post('/admin/kost/{id}/update', [OwnerController::class, 'update'])->name('admin.kost.update');
    Route::post('/admin/kost/{id}/status', [OwnerController::class, 'toggleStatus'])->name('admin.kost.toggle');
    Route::post('/admin/kost/{id}/hapus', [OwnerController::class, 'destroy'])->name('admin.kost.destroy');
    Route::post('/admin/kost/{id}/foto/{photo}/hapus', [OwnerController::class, 'destroyPhoto'])->name('admin.kost.photo.destroy');

    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');

    Route::get('/admin/booking', [AdminController::class, 'bookings'])->name('admin.bookings');

    Route::get('/admin/kampus', [AdminController::class, 'campuses'])->name('admin.campuses');
    Route::post('/admin/kampus', [AdminController::class, 'storeCampus'])->name('admin.campuses.store');
    Route::post('/admin/kampus/{id}/update', [AdminController::class, 'updateCampus'])->name('admin.campuses.update');
    Route::post('/admin/kampus/{id}/hapus', [AdminController::class, 'destroyCampus'])->name('admin.campuses.destroy');

    Route::get('/admin/impor', [AdminController::class, 'import'])->name('admin.import');
    Route::post('/admin/impor', [AdminController::class, 'processImport'])->name('admin.import.process');

    Route::get('/admin/statistik', [AdminController::class, 'statistics'])->name('admin.statistics');
});
