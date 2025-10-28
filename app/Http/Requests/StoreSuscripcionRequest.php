<?php
// app/Http/Requests/StoreSuscripcionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Suscripcion;

class StoreSuscripcionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required','integer','exists:empresas,id'],
            'plan'       => ['required', Rule::in(Suscripcion::PLANES)],
            'fecha_inicio' => ['nullable','date'], // si no la envías, se usa now()
        ];
    }
}
