@extends('layouts.app')
@section('content')
<h1>Administración</h1>
<form class="form" method="POST" action="{{ route('login.store') }}">@csrf<label>Usuario<input name="nombre" value="{{ old('nombre') }}" required></label><label>Contraseña<input type="password" name="contrasena" required></label><button class="primary">Iniciar sesión</button></form>
@if($errors->any())<div class="message">{{ $errors->first() }}</div>@endif
@endsection
