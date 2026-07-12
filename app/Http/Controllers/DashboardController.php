<?php

namespace App\Http\Controllers;

use App\Enums\StatoCliente;
use App\Enums\StatoRassegna;
use App\Models\Cliente;
use App\Models\Rassegna;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'clientiAttivi' => Cliente::where('stato', StatoCliente::Attivo)->count(),
            'rassegneInRaccolta' => Rassegna::where('stato', StatoRassegna::InRaccolta)->count(),
            'rassegneInRevisione' => Rassegna::where('stato', StatoRassegna::InRevisione)->count(),
        ]);
    }
}
