<?php

namespace DreamFactory\Core\DreamFactoryPrometheusExporter\Tests\Security;

use DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger\APIRequestExporter;
use PHPUnit\Framework\TestCase;

/**
 * Security: APIRequestExporter must cap metric-label cardinality.
 *
 * Without bounding, a single record-ID-bearing URL like
 * /api/v2/db/_table/users/<id> creates one new Prometheus time-series
 * per unique <id>, exploding Prometheus memory.
 */
class CardinalityCapTest extends TestCase
{
    /** @dataProvider normalizationProvider */
    public function testUriIsBucketed(string $input, string $expected): void
    {
        $this->assertSame($expected, APIRequestExporter::normalizeUriForLabel($input));
    }

    public static function normalizationProvider(): array
    {
        return [
            'integer record id'      => ['/api/v2/db/_table/users/12345', '/api/v2/db/_table/users/:id'],
            'multiple int segments'  => ['/api/v2/db/_table/orders/42/items/7', '/api/v2/db/_table/orders/:id/items/:id'],
            'uuid segment'           => ['/api/v2/db/_table/users/550e8400-e29b-41d4-a716-446655440000', '/api/v2/db/_table/users/:uuid'],
            'hex hash segment'       => ['/api/v2/files/abcdef0123456789abcdef0123456789', '/api/v2/files/:hash'],
            'plain path unchanged'   => ['/api/v2/db/_schema', '/api/v2/db/_schema'],
        ];
    }

    public function testHardCapAtLength(): void
    {
        // Long non-hex path so the bucketing rules (id/uuid/hash) don't fire.
        $longPath = '/api/v2/' . str_repeat('XYZ_', 200);
        $out = APIRequestExporter::normalizeUriForLabel($longPath);
        $this->assertLessThanOrEqual(259, strlen($out));
        $this->assertStringEndsWith('...', $out);
    }
}
