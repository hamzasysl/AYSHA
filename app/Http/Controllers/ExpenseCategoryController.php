<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        ExpenseCategory::create($data + [
            'slug' => ExpenseCategory::makeSlug($data['label']),
            'sort' => (int) (ExpenseCategory::max('sort') ?? 0) + 10,
        ]);

        return redirect()->route('settings.edit', ['tab' => 'categories'])->with('success', $data['label'].' kategorisi eklendi.');
    }

    public function update(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $category->update($data);

        return redirect()->route('settings.edit', ['tab' => 'categories'])->with('success', $category->label.' güncellendi.');
    }

    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        abort_if($category->is_system, 422, 'Bu kategori sistem kategorisidir, silinemez.');
        $used = Expense::where('category', $category->slug)->count();
        if ($used > 0) {
            return redirect()->route('settings.edit', ['tab' => 'categories'])->with('error', $category->label.' kategorisinde '.$used.' kayıt var; önce o kayıtları başka kategoriye taşıyın veya silin.');
        }
        $category->delete();

        return redirect()->route('settings.edit', ['tab' => 'categories'])->with('success', $category->label.' silindi.');
    }

    private function validated(Request $request, ?ExpenseCategory $ignore = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:60', Rule::unique('expense_categories', 'label')->ignore($ignore?->id)],
            'icon' => ['required', Rule::in(ExpenseCategory::ICONS)],
            'color' => ['required', Rule::in(array_keys(ExpenseCategory::COLORS))],
            'employee_based' => ['boolean'],
        ], [], ['label' => 'kategori adı', 'icon' => 'ikon', 'color' => 'renk']) + ['employee_based' => $request->boolean('employee_based')];
    }
}
