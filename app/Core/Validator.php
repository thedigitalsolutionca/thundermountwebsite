<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /**
     * @param array<string,string> $labels
     */
    public function __construct(private array $data, private array $labels = [])
    {
    }

    /**
     * Rules are pipe-separated: required|email|min:8|max:255|same:password
     */
    public function validate(array $rules): self
    {
        foreach ($rules as $field => $ruleset) {
            $value = trim((string) ($this->data[$field] ?? ''));
            $label = $this->labels[$field] ?? ucwords(str_replace(['_', '-'], ' ', $field));
            foreach (explode('|', $ruleset) as $rule) {
                if ($rule === '' || isset($this->errors[$field])) {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $label, $value, $name, $param);
            }
        }
        return $this;
    }

    private function apply(string $field, string $label, string $value, string $rule, ?string $param): void
    {
        switch ($rule) {
            case 'required':
                if ($value === '') {
                    $this->fail($field, $label . ' is required.');
                }
                break;
            case 'email':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->fail($field, 'Enter a valid email address.');
                }
                break;
            case 'min':
                if ($value !== '' && mb_strlen($value) < (int) $param) {
                    $this->fail($field, sprintf('%s must be at least %d characters.', $label, (int) $param));
                }
                break;
            case 'max':
                if (mb_strlen($value) > (int) $param) {
                    $this->fail($field, sprintf('%s may not be longer than %d characters.', $label, (int) $param));
                }
                break;
            case 'numeric':
                if ($value !== '' && !is_numeric($value)) {
                    $this->fail($field, $label . ' must be a number.');
                }
                break;
            case 'integer':
                if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->fail($field, $label . ' must be a whole number.');
                }
                break;
            case 'min_value':
                if ($value !== '' && (float) $value < (float) $param) {
                    $this->fail($field, $label . ' is too low.');
                }
                break;
            case 'max_value':
                if ($value !== '' && (float) $value > (float) $param) {
                    $this->fail($field, $label . ' is too high.');
                }
                break;
            case 'same':
                if ($value !== (string) ($this->data[$param] ?? '')) {
                    $this->fail($field, $label . ' does not match.');
                }
                break;
            case 'accepted':
                if (!in_array($value, ['1', 'on', 'yes', 'true'], true)) {
                    $this->fail($field, 'Please accept ' . strtolower($label) . '.');
                }
                break;
            case 'phone':
                if ($value !== '' && !preg_match('/^[0-9+().\-\s]{7,30}$/', $value)) {
                    $this->fail($field, 'Enter a valid phone number.');
                }
                break;
            case 'postcode':
                if ($value !== '' && !preg_match('/^[A-Za-z0-9\- ]{3,12}$/', $value)) {
                    $this->fail($field, 'Enter a valid postal code.');
                }
                break;
            case 'url':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->fail($field, 'Enter a valid URL including https://.');
                }
                break;
            case 'in':
                if ($value !== '' && !in_array($value, explode(',', (string) $param), true)) {
                    $this->fail($field, 'Choose a valid option for ' . strtolower($label) . '.');
                }
                break;
            case 'date':
                if ($value !== '' && strtotime($value) === false) {
                    $this->fail($field, 'Enter a valid date.');
                }
                break;
        }
    }

    private function fail(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }

    /**
     * Keep only validated/safe values.
     */
    public function validated(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field] = isset($this->data[$field]) ? trim((string) $this->data[$field]) : null;
        }
        return $out;
    }
}
