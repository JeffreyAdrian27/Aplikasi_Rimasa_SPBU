<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isOwner()) {
            abort(403, 'Hanya Owner yang dapat mengakses pengaturan.');
        }

        return Inertia::render('Settings/Index', [
            'branches' => Branch::orderBy('id')->get(),
            'users' => User::with('branch:id,code,name')->orderBy('id')->get(['id', 'name', 'email', 'role', 'branch_id']),
        ]);
    }

    public function updateBranch(Request $request, Branch $branch): RedirectResponse
    {
        if (! $request->user()->isOwner()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'margin_percent' => ['required', 'numeric', 'min:0', 'max:50'],
        ]);

        $branch->update([
            'name' => $data['name'],
            'margin_rate' => round(((float) $data['margin_percent']) / 100, 4),
        ]);

        return back()->with('success', "Pengaturan cabang {$branch->name} berhasil diperbarui.");
    }
}
