<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * "Mi plan": qué tiene contratado el comercio y qué le daría el siguiente.
 *
 * Hasta ahora los planes se veían en la landing —que el cliente ya no visita— y en
 * /admin/plans, que es del super admin. El comercio no tenía dónde enterarse de que
 * existe algo más que lo suyo, y el único cartel que lo decía estaba dentro de Analytics,
 * justo la sección que un Básico no tiene.
 *
 * Los planes salen de la base con el mismo filtro que la sección de precios de la landing
 * (activos y `orden > 0`, así el plan de demo queda afuera): nada escrito a mano acá.
 */
class MiPlanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // El super admin administra los planes, no los contrata.
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.plans.index');
        }

        $comercios = $user->comercios()->with('plan')->get();

        return view('mi-plan', [
            'comercios' => $comercios,
            'planes' => Plan::where('activo', true)->where('orden', '>', 0)->orderBy('orden')->get(),
            // Los que ya tiene en alguno de sus comercios: esos no se ofrecen.
            'planesEnUso' => $comercios->map(fn ($c) => $c->planEfectivo()?->id)->filter()->unique(),
            'soporteWhatsapp' => config('services.support.whatsapp'),
            'soporteEmail' => config('services.support.email'),
        ]);
    }
}
