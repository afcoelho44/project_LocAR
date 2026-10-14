<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a checagem de dono é feita no controller
    }

    public function rules(): array
    {
        return [
            'description'           => 'required|string',
            'clue_text'             => 'nullable|string|max:255',
            'position'              => 'required|integer|min:1',
            'lat'                   => 'required|numeric|between:-90,90',
            'lng'                   => 'required|numeric|between:-180,180',
            'radius'                => 'required|integer|min:5|max:500',
            'object_3d_id'          => 'nullable|exists:objects_3d,id',
            'answers'               => 'required|array|size:4',
            'answers.0.description' => 'required|string',
            'answers.1.description' => 'required|string',
            'answers.2.description' => 'nullable|string',
            'answers.3.description' => 'nullable|string',
            'correct_answer'        => 'required|integer|in:0,1,2,3',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Clique no mapa para escolher o ponto da pergunta.',
            'lng.required' => 'Clique no mapa para escolher o ponto da pergunta.',
            'answers.0.description.required' => 'A alternativa A é obrigatória.',
            'answers.1.description.required' => 'A alternativa B é obrigatória.',
            'correct_answer.required' => 'Marque qual alternativa é a correta.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $i = $this->input('correct_answer');

            if ($i !== null && blank($this->input("answers.$i.description"))) {
                $v->errors()->add('correct_answer', 'A alternativa correta não pode estar vazia.');
            }
        });
    }
}