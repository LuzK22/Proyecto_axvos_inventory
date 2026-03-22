<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should NOT be stripped (passwords, tokens, encoded content).
     */
    private const SKIP_FIELDS = [
        'password', 'password_confirmation', 'current_password',
        '_token', 'two_factor_code', 'two_factor_recovery_code',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $this->sanitize($input);
        $request->replace($input);

        return $next($request);
    }

    private function sanitize(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, self::SKIP_FIELDS, true)) {
                continue;
            }

            if (is_array($value)) {
                $this->sanitize($value);
            } elseif (is_string($value)) {
                $value = strip_tags($value);
            }
        }
    }
}
