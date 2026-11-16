<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * OSSZ ATELIER — sequential system-generated codes.
 *
 * Reference formats (see src/lib/atelier/tasks.ts and prisma/seed.ts):
 *   user()     => "USR-001"    (3 digits, users.code)
 *   customer() => "CUST-0001"  (4 digits, customers.code)
 *   task()     => "TASK-0001"  (4 digits, production_tasks.code)
 *   order()    => "ORD-0001"   (4 digits, suggested orders.order_number)
 *
 * Unlike the original count()-based implementation, the next number is
 * derived from the MAXIMUM existing numeric suffix + 1, so codes stay
 * collision-free even after rows have been deleted. Because two concurrent
 * requests can still compute the same candidate, persisting code should use
 * {@see Codes::unique()} which retries the insert on a unique-constraint
 * violation with a freshly generated code.
 */
final class Codes
{
    /**
     * How many times unique() will retry a colliding insert.
     */
    private const MAX_RETRIES = 10;

    /**
     * Next user code: USR-001, USR-002, ...
     */
    public static function user(): string
    {
        return self::next('USR', 3, 'users', 'code');
    }

    /**
     * Next customer code: CUST-0001, CUST-0002, ...
     */
    public static function customer(): string
    {
        return self::next('CUST', 4, 'customers', 'code');
    }

    /**
     * Next production task code: TASK-0001, TASK-0002, ...
     */
    public static function task(): string
    {
        return self::next('TASK', 4, 'production_tasks', 'code');
    }

    /**
     * Next suggested order reference: ORD-0001, ORD-0002, ...
     *
     * Order numbers are normally entered manually (they are the customer's
     * business reference); this generator provides the next free ORD-####
     * suggestion when the operator does not supply one.
     */
    public static function order(): string
    {
        return self::next('ORD', 4, 'orders', 'order_number');
    }

    /**
     * Generate the next free code for the given entity kind and run
     * $attempt with it. If the insert inside $attempt trips the unique
     * constraint (concurrent request grabbed the same code), a fresh code
     * is generated and the attempt is retried.
     *
     * Example:
     *   $customer = Codes::unique('customer', function (string $code) use ($data) {
     *       return Customer::create([...$data, 'code' => $code]);
     *   });
     *
     * @template T
     *
     * @param  callable(string): T  $attempt
     * @return T
     */
    public static function unique(string $kind, callable $attempt): mixed
    {
        $generator = match ($kind) {
            'user' => self::user(...),
            'customer' => self::customer(...),
            'task' => self::task(...),
            'order' => self::order(...),
            default => throw new InvalidArgumentException("Unknown code kind [{$kind}]."),
        };

        for ($tries = 0; $tries <= self::MAX_RETRIES; $tries++) {
            $code = $generator();

            try {
                return $attempt($code);
            } catch (UniqueConstraintViolationException|QueryException $e) {
                if (! self::isUniqueViolation($e) || $tries === self::MAX_RETRIES) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException("Unable to generate a unique code for [{$kind}].");
    }

    /**
     * Compute "PREFIX-<zero-padded max suffix + 1>", skipping codes that
     * already exist (e.g. when the maximum was inserted concurrently).
     */
    private static function next(string $prefix, int $padding, string $table, string $column): string
    {
        $next = self::maxSuffix($prefix, $table, $column) + 1;

        for ($tries = 0; $tries < 100; $tries++) {
            $code = $prefix.'-'.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);

            if (! DB::table($table)->where($column, $code)->exists()) {
                return $code;
            }

            $next++;
        }

        throw new RuntimeException("Unable to find a free code with prefix [{$prefix}].");
    }

    /**
     * Highest numeric suffix currently in use for the prefix (0 when none).
     */
    private static function maxSuffix(string $prefix, string $table, string $column): int
    {
        $max = 0;

        $codes = DB::table($table)
            ->where($column, 'like', $prefix.'-%')
            ->pluck($column);

        foreach ($codes as $code) {
            $suffix = substr((string) $code, strlen($prefix) + 1);

            if ($suffix !== false && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }

    /**
     * Whether the exception is a unique-constraint violation
     * (MySQL/MariaDB SQLSTATE 23000, SQLite/PostgreSQL 23505).
     */
    private static function isUniqueViolation(\Throwable $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        if ($e instanceof QueryException) {
            return in_array($e->errorInfo[0] ?? null, ['23000', '23505'], true);
        }

        return false;
    }
}
