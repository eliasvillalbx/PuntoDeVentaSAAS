<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'nombre'            => ['required','string','max:120'],
            'apellido_paterno'  => ['required','string','max:120'],
            'apellido_materno'  => ['nullable','string','max:120'],
            'telefono'          => ['nullable','string','max:20'],
            'email'             => ['required','email','max:160','unique:users,email'],
            'password'          => ['required','string','min:8','confirmed'],
            'id_empresa'        => ['required','integer','exists:empresas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required'            => 'El email es obligatorio.',
            'email.unique'              => 'Este email ya está registrado.',
            'password.required'         => 'La contraseña es obligatoria.',
            'password.confirmed'        => 'La confirmación de contraseña no coincide.',
            'id_empresa.required'       => 'Debes seleccionar una empresa.',
        ];
    }
}
