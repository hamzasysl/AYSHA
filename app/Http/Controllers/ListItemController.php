<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\ListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListItemController extends Controller
{
    public function store(Request $request, string $type): RedirectResponse
    {
        abort_unless(isset(ListItem::TYPES[$type]), 404);
        $data = $this->validated($request, $type);

        ListItem::create([
            'type' => $type,
            'slug' => ListItem::makeSlug($type, $data['label']),
            'label' => $data['label'],
            'meta' => $type === 'leave_type' ? ['deduct' => $request->boolean('deduct'), 'icon' => $data['icon'] ?? 'fa-calendar'] : null,
            'sort' => (int) (ListItem::where('type', $type)->max('sort') ?? 0) + 10,
        ]);

        return redirect()->route('settings.edit', ['tab' => 'lists', 'list' => $type])->with('success', $data['label'].' eklendi.');
    }

    public function update(Request $request, ListItem $item): RedirectResponse
    {
        $data = $this->validated($request, $item->type, $item);
        $old = $item->label;

        $item->label = $data['label'];
        if ($item->type === 'leave_type') {
            $item->meta = ['deduct' => $request->boolean('deduct'), 'icon' => $data['icon'] ?? ($item->meta['icon'] ?? 'fa-calendar')];
        } else {
            // Görev / banka: etiket değişince personel kartlarındaki değer de güncellensin
            $item->slug = $data['label'];
            $column = $item->type === 'position' ? 'position' : 'bank_name';
            Employee::withTrashed()->where($column, $old)->update([$column => $data['label']]);
        }
        $item->save();

        return redirect()->route('settings.edit', ['tab' => 'lists', 'list' => $item->type])->with('success', $data['label'].' güncellendi.');
    }

    public function destroy(ListItem $item): RedirectResponse
    {
        abort_if($item->is_system, 422, 'Bu öğe sistem öğesidir, silinemez.');

        $used = match ($item->type) {
            'position' => Employee::withTrashed()->where('position', $item->label)->count(),
            'bank' => Employee::withTrashed()->where('bank_name', $item->label)->count(),
            'leave_type' => Leave::where('type', $item->slug)->count(),
        };
        if ($used > 0) {
            return redirect()->route('settings.edit', ['tab' => 'lists', 'list' => $item->type])->with('error', $item->label.' '.$used.' kayıtta kullanılıyor; silinemez.');
        }
        $item->delete();

        return redirect()->route('settings.edit', ['tab' => 'lists', 'list' => $item->type])->with('success', $item->label.' silindi.');
    }

    private function validated(Request $request, string $type, ?ListItem $ignore = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:80', Rule::unique('list_items', 'label')->where('type', $type)->ignore($ignore?->id)],
            'icon' => [$type === 'leave_type' ? 'required' : 'nullable', 'string', 'max:40', 'regex:/^fa-[a-z0-9-]+$/'],
            'deduct' => ['nullable', 'boolean'],
        ], ['label.unique' => 'Bu ad zaten listede var.'], ['label' => 'ad', 'icon' => 'ikon']);
    }
}
