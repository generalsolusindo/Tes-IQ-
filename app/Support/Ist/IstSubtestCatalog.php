<?php

namespace App\Support\Ist;

final class IstSubtestCatalog
{
    public const EXPECTED_SUBTEST_COUNT = 9;

    public const EXPECTED_QUESTION_COUNT = 176;

    public const EXPECTED_CORE_DURATION_SECONDS = 4320;

    public static function all(): array
    {
        return [
            [
                'code' => 'SE',
                'name' => 'Satzerganzung (Melengkapi Kalimat)',
                'sequence' => 1,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 360,
                'memorization_seconds' => 0,
                'answering_seconds' => 360,
            ],
            [
                'code' => 'WA',
                'name' => 'Wortauswahl (Memilih Kata)',
                'sequence' => 2,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 360,
                'memorization_seconds' => 0,
                'answering_seconds' => 360,
            ],
            [
                'code' => 'AN',
                'name' => 'Analogien (Analogi)',
                'sequence' => 3,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 420,
                'memorization_seconds' => 0,
                'answering_seconds' => 420,
            ],
            [
                'code' => 'GE',
                'name' => 'Gemeinsamkeiten (Persamaan)',
                'sequence' => 4,
                'question_count' => 16,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
                'duration_seconds' => 480,
                'memorization_seconds' => 0,
                'answering_seconds' => 480,
            ],
            [
                'code' => 'RA',
                'name' => 'Rechenaufgaben (Berhitung)',
                'sequence' => 5,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::NUMERIC,
                'duration_seconds' => 600,
                'memorization_seconds' => 0,
                'answering_seconds' => 600,
            ],
            [
                'code' => 'ZR',
                'name' => 'Zahlenreihen (Deret Angka)',
                'sequence' => 6,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::NUMERIC,
                'duration_seconds' => 600,
                'memorization_seconds' => 0,
                'answering_seconds' => 600,
            ],
            [
                'code' => 'FA',
                'name' => 'Figurenauswahl (Memilih Bentuk)',
                'sequence' => 7,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::IMAGE_CHOICE,
                'duration_seconds' => 420,
                'memorization_seconds' => 0,
                'answering_seconds' => 420,
            ],
            [
                'code' => 'WU',
                'name' => 'Wurfelaufgaben (Latihan Kubus)',
                'sequence' => 8,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::IMAGE_CHOICE,
                'duration_seconds' => 540,
                'memorization_seconds' => 0,
                'answering_seconds' => 540,
            ],
            [
                'code' => 'ME',
                'name' => 'Merkaufgaben (Latihan Mengingat)',
                'sequence' => 9,
                'question_count' => 20,
                'default_answer_type' => IstAnswerType::SINGLE_CHOICE,
                'duration_seconds' => 540,
                'memorization_seconds' => 180,
                'answering_seconds' => 360,
            ],
        ];
    }
}
