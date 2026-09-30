<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user instanceof Usuario) {
            return $this->redirectForRole($user);
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['nombre' => ['required', 'string'], 'contrasena' => ['required', 'string']]);
        if (! Auth::attempt(['nombre' => $credentials['nombre'], 'password' => $credentials['contrasena']])) {
            return back()->withErrors(['nombre' => 'Usuario o contraseña incorrectos.'])->onlyInput('nombre');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user instanceof Usuario && ($user->isSuperadmin() || ($user->hasRole(Role::Admin) && $user->id_empresa !== null))) {
            return $this->redirectForRole($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors(['nombre' => 'La cuenta no tiene un rol o empresa asignados.'])->onlyInput('nombre');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin');
    }

    private function redirectForRole(Usuario $user): RedirectResponse
    {
        if ($user->isSuperadmin()) {
            return redirect()->route('admin.panel');
        }

        if ($user->hasRole(Role::Admin) && $user->id_empresa !== null) {
            return redirect()->route('company.settings', $user->id_empresa);
        }

        Auth::logout();

        return redirect()->route('login')->withErrors(['nombre' => 'La cuenta no tiene un rol o empresa asignados.']);
    }
}
