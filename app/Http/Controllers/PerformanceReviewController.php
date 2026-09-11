<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerformanceReviewRequest;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PerformanceReviewController extends Controller
{
    public function index(Request $request)
    {
        [$dy, $dm] = PerformanceReview::defaultPeriod();
        $year = (int) $request->get('year', $dy);
        $month = (int) $request->get('month', $dm);
        if ($month < 1 || $month > 12) {
            $month = $dm;
        }
        if ($year < 2020 || $year > 2100) {
            $year = $dy;
        }
        $employeeId = $request->integer('employee_id') ?: null;

        // Dönem tablosu: tüm aktif personel + o ay için varsa değerlendirmesi
        $periodReviews = PerformanceReview::forPeriod($year, $month)->with('reviewer')->get()->keyBy('employee_id');
        $employees = Employee::active()->orderBy('first_name')->orderBy('last_name')->get();
        $rows = $employees->map(fn ($e) => ['employee' => $e, 'review' => $periodReviews->get($e->id)]);

        $stats = [
            'total' => $employees->count(),
            'rated' => $rows->filter(fn ($r) => $r['review'])->count(),
            'avg' => $periodReviews->isNotEmpty() ? round($periodReviews->avg('score'), 1) : null,
            'low' => $periodReviews->filter(fn ($r) => $r->score < 6)->count(),
        ];

        // Seçili personelin ay ay geçmişi
        $history = $employeeId
            ? PerformanceReview::with('reviewer')->where('employee_id', $employeeId)->orderByDesc('period_year')->orderByDesc('period_month')->get()
            : collect();
        $selectedEmployee = $employeeId ? Employee::withTrashed()->find($employeeId) : null;

        // Genel sıralama (tüm dönemlerin ortalaması)
        $leaderboard = Employee::active()
            ->withAvg('performanceReviews as avg_score', 'score')
            ->withCount('performanceReviews')
            ->whereHas('performanceReviews')
            ->orderByDesc('avg_score')
            ->get();

        $allEmployees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'status']);

        return view('reviews.index', compact('rows', 'stats', 'history', 'selectedEmployee', 'leaderboard', 'allEmployees', 'year', 'month', 'employeeId'));
    }

    public function create(Request $request)
    {
        [$dy, $dm] = PerformanceReview::defaultPeriod();
        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $employeeId = $request->integer('employee_id') ?: null;
        $py = (int) $request->get('year', $dy);
        $pm = (int) $request->get('month', $dm);

        // Bu personel + dönem zaten değerlendirildiyse doğrudan düzenlemeye götür
        if ($employeeId) {
            $existing = PerformanceReview::forPeriod($py, $pm)->where('employee_id', $employeeId)->first();
            if ($existing) {
                return redirect()->route('reviews.edit', $existing)->with('success', 'Bu ay için değerlendirme zaten var, düzenleyebilirsiniz.');
            }
        }

        $review = new PerformanceReview([
            'employee_id' => $employeeId,
            'review_date' => now()->toDateString(),
            'period_year' => $py, 'period_month' => $pm,
            'attendance' => 4, 'quality' => 4, 'attitude' => 4, 'score' => 8,
        ]);

        return view('reviews.create', compact('employees', 'review'));
    }

    public function store(PerformanceReviewRequest $request): RedirectResponse
    {
        $review = PerformanceReview::create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->route('reviews.index', ['year' => $review->period_year, 'month' => $review->period_month])
            ->with('success', $review->employee->full_name.' için '.$review->period_label.' değerlendirmesi kaydedildi.');
    }

    public function edit(PerformanceReview $review)
    {
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('reviews.edit', compact('employees', 'review'));
    }

    public function update(PerformanceReviewRequest $request, PerformanceReview $review): RedirectResponse
    {
        $review->update($request->validated());

        return redirect()->route('reviews.index', ['year' => $review->period_year, 'month' => $review->period_month])
            ->with('success', 'Değerlendirme güncellendi.');
    }

    public function destroy(PerformanceReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Değerlendirme silindi.');
    }
}
