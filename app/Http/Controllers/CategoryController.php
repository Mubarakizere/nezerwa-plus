<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CategoryController extends Controller
{
    /** Build a consistent log context for this request */
    private function ctx(Request $request, array $extra = []): array
    {
        return array_merge([
            'rid'      => $request->attributes->get('rid') ?? $request->attributes->set('rid', (string) Str::uuid()) ?: $request->attributes->get('rid'),
            'route'    => optional($request->route())->getName(),
            'method'   => $request->method(),
            'path'     => $request->path(),
            'ip'       => $request->ip(),
            'user_id'  => optional($request->user())->id,
            'input'    => Arr::except($request->all(), ['_token', '_method']),
        ], $extra);
    }

    public function index(Request $request)
{
    $filterKind = $request->query('kind');      // product|expense|both|inactive|trash
    $term = trim((string) $request->query('q', ''));
    $perPageParam = $request->query('per_page');
    $op = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

    Log::info('Categories.index', $this->ctx($request, compact('filterKind','term','perPageParam','op')));

    // Base query: normal or trash
    $base = Category::query();
    if ($filterKind === 'trash') {
        $base = Category::onlyTrashed();
    }

    $query = $base->with(['parent:id,name'])
                  ->withCount(['products','expenses'])
                  ->ordered();

    // Kind / active filters (skip when viewing trash except `inactive` which doesn’t apply)
    if ($filterKind === 'inactive') {
        $query->where('is_active', false);
    } elseif (in_array($filterKind, ['product','expense','both'], true)) {
        $query->where('kind', $filterKind)->where('is_active', true);
    }

    // Case-insensitive search
    if ($term !== '') {
        $pattern = "%{$term}%";
        $query->where(function ($w) use ($op, $pattern, $term) {
            $w->where('name', $op, $pattern)
              ->orWhere('code', $op, $pattern)
              ->orWhere('description', $op, $pattern)
              ->orWhereHas('parent', fn($p) => $p->where('name', $op, $pattern));
            if (is_numeric($term)) $w->orWhere('id', (int) $term);
        });
    }

    // Pagination control
    if ($perPageParam === 'all') {
        $categories = $query->get();
    } else {
        $perPage = (int) ($perPageParam ?? 12);
        $perPage = max(5, min($perPage, 500));
        $categories = $query->paginate($perPage)->appends($request->query());
    }

    Log::debug('Categories.index.result', $this->ctx($request, [
        'returned' => $categories instanceof \Illuminate\Pagination\LengthAwarePaginator ? $categories->count() : count($categories),
    ]));

    return view('categories.index', compact('categories','filterKind'));
}

/** Restore a soft-deleted category */
public function restore(Request $request, $id)
{
    $ctx = $this->ctx($request, ['category_id' => $id]);
    try {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        Log::info('Categories.restore.success', $ctx);

        // Stay on trash tab after restore
        return redirect()
            ->route('categories.index', array_merge($request->except('page'), ['kind' => 'trash']))
            ->with('success', "♻️ Category restored. (ref: {$ctx['rid']})");

    } catch (Throwable $e) {
        Log::error('Categories.restore.error', array_merge($ctx, ['message' => $e->getMessage()]));
        return back()->with('error', "Restore failed. Ref: {$ctx['rid']}");
    }
}

/** Permanently delete a soft-deleted category */
public function forceDestroy(Request $request, $id)
{
    $ctx = $this->ctx($request, ['category_id' => $id]);
    try {
        $category = Category::onlyTrashed()->withCount(['products','expenses'])->findOrFail($id);

        // Safety: block if still referenced (avoid FK errors / orphans)
        if (($category->products_count ?? 0) > 0 || ($category->expenses_count ?? 0) > 0) {
            Log::warning('Categories.forceDestroy.blocked_in_use', array_merge($ctx, [
                'products_count' => $category->products_count,
                'expenses_count' => $category->expenses_count,
            ]));
            return back()->with('error', "Cannot delete forever: category is in use. Ref: {$ctx['rid']}");
        }

        $category->forceDelete();

        Log::info('Categories.forceDestroy.success', $ctx);

        return redirect()
            ->route('categories.index', array_merge($request->except('page'), ['kind' => 'trash']))
            ->with('success', "🧨 Category permanently deleted. (ref: {$ctx['rid']})");

    } catch (Throwable $e) {
        Log::error('Categories.forceDestroy.error', array_merge($ctx, ['message' => $e->getMessage()]));
        return back()->with('error', "Permanent delete failed. Ref: {$ctx['rid']}");
    }
}

    public function create(Request $request)
    {
        Log::info('Categories.create', $this->ctx($request));
        $parents = Category::ordered()->get(['id','name','kind']);
        return view('categories.create', compact('parents'));
    }

    public function store(CategoryStoreRequest $request)
    {
        $ctx = $this->ctx($request);
        Log::info('Categories.store.validating', $ctx);

        try {
            $data = $request->validated();
            $data['is_active']  = $request->boolean('is_active', true);
            $data['sort_order'] = $data['sort_order'] ?? 0;

            Log::debug('Categories.store.validated', array_merge($ctx, ['validated' => $data]));

            $category = Category::create($data);

            Log::info('Categories.store.success', array_merge($ctx, ['category_id' => $category->id]));

            return redirect()
                ->route('categories.index')
                ->with('success', " Category created. (ref: {$ctx['rid']})");

        } catch (QueryException $e) {
            Log::error('Categories.store.db_error', array_merge($ctx, [
                'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
            ]));
            return back()->withInput()->with('error', "DB error while creating category. Ref: {$ctx['rid']}");
        } catch (Throwable $e) {
            Log::error('Categories.store.error', array_merge($ctx, [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]));
            return back()->withInput()->with('error', "Unexpected error. Ref: {$ctx['rid']}");
        }
    }

    public function edit(Request $request, Category $category)
    {
        Log::info('Categories.edit', $this->ctx($request, ['category_id' => $category->id]));
        $parents = Category::where('id','!=',$category->id)->ordered()->get(['id','name','kind']);
        return view('categories.edit', compact('category','parents'));
    }

    public function update(CategoryUpdateRequest $request, Category $category)
    {
        $ctx = $this->ctx($request, ['category_id' => $category->id]);
        Log::info('Categories.update.validating', $ctx);

        try {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', false);

            Log::debug('Categories.update.validated', array_merge($ctx, ['validated' => $data]));

            $category->update($data);

            Log::info('Categories.update.success', $ctx);

            return redirect()
                ->route('categories.index')
                ->with('success', " Category updated. (ref: {$ctx['rid']})");

        } catch (QueryException $e) {
            Log::error('Categories.update.db_error', array_merge($ctx, [
                'sql'       => method_exists($e, 'getSql') ? $e->getSql() : null,
                'bindings'  => method_exists($e, 'getBindings') ? $e->getBindings() : null,
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
            ]));
            return back()->withInput()->with('error', "DB error while updating category. Ref: {$ctx['rid']}");
        } catch (Throwable $e) {
            Log::error('Categories.update.error', array_merge($ctx, [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]));
            return back()->withInput()->with('error', "Unexpected error. Ref: {$ctx['rid']}");
        }
    }

    public function destroy(Request $request, Category $category)
    {
        $ctx = $this->ctx($request, ['category_id' => $category->id]);
        Log::info('Categories.destroy.start', $ctx);

        try {
            $category->delete();

            Log::info('Categories.destroy.success', $ctx);

            return redirect()
                ->route('categories.index')
                ->with('success', " Category deleted. (ref: {$ctx['rid']})");

        } catch (Throwable $e) {
            Log::error('Categories.destroy.error', array_merge($ctx, [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]));
            return back()->with('error', "Delete failed. Ref: {$ctx['rid']}");
        }
    }
}
