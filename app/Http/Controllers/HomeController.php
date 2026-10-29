<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $numero = preg_replace('/\D/', '', (string) config('huellita.whatsapp'));
        $whatsapp = strlen($numero) >= 8 && strlen($numero) <= 15 ? 'https://wa.me/'.$numero.'?text='.rawurlencode('Hola, buenas tardes. Vi la página de Huellita y quisiera consultar sobre la atención para mi mascota.') : null;

        return view('huellita.home', compact('whatsapp'));
    }
}
