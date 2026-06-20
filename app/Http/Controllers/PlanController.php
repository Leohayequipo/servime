<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Listado de planes + asignación a comercios (solo super admin, vía middleware de ruta).
     */
    public function index()
    {
        $plans = Plan::orderBy('orden')->get();
        $comercios = Comercio::with('plan')->orderBy('nombre')->get();

        return view('admin.plans.index', compact('plans', 'comercios'));
    }

    public function create()
    {
        return view('admin.plans.edit', ['plan' => new Plan(['activo' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['key'] = $data['key'] ?: \Illuminate\Support\Str::slug($data['nombre']);

        Plan::create($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan creado correctamente.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request, $plan);
        // La key es estable: no se edita una vez creada.
        unset($data['key']);

        $plan->update($data);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan «{$plan->nombre}» actualizado correctamente.");
    }

    public function destroy(Plan $plan)
    {
        if ($plan->comercios()->exists()) {
            return redirect()->route('admin.plans.index')
                ->with('error', 'No se puede eliminar: hay comercios usando este plan. Reasignalos primero.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan eliminado.');
    }

    /**
     * Asignar un plan a un comercio.
     */
    public function assignToComercio(Request $request, Comercio $comercio)
    {
        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
        ]);

        $comercio->update(['plan_id' => $validated['plan_id']]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan asignado a «{$comercio->nombre}».");
    }

    /**
     * Reset manual del consumo de IA de un comercio (se cobra aparte).
     */
    public function resetIaUsage(Comercio $comercio)
    {
        $comercio->resetUsoIA();

        return redirect()->route('admin.plans.index')
            ->with('success', "Uso de IA reseteado para «{$comercio->nombre}».");
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'key' => [
                'nullable', 'string', 'max:50',
                Rule::unique('plans', 'key')->ignore($plan?->id),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'mensajes_ia_mes' => ['nullable', 'integer', 'min:0'],
            'modelo_ia' => ['required', 'string', 'max:50'],
            'max_tokens_chat' => ['required', 'integer', 'min:50', 'max:4000'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(['analytics', 'excel', 'promociones'])],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $validated['features'] = $validated['features'] ?? [];
        $validated['activo'] = $request->boolean('activo');
        $validated['orden'] = $validated['orden'] ?? 0;
        // mensajes_ia_mes vacío = ilimitado (null)
        $validated['mensajes_ia_mes'] = $request->filled('mensajes_ia_mes')
            ? (int) $request->input('mensajes_ia_mes')
            : null;

        return $validated;
    }
}
