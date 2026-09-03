<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * PHP Port of "Fractional Indexing" by David Greenspan with a base 62 implementation
 * @See https://observablehq.com/@dgreensp/implementing-fractional-indexing
 *
 * @internal
 */
class FractionalIndexing
{
    private const INTEGER_ZERO = "a0";

    private const SMALLEST_INTEGER = "A00000000000000000000000000";

    private const BASE_62_DIGITS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

    /**
     * `a` is an order key or null (START).
     * `b` is an order key or null (END).
     * `a < b` lexicographically if both are non-null.
     * digits is a string such as '0123456789' for base 10. Digits must be in
     * ascending character code order!
     */
    public static function generateKeyBetween(?string $a, ?string $b, string $digits = self::BASE_62_DIGITS): ?string
    {
        if ($a !== null) {
            self::validateOrderKey($a);
        }

        if ($b !== null) {
            self::validateOrderKey($b);
        }

        if ($a !== null && $b !== null && strcmp($a, $b) >= 0) {
            throw new \RuntimeException($a . ' >= ' . $b);
        }
        if ($a === null && $b === null) {
            return self::INTEGER_ZERO;
        }
        if ($a === null) {
            $ib = self::getIntegerPart($b);
            $fb = substr($b, strlen($ib));
            if ($ib === self::SMALLEST_INTEGER) {
                return $ib . self::midpoint('', $fb, $digits);
            }
            return strcmp($ib, $b) < 0 ? $ib : self::decrementInteger($ib, $digits);
        }

        if ($b === null) {
            $ia = self::getIntegerPart($a);
            $fa = substr($a, strlen($ia));
            $i = self::incrementInteger($ia, $digits);
            return $i ?? ($ia . self::midpoint($fa, null, $digits));
        }

        $ia = self::getIntegerPart($a);
        $fa = substr($a, strlen($ia));
        $ib = self::getIntegerPart($b);
        $fb = substr($b, strlen($ib));
        if ($ia === $ib) {
            return $ia . self::midpoint($fa, $fb, $digits);
        }

        $i = self::incrementInteger($ia, $digits);
        return $i !== null && strcmp($i, $b) < 0 ? $i : $ia . self::midpoint($fa, null, $digits);
    }

    /**
     * same preconditions as generateKeysBetween.
     * n >= 0.
     * Returns an array of n distinct keys in sorted order.
     * If a and b are both null, returns [a0, a1, ...]
     * If one or the other is null, returns consecutive "integer"
     * keys.  Otherwise, returns relatively short keys between
     * a and b.
     *
     * @return array<int,string|null>
     */
    public static function generateNKeysBetween(?string $a, ?string $b, int $n, string $digits = self::BASE_62_DIGITS): array
    {
        if ($n === 0) {
            return [];
        }
        if ($n === 1) {
            return [self::generateKeyBetween($a, $b, $digits)];
        }
        if ($b === null) {
            $c = self::generateKeyBetween($a, $b, $digits);
            $result = [$c];
            for ($i = 0; $i < $n - 1; $i++) {
                $c = self::generateKeyBetween($c, $b, $digits);
                $result[] = $c;
            }
            return $result;
        }
        if ($a === null) {
            $c = self::generateKeyBetween($a, $b, $digits);
            $result = [$c];
            for ($i = 0; $i < $n - 1; $i++) {
                $c = self::generateKeyBetween($a, $c, $digits);
                $result[] = $c;
            }

            return array_reverse($result);
        }
        $mid = intdiv($n, 2);
        $c = self::generateKeyBetween($a, $b, $digits);
        return [
            ...self::generateNKeysBetween($a, $c, $mid, $digits),
            $c,
            ...self::generateNKeysBetween($c, $b, $n - $mid - 1, $digits),
        ];
    }

    private static function validateOrderKey(string $key): void
    {
        if ($key === self::SMALLEST_INTEGER) {
            throw new \RuntimeException('invalid order key: ' . $key);
        }
        $i = self::getIntegerPart($key);
        $f = substr($key, strlen($i));
        if (str_ends_with($f, '0')) {
            throw new \RuntimeException('invalid order key: ' . $key);
        }
    }

    private static function getIntegerPart(string $key): string
    {
        $integerPartLength = self::getIntegerLength($key[0]);
        if ($integerPartLength > strlen($key)) {
            throw new \RuntimeException('invalid order key: ' . $key);
        }
        return substr($key, 0, $integerPartLength);
    }

