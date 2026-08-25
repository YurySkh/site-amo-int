<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\LeadData;
use App\Http\ApiException;

final class LeadRequestValidator
{
    /**
     * @param array<string, mixed> $input
     */
    public static function validate(array $input): LeadData
    {
        $errors = [];

        $name = self::optionalTrimmedString($input, 'name', $errors);
        $email = self::optionalTrimmedString($input, 'email', $errors);
        $phone = self::phone($input, $errors);
        $price = self::price($input, $errors);
        $timeOnSiteOver30 = self::timeOnSiteOver30($input, $errors);
        $roistatVisit = self::roistatVisit($input, $errors);

        if (self::length($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        }

        if (self::length($email) > 254) {
            $errors['email'] = 'Email must not exceed 254 characters.';
        } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email has an invalid format.';
        }

        if ($errors !== []) {
            throw new ApiException(422, 'Validation failed.', $errors);
        }

        return new LeadData(
            name: $name,
            email: $email,
            phone: $phone,
            price: $price,
            timeOnSiteOver30: $timeOnSiteOver30,
            roistatVisit: $roistatVisit,
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    private static function optionalTrimmedString(array $input, string $field, array &$errors): string
    {
        $value = $input[$field] ?? '';

        if (!is_string($value)) {
            $errors[$field] = ucfirst($field) . ' must be a string.';
            return '';
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    private static function phone(array $input, array &$errors): string
    {
        $value = $input['phone'] ?? null;

        if (!is_string($value)) {
            $errors['phone'] = 'Phone must be a string.';
            return '';
        }

        if (!preg_match('/^\d{5,15}$/D', $value)) {
            $errors['phone'] = 'Phone must contain from 5 to 15 digits.';
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    private static function price(array $input, array &$errors): int
    {
        $value = $input['price'] ?? '';

        if ($value === '' || $value === null) {
            return 0;
        }

        if (is_int($value)) {
            if ($value < 0 || $value > 99_999_999) {
                $errors['price'] = 'Price must be an integer from 0 to 99999999.';
                return 0;
            }

            return $value;
        }

        if (!is_string($value) || !preg_match('/^\d{1,8}$/D', $value)) {
            $errors['price'] = 'Price must contain up to 8 digits.';
            return 0;
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    private static function timeOnSiteOver30(array $input, array &$errors): bool
    {
        $value = $input['timeOnSiteOver30'] ?? false;

        if (!is_bool($value)) {
            $errors['timeOnSiteOver30'] = 'Time-on-site flag must be a boolean.';
            return false;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    private static function roistatVisit(array $input, array &$errors): string
    {
        $value = $input['roistatVisit'] ?? '';

        if (!is_string($value)) {
            $errors['roistatVisit'] = 'Roistat visit ID must be a string.';
            return '';
        }

        if (self::length($value) > 255) {
            $errors['roistatVisit'] = 'Roistat visit ID must not exceed 255 characters.';
        }

        return $value;
    }

    private static function length(string $value): int
    {
        return mb_strlen($value, 'UTF-8');
    }
}
