<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="app">
        @include('partials.topbar')
        @include('partials.flash')
        {{ $slot }}
    </div>

    <div id="toasts" style="position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:360px;"></div>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notifica', (e) => {
                const d = Array.isArray(e) ? e[0] : e;
                const el = document.createElement('div');
                el.className = 'flash ' + (d.tipo === 'error' ? 'danger' : 'success');
                el.style.boxShadow = '0 6px 18px rgba(0,0,0,.14)';
                el.textContent = d.messaggio;
                document.getElementById('toasts').appendChild(el);
                setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 4200);
            });
        });
    </script>
    @livewireScripts
</body>
</html>
