<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('vehicle') ? $this->route('vehicle')->id : null;

        $rules = [
            'brand' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
        ];

        // Reglas específicas para la creación (POST)
        if ($this->isMethod('post')) {
            $rules['residence_id'] = 'required|exists:residences,id';
            $rules['plate'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicles')->whereNull('deleted_at'),
            ];
        }

        // Reglas específicas para la actualización (PUT/PATCH)
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['plate'] = [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('vehicles')->ignore($vehicleId)->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }
}
