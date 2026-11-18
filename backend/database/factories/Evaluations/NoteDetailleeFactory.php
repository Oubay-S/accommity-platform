<?php

namespace Database\Factories\Evaluations;

use App\Models\Evaluations\Evaluation;
use App\Models\Evaluations\NoteDetaillee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteDetaillee>
 */
class NoteDetailleeFactory extends Factory
{
    protected $model = NoteDetaillee::class;

    public function definition(): array
    {
        return [
            'id_evaluation' => Evaluation::factory(),
            'proprete' => 5,
            'communication' => 5,
            'emplacement' => 5,
            'rapport_qualite_prix' => 5,
            'exactitude' => 5,
        ];
    }
}