    /**
     * note that this may return null, as there is a smallest integer
     */
    private static function decrementInteger(string $x, string $digits): ?string
    {
        self::validateInteger($x);
        $digs = str_split($x);
        $head = (string)array_shift($digs);

        $borrow = true;
        for ($i = count($digs) - 1; $borrow && $i >= 0; $i--) {
            $d = strpos($digits, $digs[$i]) - 1;
            if ($d === -1) {
                $digs[$i] = substr($digits, -1);
            } else {
                $digs[$i] = $digits[$d];
                $borrow = false;
            }
        }
        if ($borrow) {
            if ($head === 'a') {
                return 'Z' . substr($digits, -1);
            }
            if ($head === 'A') {
                return null;
            }

            $h = chr(ord($head) - 1);
            if (strcmp($h, 'Z') < 0) {
                $digs[] = substr($digits, -1);

            } else {
                array_pop($digs);
            }
            return $h . implode('', $digs);
        }

        return $head . implode('', $digs);
    }

    /**
     * note that this may return null, as there is a largest integer
     */
    private static function incrementInteger(string $x, string $digits): ?string
    {
        self::validateInteger($x);
        $digs = str_split($x);
        $head = (string)array_shift($digs);

        $carry = true;
        for ($i = count($digs) - 1; $carry && $i >= 0; $i--) {
            $d = strpos($digits, $digs[$i]) + 1;
            if ($d === strlen($digits)) {
                $digs[$i] = '0';
            } else {
                $digs[$i] = $digits[$d];
                $carry = false;
            }
        }

        if ($carry) {
            if ($head === 'Z') {
                return 'a0';
            }
            if ($head === 'z') {
                return null;
            }
            $h = chr(ord($head) + 1);
            if (strcmp($h, 'a') > 0) {
                $digs[] = '0';
            } else {
                array_pop($digs);
            }
            return $h . implode('', $digs);
        }

        return $head . implode('', $digs);
    }

    /**
     * `a` may be empty string, `b` is null or non-empty string.
     * `a < b` lexicographically if `b` is non-null.
     * no trailing zeros allowed.
     * digits is a string such as '0123456789' for base 10.  Digits must be in
     * ascending character code order!
     */
    private static function midpoint(string $a, ?string $b, string $digits): string
    {
        if ($b !== null && strcmp($a, $b) >= 0) {
            throw new \RuntimeException($a . ' >= ' . $b);
        }
        if (str_ends_with($a, '0') || ($b && str_ends_with($b, '0'))) {
            throw new \RuntimeException('trailing zero');
        }
        if ($b) {
            // remove longest common prefix.  pad `a` with 0s as we
            // go.  note that we don't need to pad `b`, because it can't
            // end before `a` while traversing the common prefix.
            $n = 0;
            while (($a[$n] ?? '0') === $b[$n]) {
                $n++;
            }
            if ($n > 0) {
                return substr($b, 0, $n) . self::midpoint(substr($a, $n), substr($b, $n), $digits);
            }
        }

        // first digits (or lack of digit) are different
        $digitA = $a ? strpos($digits, $a[0]) : 0;
        $digitB = $b !== null ? strpos($digits, $b[0]) : strlen($digits);
        if ($digitB - $digitA > 1) {
            $midDigit = (int)round(0.5 * ($digitA + $digitB));
            return $digits[$midDigit];
        }
        // first digits are consecutive
        if ($b && strlen($b) > 1) {
            return $b[0];
        }

        // `b` is null or has length 1 (a single digit).
        // the first digit of `a` is the previous digit to `b`,
        // or 9 if `b` is null.
        // given, for example, midpoint('49', '5'), return
        // '4' + midpoint('9', null), which will become
        // '4' + '9' + midpoint('', null), which is '495'
        return $digits[$digitA] . self::midpoint(substr($a, 1), null, $digits);
    }

    private static function validateInteger(string $int): void
    {
        if (strlen($int) !== self::getIntegerLength($int[0])) {
            throw new \RuntimeException('invalid integer part of order key: ' . $int);
        }
    }

    private static function getIntegerLength(mixed $head): int
    {
        if (strcmp($head, 'a') >= 0 && strcmp($head, 'z') <= 0) {
            return ord($head[0]) - ord('a') + 2;
        }

        if (strcmp($head, 'A') >= 0 && strcmp($head, 'Z') <= 0) {
            return ord('Z') - ord($head[0]) + 2;
        }

        throw new \RuntimeException('Invalid order key head: ' . $head);
    }


}