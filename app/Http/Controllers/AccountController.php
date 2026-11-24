<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('accounts.index', ['accounts' => User::query()->orderBy('nombre')->orderBy('id_usuario')->paginate(15)]);
    }

    public function create(): View
    {
        return view('accounts.form', ['account' => new User]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $account = new User($request->safe()->only(['nombre', 'email', 'telefono']));
        $account->password = Hash::make($request->validated('password'));
        $account->rol = $request->validated('rol');

        try {
            $account->save();
        } catch (UniqueConstraintViolationException) {
            $this->reportDuplicate($account);
        }

        Log::info('mototaxi.account.created', ['actor_id' => $request->user()->getKey(), 'account_id' => $account->getKey()]);

        return redirect()->route('accounts.index')->with('status', 'Cuenta creada.');
    }

    public function edit(User $account): View
    {
        return view('accounts.form', ['account' => $account]);
    }

    public function update(AccountRequest $request, User $account): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $account) {
                $account->refresh();
                Gate::authorize('update', $account);
                $account->fill($request->validated());
                if ($account->isDirty('email')) {
                    $account->email_verified_at = null;
                    $account->revokeAccess();
                } else {
                    $account->save();
                }
            });
        } catch (UniqueConstraintViolationException) {
            $this->reportDuplicate($account);
        }

        Log::info('mototaxi.account.updated', ['actor_id' => $request->user()->getKey(), 'account_id' => $account->getKey()]);

        return redirect()->route('accounts.index')->with('status', 'Cuenta actualizada.');
    }

    public function deactivate(User $account): RedirectResponse
    {
        return $this->setActive($account, false);
    }

    public function activate(User $account): RedirectResponse
    {
        return $this->setActive($account, true);
    }

    private function setActive(User $account, bool $active): RedirectResponse
    {
        $changed = DB::transaction(function () use ($account, $active): bool {
            $current = User::query()->lockForUpdate()->findOrFail($account->getKey());
            Gate::authorize('update', $current);
            if ($current->isActive() === $active) {
                return false;
            }

            $current->activo = $active;
            $current->revokeAccess();

            return true;
        });

        if ($changed) {
            Log::info('mototaxi.account.access_changed', ['account_id' => $account->getKey(), 'actor_id' => request()->user()->getKey(), 'active' => $active]);
        }

        return redirect()->route('accounts.index')->with('status', $active ? 'Cuenta activa.' : 'Cuenta desactivada.');
    }

    private function reportDuplicate(User $account): never
    {
        $accounts = User::query()->whereKeyNot($account->getKey());
        $field = $account->email !== null && $accounts->where('email', $account->email)->exists() ? 'email' : 'telefono';
        throw ValidationException::withMessages([$field => 'Ya existe una cuenta con este dato.']);
    }
}
