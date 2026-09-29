@extends('layouts.app')
@section('content')
<header class="company-header">@if($company->logo_url)<img class="logo" src="{{ $company->logo_url }}" alt="Logo de {{ $company->nombre }}">@endif<h1>{{ $company->nombre }}</h1></header>
<x-equipment-card :equipment="$equipment" />
@endsection
