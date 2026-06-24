<?php

namespace App\Support;

use App\Models\ServiceOrder;
use Illuminate\Support\Str;

class TransferPaymentInstructions
{
    public static function forOrder(ServiceOrder $order): array
    {
        $invoice = $order->invoice;
        $reference = self::reference($order);

        return [
            'configured' => self::bankName() !== '' && self::accountNumber() !== '',
            'bank_name' => self::bankName(),
            'account_number' => self::accountNumber(),
            'account_name' => self::accountName(),
            'reference' => $reference,
            'amount' => (float) ($invoice?->total_price ?? 0),
        ];
    }

    public static function reference(ServiceOrder $order): string
    {
        $invoiceNumber = $order->invoice?->invoice_number ?: 'NOINV';
        $orderNumber = $order->order_number ?: ('ORDER-'.$order->id);

        return Str::upper(Str::limit(
            preg_replace('/[^A-Za-z0-9-]/', '-', "TRF-{$invoiceNumber}-{$orderNumber}"),
            100,
            ''
        ));
    }

    private static function bankName(): string
    {
        return trim((string) config('payments.transfer.bank_name', ''));
    }

    private static function accountNumber(): string
    {
        return trim((string) config('payments.transfer.account_number', ''));
    }

    private static function accountName(): string
    {
        return trim((string) config('payments.transfer.account_name', ''));
    }
}
