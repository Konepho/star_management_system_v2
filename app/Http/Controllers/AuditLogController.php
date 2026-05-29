<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $accessibleCategories = $request->user()?->accessibleAuditLogCategories() ?? collect();
        abort_if($accessibleCategories->isEmpty(), 403);

        $search = trim((string) $request->string('search')->toString());
        $category = trim((string) $request->string('category')->toString());
        $module = trim((string) $request->string('module')->toString());

        $query = AuditLog::query()
            ->with(['user'])
            ->whereIn('category', $accessibleCategories->all())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($category !== '' && $accessibleCategories->contains($category)) {
            $query->where('category', $category);
        }

        if ($module !== '') {
            $query->where('module', $module);
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('summary', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('module', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('username', 'like', '%' . $search . '%');
                    });
            });
        }

        return view('audit-logs.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => [
                'search' => $search,
                'category' => $accessibleCategories->contains($category) ? $category : '',
                'module' => $module,
            ],
            'categories' => $accessibleCategories,
            'modules' => AuditLog::query()
                ->whereIn('category', $accessibleCategories->all())
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
        ]);
    }
}
