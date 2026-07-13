<div class="topbar">
    <div class="brand"><a href="{{ route('dashboard') }}">{{ config('app.name') }}</a></div>
    <nav>
        <a href="{{ route('clienti.index') }}" class="{{ request()->routeIs('clienti.*') ? 'active' : '' }}">Clienti</a>
        <a href="{{ route('rassegne.index') }}" class="{{ request()->routeIs('rassegne.*') ? 'active' : '' }}">Rassegne</a>
        <a href="{{ route('archivio.index') }}" class="{{ request()->routeIs('archivio.*') ? 'active' : '' }}">Archivio</a>
        <a href="{{ route('statistiche.index') }}" class="{{ request()->routeIs('statistiche.*') ? 'active' : '' }}">Statistiche</a>
        <a href="{{ route('log.index') }}" class="{{ request()->routeIs('log.*') ? 'active' : '' }}">Log</a>
        @if (auth()->user()->isSupervisore())
            <a href="{{ route('cestino.index') }}" class="{{ request()->routeIs('cestino.*') ? 'active' : '' }}">Cestino</a>
        @endif
    </nav>
    <div class="user">
        <span>{{ auth()->user()->name }} · {{ auth()->user()->ruolo->etichetta() }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn small">Esci</button>
        </form>
    </div>
</div>
