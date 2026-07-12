@extends('layouts.guest')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <h1>{{ config('app.name') }}</h1>
        <p class="lead">Accesso riservato al team dell'agenzia.</p>

        @if ($errors->any())
            <div class="flash danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label class="field" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="{{ $errors->has('email') ? 'invalid' : '' }}" required autofocus>

            <label class="field" for="password">Password</label>
            <input type="password" id="password" name="password"
                   class="{{ $errors->has('password') ? 'invalid' : '' }}" required>

            <label class="field" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="ricordami" style="width:auto;margin:0;"> Ricordami
            </label>

            <button type="submit" class="btn primary wide">Entra</button>
        </form>
    </div>
</div>
@endsection
