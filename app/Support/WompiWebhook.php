<?php

declare(strict_types=1);

namespace App\Support;

/** Verificación de firma de eventos Wompi (docs.wompi.co → Eventos). */
final class WompiWebhook
{
    public static function verify(array $payload, string $eventsSecret): bool
    {
        if ($eventsSecret === '') {
            return false;
        }

        $checksum = (string) ($payload['signature']['checksum'] ?? '');
        $properties = $payload['signature']['properties'] ?? [];
        if ($checksum === '' || ! is_array($properties) || $properties === []) {
            return false;
        }

        $token = '';
        foreach ($properties as $property) {
            if (! is_string($property)) {
                return false;
            }
            $parts = explode('.', $property, 2);
            $root = $parts[0];
            $field = $parts[1] ?? null;
            $node = $payload['data'][$root] ?? null;
            if (! is_array($node) || $field === null || ! array_key_exists($field, $node)) {
                return false;
            }
            $token .= (string) $node[$field];
        }

        $token .= (string) ($payload['timestamp'] ?? '').$eventsSecret;
        $expected = hash('sha256', $token);

        return hash_equals($expected, $checksum);
    }
}

// ponytail: runnable self-check — falla si cambia el algoritmo de firma
if (PHP_SAPI === 'cli' && ($argv[0] ?? '') === __FILE__) {
    $secret = 'test_secret';
    $payload = [
        'timestamp' => 1_700_000_000,
        'data' => ['transaction' => ['id' => 'tx_123', 'status' => 'APPROVED']],
        'signature' => [
            'properties' => ['transaction.id', 'transaction.status'],
            'checksum' => hash('sha256', 'tx_123APPROVED1_700_000_000'.$secret),
        ],
    ];
    assert(WompiWebhook::verify($payload, $secret));
    assert(! WompiWebhook::verify($payload, 'wrong'));
    echo "WompiWebhook OK\n";
}
