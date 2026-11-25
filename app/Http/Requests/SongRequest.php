<?php

namespace App\Http\Requests;

use App\Enums\Instrument;
use App\Services\TabDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'band' => ['nullable', 'string', 'max:255'],
            // Valori ammessi: quelli dell'Enum Instrument (bass, guitar, voice), letti dall'Enum stesso.
            'instrument' => ['required', Rule::in(array_column(Instrument::cases(), 'value'))],
            'show_instrument' => ['required', 'boolean'],
            'show_band' => ['required', 'boolean'],
            // Il foglio arriva come testo JSON (lo scrive l'editor in un campo nascosto)
            // e deve contenere almeno un carattere.
            'tab' => ['required', 'json', function (string $attribute, mixed $value, Closure $fail) {
                $doc = json_decode((string) $value, true);

                if (! is_array($doc) || ! TabDocument::hasText($doc)) {
                    $fail('validation.required')->translate();
                }
            }],
        ];
    }

    /**
     * Dati validati pronti per il modello: il foglio passa da testo JSON ad array
     * (il cast 'array' del modello lo riconverte in JSON quando salva).
     *
     * @return array<string, mixed>
     */
    public function songData(): array
    {
        $data = $this->validated();
        $data['tab'] = json_decode($data['tab'], true);

        return $data;
    }
}
