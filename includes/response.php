<?php
/**
 * CASPA School OS – JSON response helpers
 *
 * All API endpoints must use these functions and nothing else
 * for output, so the contract {"ok":bool,...} is always honoured.
 *
 * Functions
 *   json_ok($data, $status)    – {"ok":true,"data":...}
 *   json_fail($error, $status) – {"ok":false,"error":"..."}
 *   validate_input($rules)      – validate $_POST fields; calls json_fail on error
 */

declare(strict_types=1);

/**
 * Send a successful JSON response and exit.
 *
 * @param mixed $data   Payload to send under the "data" key.
 * @param int   $status HTTP status code (default 200).
 */
function json_ok(mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send an error JSON response and exit.
 *
 * @param string $error  Human-readable error message.
 * @param int    $status HTTP status code (default 422 for validation errors).
 */
function json_fail(string $error, int $status = 422): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validate POST fields against simple rules.
 * Calls json_fail(422) automatically if validation fails.
 *
 * Rules per field (pipe-separated):
 *   required   – must be present and non-empty
 *   email      – must be a valid email address
 *   int        – must be numeric
 *   min:N      – string length >= N
 *   max:N      – string length <= N
 *   in:a,b,c   – value must be one of listed options
 *
 * @param array<string, string> $rules   ['field' => 'required|email|min:6']
 * @return array<string, string>         Trimmed & validated POST values
 */
function validate_input(array $rules): array
{
    $values = [];
    $errors = [];

    foreach ($rules as $field => $ruleStr) {
        $fieldRules = explode('|', $ruleStr);
        $raw        = $_POST[$field] ?? '';
        $value      = trim((string) $raw);

        foreach ($fieldRules as $rule) {
            [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($ruleName) {
                case 'required':
                    if ($value === '') {
                        $errors[$field] = ucfirst($field) . ' is required.';
                    }
                    break;

                case 'email':
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = ucfirst($field) . ' must be a valid email address.';
                    }
                    break;

                case 'int':
                    if ($value !== '' && !ctype_digit($value)) {
                        $errors[$field] = ucfirst($field) . ' must be an integer.';
                    }
                    break;

                case 'min':
                    if ($value !== '' && mb_strlen($value) < (int) $param) {
                        $errors[$field] = ucfirst($field) . " must be at least {$param} characters.";
                    }
                    break;

                case 'max':
                    if ($value !== '' && mb_strlen($value) > (int) $param) {
                        $errors[$field] = ucfirst($field) . " must be at most {$param} characters.";
                    }
                    break;

                case 'in':
                    $allowed = explode(',', (string) $param);
                    if ($value !== '' && !in_array($value, $allowed, true)) {
                        $errors[$field] = ucfirst($field) . ' has an invalid value.';
                    }
                    break;
            }

            // Stop checking further rules for this field once an error is found
            if (isset($errors[$field])) {
                break;
            }
        }

        $values[$field] = $value;
    }

    if (!empty($errors)) {
        json_fail(implode(' ', $errors), 422);
    }

    return $values;
}
