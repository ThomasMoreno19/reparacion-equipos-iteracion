<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function create() { return view('admin.login'); }
    public function store(Request $request)
    { $credentials = $request->validate(['nombre' => ['required', 'string'], 'contrasena' => ['required', 'string']]); if (!Auth::attempt(['nombre' => $credentials['nombre'], 'password' => $credentials['contrasena']])) return back()->withErrors(['nombre' => 'Usuario o contraseña incorrectos.'])->onlyInput('nombre'); $request->session()->regenerate(); return redirect()->intended('/admin/panel'); }
    public function destroy(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/admin'); }
}
