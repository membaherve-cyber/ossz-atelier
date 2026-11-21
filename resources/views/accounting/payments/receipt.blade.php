<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $payment->reference }} — OSSZ ATELIER</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #26262c; margin: 32px; font-size: 13px; max-width: 680px; }
        .brand { font-weight: 700; letter-spacing: .08em; font-size: 15px; }
        .muted { color: #6b6b76; font-size: 11px; }
        h1 { margin: 20px 0 4px; font-size: 22px; letter-spacing: .06em; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e2e7; padding: 6px 10px; text-align: left; vertical-align: top; }
        th { background: #f4f4f6; text-transform: uppercase; font-size: 10px; letter-spacing: .05em; width: 38%; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .amount { margin-top: 16px; border: 2px solid #26262c; padding: 14px 16px; text-align: center; }
        .amount .label { text-transform: uppercase; font-size: 10px; letter-spacing: .08em; color: #6b6b76; }
        .amount .value { font-size: 26px; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: 4px; }
        .thanks { margin-top: 28px; text-align: center; font-size: 13px; }
        .print-btn { display: inline-flex; align-items: center; gap: 6px; margin-top: 20px; padding: 8px 16px; border: 1px solid #d4d4d8; background: #fff; border-radius: 6px; font: inherit; cursor: pointer; }
        .print-btn:hover { background: #f4f4f6; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @php
        /**
         * OSSZ ATELIER — printable payment receipt (GET /payments/{id}/receipt,
         * perm accounting.payments; Finance & Comptabilité spec v1.3 §4.2
         * "édition de reçus").
         *
         * Variables (PaymentController@receipt): $payment (with
         * order.customer and treasuryAccount).
         *
         * Standalone print document (same approach as accounting/print):
         * FR-primary bilingual labels because this is a customer
         * document in Cameroon; auto-opens the print dialog via
         * ?print=1, with an on-page button as fallback.
         */
    @endphp

    <div class="brand">OSSZ ATELIER</div>
    <div class="muted">Atelier de couture — Douala, Cameroun</div>
    <div class="muted">{{ \App\Support\Trans::fill('frep.printedAt', ['date' => now()->locale(app()->getLocale())->translatedFormat('j M Y H:i')]) }}</div>
    <hr style="border: none; border-top: 2px solid #26262c; margin: 10px 0 16px;">

    <h1>REÇU / RECEIPT</h1>

    <table>
        <tbody>
            <tr>
                <th>Reçu n° / Receipt no.</th>
                <td class="num">{{ $payment->reference }}</td>
            </tr>
            <tr>
                <th>{{ __('pay.colDate') }}</th>
                <td class="num">{{ $payment->payment_date->locale(app()->getLocale())->translatedFormat('j F Y') }}</td>
            </tr>
            <tr>
                <th>Reçu de / Received from</th>
                <td>
                    <strong>{{ $payment->order?->customer?->name ?? $payment->customer?->name ?? '—' }}</strong>
                    @if ($payment->order?->customer?->phone)
                        <span class="muted"> · {{ $payment->order->customer->phone }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Pour la commande / For order</th>
                <td>
                    {{ $payment->order?->order_number ?? '—' }}
                    @if ($payment->order?->expected_delivery_date)
                        <span class="muted"> · {{ __('orders.statDelivery') }}: {{ $payment->order->expected_delivery_date->locale(app()->getLocale())->translatedFormat('j M Y') }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('pay.colMethod') }}</th>
                <td>
                    {{ __('labels.payMethod.'.$payment->method) }}
                    @if ($payment->treasuryAccount?->name)
                        <span class="muted"> · {{ $payment->treasuryAccount->name }}</span>
                    @endif
                    @if ($payment->txn_ref)
                        <span class="muted"> · {{ $payment->txn_ref }}</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="amount">
        <div class="label">{{ __('pay.colAmount') }} / Montant</div>
        <div class="value">{{ \App\Support\Money::format($payment->amount, $payment->currency) }}</div>
    </div>

    @if ($payment->notes)
        <table>
            <tbody>
                <tr>
                    <th>{{ __('pay.fieldNotes') }}</th>
                    <td>{{ $payment->notes }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <p class="thanks">Merci de votre confiance / Thank you for your trust</p>

    <button type="button" class="print-btn no-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
        {{ __('frep.print') }}
    </button>

    <script>if (window.location.search.includes('print=1')) window.print()</script>
</body>
</html>
