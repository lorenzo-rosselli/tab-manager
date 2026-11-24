<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SongRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * L'app ha un solo utente e nessun login: chiunque usi l'app può salvare.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Usate sia per creare (store) sia per modificare (update) un brano.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['required', 'string', 'max:255'],
            'instrument' => ['required', 'in:bass,guitar'],
            'tuning' => ['required', 'string', 'max:50'],
            'tab' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
