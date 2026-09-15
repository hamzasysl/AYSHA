<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ListItemController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryPaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Adresler Türkçe; rota adları (route('employees.index') vb.) İngilizce kalır.
// Yetki: can:edit (Tam yetkili, Patron, Düzenleyici) · can:delete / can:manage (Tam yetkili, Patron) · Çalışan sadece GET

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/giris', [LoginController::class, 'create'])->name('login');
    Route::post('/giris', [LoginController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/cikis', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    // ---- Herkes: görüntüleme
    Route::get('/genel-bakis', DashboardController::class)->name('dashboard');
    Route::resource('personel', EmployeeController::class)->names('employees')->parameters(['personel' => 'employee'])->only(['index', 'show'])->where(['employee' => '[0-9]+']);
    Route::get('/maas', [SalaryPaymentController::class, 'index'])->name('payments.index');
    Route::get('/maas/banka-dosyasi', [SalaryPaymentController::class, 'bankFile'])->name('payments.bank-file');
    Route::get('/muhasebe', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::resource('performans', PerformanceReviewController::class)->names('reviews')->parameters(['performans' => 'review'])->only(['index']);
    Route::resource('izinler', LeaveController::class)->names('leaves')->parameters(['izinler' => 'leave'])->only(['index']);
    Route::get('/izinler/{leave}/yazdir', [LeaveController::class, 'print'])->name('leaves.print');
    Route::get('/raporlar', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/ayarlar', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/ayarlar/hesap', [SettingController::class, 'account'])->name('settings.account');

    // ---- Ekleme / düzenleme
    Route::middleware('can:edit')->group(function () {
        Route::resource('personel', EmployeeController::class)->names('employees')->parameters(['personel' => 'employee'])->only(['create', 'store', 'edit', 'update']);

        Route::post('/notlar/{type}/{id}', [NoteController::class, 'store'])->name('notes.store')->whereIn('type', ['employees', 'expenses', 'leaves'])->whereNumber('id');
        Route::patch('/notlar/{note}', [NoteController::class, 'update'])->name('notes.update');

        Route::post('/maas/olustur', [SalaryPaymentController::class, 'generate'])->name('payments.generate');
        Route::post('/maas/tumunu-ode', [SalaryPaymentController::class, 'markAllPaid'])->name('payments.mark-all-paid');
        Route::patch('/maas/{payment}', [SalaryPaymentController::class, 'update'])->name('payments.update');
        Route::post('/maas/{payment}/odendi', [SalaryPaymentController::class, 'markPaid'])->name('payments.mark-paid');
        Route::post('/maas/{payment}/durum', [SalaryPaymentController::class, 'setStatus'])->name('payments.status');
        Route::post('/maas/{payment}/iade', [SalaryPaymentController::class, 'refundReceived'])->name('payments.refund');

        Route::post('/muhasebe', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('/muhasebe/toplu', [ExpenseController::class, 'bulkStore'])->name('expenses.bulk');
        Route::post('/muhasebe/yemek-yol-olustur', [ExpenseController::class, 'generateAllowances'])->name('expenses.generate-allowances');
        Route::patch('/muhasebe/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::post('/muhasebe/{expense}/odendi', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');
        Route::post('/muhasebe/{expense}/durum', [ExpenseController::class, 'setStatus'])->name('expenses.status');
        Route::post('/muhasebe/{expense}/iade', [ExpenseController::class, 'refundReceived'])->name('expenses.refund');

        Route::resource('performans', PerformanceReviewController::class)->names('reviews')->parameters(['performans' => 'review'])->only(['create', 'store', 'edit', 'update']);
        Route::resource('izinler', LeaveController::class)->names('leaves')->parameters(['izinler' => 'leave'])->only(['create', 'store', 'edit', 'update']);
    });

    // ---- Silme
    Route::middleware('can:delete')->group(function () {
        Route::resource('personel', EmployeeController::class)->names('employees')->parameters(['personel' => 'employee'])->only(['destroy']);
        Route::delete('/notlar/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        Route::delete('/maas/{payment}', [SalaryPaymentController::class, 'destroy'])->name('payments.destroy');
        Route::delete('/muhasebe/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::resource('performans', PerformanceReviewController::class)->names('reviews')->parameters(['performans' => 'review'])->only(['destroy']);
        Route::resource('izinler', LeaveController::class)->names('leaves')->parameters(['izinler' => 'leave'])->only(['destroy']);
    });

    // ---- Ayarlar ve kullanıcılar
    Route::middleware('can:manage')->group(function () {
        Route::put('/ayarlar', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/ayarlar/kullanicilar', [UserController::class, 'store'])->name('users.store');
        Route::put('/ayarlar/kullanicilar/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/ayarlar/kullanicilar/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/ayarlar/kategoriler', [ExpenseCategoryController::class, 'store'])->name('categories.store');
        Route::put('/ayarlar/kategoriler/{category}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/ayarlar/kategoriler/{category}', [ExpenseCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/ayarlar/listeler/{type}', [ListItemController::class, 'store'])->name('lists.store')->whereIn('type', ['position', 'bank', 'leave_type']);
        Route::put('/ayarlar/listeler/{item}', [ListItemController::class, 'update'])->name('lists.update');
        Route::delete('/ayarlar/listeler/{item}', [ListItemController::class, 'destroy'])->name('lists.destroy');
        Route::post('/ayarlar/sistem/guncelle', [SystemController::class, 'migrate'])->name('system.migrate');
        Route::post('/ayarlar/sistem/onbellek', [SystemController::class, 'clearCache'])->name('system.cache');
    });
});
