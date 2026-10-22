<?php

namespace App\Actions\Learners;

use App\Models\Promotion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportLearners
{
    /**
     * Header aliases (normalized) for each expected column.
     */
    private const array COLUMNS = [
        'last_name' => ['nom', 'nomdefamille', 'lastname'],
        'first_name' => ['prenom', 'firstname'],
        'email' => ['email', 'mail', 'courriel', 'adresseemail'],
    ];

    public function __construct(private EnrollLearner $enroll) {}

    /**
     * Enroll every learner listed in a CSV file (columns: nom, prenom, email).
     *
     * @return array{created: int, enrolled: int, errors: list<array{line: int, message: string}>}
     *
     * @throws ImportException when the file itself cannot be used
     */
    public function handle(Promotion $promotion, string $contents): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $this->toUtf8($contents)) ?: [];
        $header = array_shift($lines) ?? '';
        $delimiter = $this->detectDelimiter($header);
        $columns = $this->mapColumns(str_getcsv($header, $delimiter, escape: ''));

        $report = ['created' => 0, 'enrolled' => 0, 'errors' => []];
        $hasRows = false;

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $hasRows = true;
            $lineNumber = $index + 2;
            $cells = str_getcsv($line, $delimiter, escape: '');
            $row = array_map(fn (int $position) => trim($cells[$position] ?? ''), $columns);

            $validator = Validator::make($row, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
            ], attributes: ['last_name' => 'nom']);

            if ($validator->fails()) {
                $report['errors'][] = ['line' => $lineNumber, 'message' => $validator->errors()->first()];

                continue;
            }

            try {
                $result = $this->enroll->handle($promotion, $row['first_name'], $row['last_name'], $row['email']);
                $report[$result === EnrollmentResult::Created ? 'created' : 'enrolled']++;
            } catch (EnrollmentException $e) {
                $report['errors'][] = ['line' => $lineNumber, 'message' => $e->getMessage()];
            }
        }

        if (! $hasRows) {
            throw new ImportException('Le fichier ne contient aucun apprenant.');
        }

        return $report;
    }

    /**
     * Excel saves French CSV files in Windows-1252 by default.
     */
    private function toUtf8(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        return mb_check_encoding($contents, 'UTF-8')
            ? $contents
            : mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
    }

    private function detectDelimiter(string $header): string
    {
        $counts = array_map(fn (string $delimiter) => substr_count($header, $delimiter), [';' => ';', ',' => ',', "\t" => "\t"]);
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array{first_name: int, last_name: int, email: int}
     */
    private function mapColumns(array $header): array
    {
        $normalized = array_map(
            fn (?string $name) => preg_replace('/[^a-z]/', '', Str::lower(Str::ascii((string) $name))),
            $header,
        );

        $columns = [];

        foreach (self::COLUMNS as $field => $aliases) {
            $position = array_find_key($normalized, fn (?string $name) => in_array($name, $aliases, true));

            if ($position === null) {
                throw new ImportException('Le fichier doit contenir les colonnes nom, prenom et email.');
            }

            $columns[$field] = $position;
        }

        /** @var array{first_name: int, last_name: int, email: int} $columns */
        return $columns;
    }
}
