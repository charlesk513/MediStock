<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Request expired or invalid. Go back, refresh the page, and try again.');
    }
}

function post_string(string $key, int $maxLength = 255, bool $required = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Invalid input format.');
    }
    $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
    if ($required && $value === '') {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' is required.');
    }
    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . " is too long (maximum {$maxLength} characters).");
    }
    return $value;
}

function post_int(string $key, int $min = 0, int $max = 2147483647): int
{
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' must be a valid number.');
    }
    return $value;
}

function post_decimal(string $key, float $min = 0, float $max = 9999999999): string
{
    $value = $_POST[$key] ?? null;
    if (!is_string($value) && !is_numeric($value)) {
        throw new InvalidArgumentException('Invalid amount.');
    }
    $value = trim((string)$value);
    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value)) {
        throw new InvalidArgumentException('Enter a valid amount with up to two decimal places.');
    }
    $number = (float)$value;
    if ($number < $min || $number > $max) {
        throw new InvalidArgumentException('Amount is outside the allowed range.');
    }
    return number_format($number, 2, '.', '');
}

function valid_date(string $key, bool $futureOnly = false): string
{
    $value = post_string($key, 10);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Enter a valid date.');
    }
    if ($futureOnly && $date <= new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Expiry date must be in the future.');
    }
    return $value;
}

function run_action(callable $callback, string $success, string $fallback = 'The operation could not be completed.'): void
{
    try {
        $callback();
        flash('success', $success);
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        error_log('Database operation failed [' . ($e->errorInfo[0] ?? 'unknown') . ']: ' . $e->getMessage());
        $sqlState = $e->errorInfo[0] ?? '';
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        if ($sqlState === '45000') {
            flash('error', $e->errorInfo[2] ?? 'The database rejected this operation because it violates a business rule.');
        } elseif ($driverCode === 1062) {
            flash('error', 'A record with the same unique value already exists.');
        } elseif ($driverCode === 1452) {
            flash('error', 'The selected related record does not exist. Refresh the page and select a valid record.');
        } elseif ($driverCode === 1451) {
            flash('error', 'This record is referenced by other records and cannot be deleted.');
        } else {
            flash('error', $fallback . ' Check the entered values and try again.');
        }
    } catch (Throwable $e) {
        error_log('Unexpected application error: ' . $e->getMessage());
        flash('error', $fallback . ' An unexpected error was logged.');
    }
}
