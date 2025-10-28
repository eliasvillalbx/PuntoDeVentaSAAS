<?php
// app/Http/Requests/UpdateSuscripcionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuscripcionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'empresa_id'   => ['required','integer','exists:empresas,id'],
            // 👉 usa tus ENUMs
            'plan'         => ['required','in:1_mes,6_meses,1_año,3_años'],
            'fecha_inicio' => ['required','date_format:Y-m-d'],
            'estado'       => ['required','in:activa,vencida,cancelada'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in' => 'Estado inválido.',
        ];
    }
}
