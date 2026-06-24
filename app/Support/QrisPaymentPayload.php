<?php

namespace App\Support;

use App\Models\ServiceOrder;
use Illuminate\Support\Str;

class QrisPaymentPayload
{
    public static function forOrder(ServiceOrder $order): array
    {
        $invoice = $order->invoice;
        $amount = (float) ($invoice?->total_price ?? 0);
        $reference = self::reference($order);
        $basePayload = trim((string) config('payments.qris.payload', ''));
        $imageUrl = trim((string) config('payments.qris.image_url', ''));
        $payload = null;

        if ($basePayload !== '' && $amount > 0) {
            $payload = self::dynamicPayload($basePayload, $amount, $reference);
            $imageUrl = self::generatorUrl($payload);
        }

        return [
            'configured' => $payload !== null || $imageUrl !== '',
            'payload' => $payload,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'reference' => $reference,
            'merchant_name' => (string) config('payments.qris.merchant_name', 'Forkis'),
            'merchant_city' => (string) config('payments.qris.merchant_city', 'Bekasi'),
            'amount' => $amount,
        ];
    }

    private static function reference(ServiceOrder $order): string
    {
        $invoiceNumber = $order->invoice?->invoice_number ?: 'NOINV';
        $orderNumber = $order->order_number ?: ('ORDER-'.$order->id);

        return Str::upper(Str::limit(
            preg_replace('/[^A-Za-z0-9-]/', '-', "QRIS-{$invoiceNumber}-{$orderNumber}"),
            100,
            ''
        ));
    }

    private static function dynamicPayload(string $basePayload, float $amount, string $reference): string
    {
        $fields = self::parseTopLevelFields($basePayload);
        $fields = array_values(array_filter(
            $fields,
            fn (array $field): bool => ! in_array($field['id'], ['54', '63'], true)
        ));

        self::upsertField($fields, '01', '12');
        self::upsertField($fields, '54', self::formatAmount($amount), beforeId: '58');
        self::upsertField($fields, '62', self::additionalData($reference), beforeId: '63');

        $withoutCrc = self::buildPayload($fields);

        return $withoutCrc.'6304'.self::crc16($withoutCrc.'6304');
    }

    private static function parseTopLevelFields(string $payload): array
    {
        $payload = preg_replace('/\s+/', '', $payload) ?? '';
        $fields = [];
        $offset = 0;
        $length = strlen($payload);

        while ($offset + 4 <= $length) {
            $id = substr($payload, $offset, 2);
            $valueLength = (int) substr($payload, $offset + 2, 2);
            $valueStart = $offset + 4;

            if ($valueLength < 0 || $valueStart + $valueLength > $length) {
                break;
            }

            $value = substr($payload, $valueStart, $valueLength);
            $fields[] = compact('id', 'value');
            $offset = $valueStart + $valueLength;

            if ($id === '63') {
                break;
            }
        }

        return $fields;
    }

    private static function upsertField(array &$fields, string $id, string $value, ?string $beforeId = null): void
    {
        foreach ($fields as &$field) {
            if ($field['id'] === $id) {
                $field['value'] = $value;

                return;
            }
        }

        $field = ['id' => $id, 'value' => $value];
        if ($beforeId !== null) {
            foreach ($fields as $index => $existingField) {
                if ($existingField['id'] === $beforeId) {
                    array_splice($fields, $index, 0, [$field]);

                    return;
                }
            }
        }

        $fields[] = $field;
    }

    private static function buildPayload(array $fields): string
    {
        return collect($fields)
            ->map(fn (array $field): string => $field['id'].str_pad((string) strlen($field['value']), 2, '0', STR_PAD_LEFT).$field['value'])
            ->implode('');
    }

    private static function additionalData(string $reference): string
    {
        $billNumber = Str::limit($reference, 25, '');

        return '01'.str_pad((string) strlen($billNumber), 2, '0', STR_PAD_LEFT).$billNumber;
    }

    private static function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private static function generatorUrl(string $payload): string
    {
        $baseUrl = rtrim((string) config('payments.qris.generator_url'), '?');
        $size = max(180, (int) config('payments.qris.generator_size', 320));

        return $baseUrl.'?'.http_build_query([
            'size' => "{$size}x{$size}",
            'data' => $payload,
        ]);
    }

    private static function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021)
                    : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
