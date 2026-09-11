<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Leave;
use App\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /** Not eklenebilecek modeller (rota parametresi => sınıf). */
    private const TYPES = [
        'employees' => Employee::class,
        'expenses' => Expense::class,
        'leaves' => Leave::class,
    ];

    public function store(Request $request, string $type, int $id): JsonResponse|RedirectResponse
    {
        $model = $this->resolve($type, $id);
        $data = $request->validate(['content' => ['required', 'string', 'max:5000']]);

        $note = $model->notes()->create($data + ['user_id' => $request->user()->id]);
        $note->load('author');

        if ($request->wantsJson()) {
            return response()->json(['note' => $this->serialize($note), 'count' => $model->notes()->count()]);
        }

        return $this->redirectBack($request, $model)->with('success', 'Not eklendi.');
    }

    public function update(Request $request, Note $note): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:5000']]);
        $note->update($data);
        $note->load('author');

        if ($request->wantsJson()) {
            return response()->json(['note' => $this->serialize($note)]);
        }

        return $this->redirectBack($request, $note->notable)->with('success', 'Not güncellendi.');
    }

    public function destroy(Request $request, Note $note): JsonResponse|RedirectResponse
    {
        $model = $note->notable;
        $note->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'count' => $model?->notes()->count() ?? 0]);
        }

        return $this->redirectBack($request, $model)->with('success', 'Not silindi.');
    }

    private function serialize(Note $note): array
    {
        return [
            'id' => $note->id,
            'content' => $note->content,
            'author' => $note->author?->name ?? '—',
            'at' => $note->created_at->format('d.m.Y H:i'),
            'human' => $note->created_at->diffForHumans(),
            'edited' => $note->updated_at->gt($note->created_at->addMinute()) ? $note->updated_at->format('d.m.Y H:i') : null,
        ];
    }

    private function resolve(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::findOrFail($id);
    }

    private function redirectBack(Request $request, ?Model $model): RedirectResponse
    {
        $to = $request->input('redirect');
        if ($to && str_starts_with($to, '/')) {
            return redirect($to);
        }

        if ($model instanceof Employee) {
            return redirect()->route('employees.show', ['employee' => $model, 'tab' => 'notes']);
        }

        return back();
    }
}
