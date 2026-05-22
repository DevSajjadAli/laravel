<?php

namespace Genvoris\Laravel\Webhooks;

/**
 * HMAC-SHA256 webhook verifier.
 *
 * Exact PHP port of class-genvoris-security.php::verify_webhook().
 * Header format: X-Genvoris-Signature: t={timestamp},v1={hmac_hex}
 *
 * Security properties:
 *   - Validates timestamp tolerance (±300 seconds) to prevent replay attacks.
 *   - Validates v1 hex format before hash_equals() to prevent length-mismatch
 *     timing attacks (a constant-time comparison of strings with different
 *     lengths leaks length information in some implementations).
 *   - Uses hash_equals() for constant-time HMAC comparison.
 *   - Returns false on any parse/validation failure (no exceptions thrown here).
 */
final class WebhookVerifier
{
    public const TOLERANCE_SECONDS = 300;

    /**
     * Verify a Genvoris webhook signature.
     *
     * @param  string  $rawBody  The raw (unparsed) request body bytes.
     * @param  string  $signatureHeader  The value of the X-Genvoris-Signature header.
     * @param  string  $secret  The webhook HMAC secret from config.
     * @return bool True when the signature is valid and within the time tolerance.
     */
    public function verify(string $rawBody, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        // Parse comma-separated key=value pairs: "t=1234567890,v1=abc..."
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            $segments = explode('=', $pair, 2);
            if (count($segments) === 2) {
                $parts[trim($segments[0])] = trim($segments[1]);
            }
        }

        if (! isset($parts['t'], $parts['v1'])) {
            return false;
        }

        $ts = (int) $parts['t'];
        $v1 = $parts['v1'];

        if ($ts === 0) {
            return false;
        }

        // Timestamp tolerance check (prevents replay attacks)
        if (abs(time() - $ts) > self::TOLERANCE_SECONDS) {
            return false;
        }

        // Validate hex format before comparison to prevent length-mismatch timing leak
        if (! preg_match('/^[a-f0-9]{64}$/i', $v1)) {
            return false;
        }

        // Compute expected HMAC over "{timestamp}.{body}"
        $expected = hash_hmac('sha256', $ts.'.'.$rawBody, $secret);

        // Constant-time comparison — NEVER use === or ==
        return hash_equals($expected, $v1);
    }
}
