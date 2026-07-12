@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="flash danger">{{ session('error') }}</div>
@endif
