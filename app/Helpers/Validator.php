<?php

declare(strict_types=1);

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $fieldRules) as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'nullable' && ($value === null || $value === '')) {
                    break;
                }

                $failed = match ($name) {
                    'required' => $value === null || (is_string($value) && trim($value) === ''),
                    'email' => $value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false,
                    'min' => is_string($value) && mb_strlen(trim($value)) < (int) $argument,
                    'max' => is_string($value) && mb_strlen($value) > (int) $argument,
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) === false,
                    'array' => !is_array($value),
                    'in' => !in_array((string) $value, explode(',', (string) $argument), true),
                    default => false,
                };

                if ($failed) {
                    $this->errors[$field][] = $this->message($field, $name, $argument);
                }
            }
        }
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function message(string $field, string $rule, ?string $argument): string
    {
        $label = ucfirst(str_replace('_', ' ', $field));
        return match ($rule) {
            'required' => $label . ' is required.',
            'email' => 'Enter a valid email address.',
            'min' => $label . ' must contain at least ' . $argument . ' characters.',
            'max' => $label . ' may not exceed ' . $argument . ' characters.',
            'integer' => $label . ' must be a whole number.',
            'array' => $label . ' must be a list.',
            'in' => 'Select a valid ' . strtolower($label) . '.',
            default => $label . ' is invalid.',
        };
    }
}
