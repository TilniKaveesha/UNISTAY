<?php
declare(strict_types=1);
require_once __DIR__ . '/unistay_module.php';

/** Parse a non-negative DECIMAL(10,2) amount as integer cents, without floating-point rounding. */
function rent_parse_cents(mixed $value): int
{
    $s = trim((string)$value);
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $s)) {
        throw new InvalidArgumentException('Enter an amount between 0 and 99999999.99, with up to 2 decimals.');
    }
    [$whole, $fraction] = array_pad(explode('.', $s, 2), 2, '');
    return ((int)$whole * 100) + (int)str_pad($fraction, 2, '0');
}

function rent_money_from_cents(int $cents): string
{
    return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
}

function rent_month_start(mixed $month): string
{
    $month = trim((string)$month);
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $month)) {
        throw new InvalidArgumentException('Choose a valid billing month.');
    }
    return $month . '-01';
}

function rent_valid_date(mixed $value): string
{
    $s = trim((string)$value);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
    if (!$date || $date->format('Y-m-d') !== $s) {
        throw new InvalidArgumentException('Choose a valid due date.');
    }
    return $s;
}

function rent_visual_status(array $payment): string
{
    $state = (string)$payment['status'];
    if ($state === 'paid') return 'Paid';
    if ((string)$payment['due_date'] < date('Y-m-d')) {
        return $state === 'partial' ? 'Overdue (partial)' : 'Overdue';
    }
    return $state === 'partial' ? 'Partially paid' : 'Unpaid';
}

function rent_remaining(array $payment): string
{
    $due = rent_parse_cents($payment['amount_due']);
    $paid = rent_parse_cents($payment['amount_paid']);
    return rent_money_from_cents(max(0, $due - $paid));
}
