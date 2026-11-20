<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>OSSZ ATELIER — Daily Health Report</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f4;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1c1917;">
@php
    /** Daily business-health e-mail — self-contained inline styles only. */
    $money = static fn (null|float|int $v): string => number_format((float) ($v ?? 0), 0).' XAF';
    $date = $report['reportDate'] instanceof \DateTimeInterface ? $report['reportDate']->format('l d F Y') : '';
    $tzName = (string) ($report['timezone'] ?? 'Africa/Douala');
    $prod = is_array($report['production']) ? $report['production'] : null;
    $fin = is_array($report['financial']) ? $report['financial'] : null;
    $staff = is_array($report['staff']) ? $report['staff'] : null;
    $alerts = is_array($report['alerts']) ? $report['alerts'] : null;
@endphp
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">OSSZ ATELIER daily business health report — production, payments and expenses at a glance.</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f4;">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background-color:#ffffff;border:1px solid #e7e5e4;">

                {{-- ===================== HEADER ===================== --}}
                <tr>
                    <td style="background-color:#1c1917;padding:28px 32px;border-top:4px solid #b45309;">
                        <p style="margin:0;font-size:20px;font-weight:700;letter-spacing:0.08em;color:#ffffff;">OSSZ&nbsp;ATELIER</p>
                        <p style="margin:6px 0 0;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#a8a29e;">Garment Production &amp; Workshop Management</p>
                        <p style="margin:20px 0 0;font-size:22px;font-weight:700;color:#ffffff;">Daily Business Health Report</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#d6d3d1;">{{ $date }} · {{ $tzName }}</p>
                    </td>
                </tr>

                {{-- ===================== TODAY KPI CARDS ===================== --}}
                <tr>
                    <td style="padding:24px 32px 8px;">
                        <p style="margin:0 0 14px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Today at a glance</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%" valign="top" style="padding:0 6px 12px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;background-color:#fafaf9;">
                                        <tr><td style="padding:14px 16px;">
                                            <p style="margin:0;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;">New orders</p>
                                            <p style="margin:6px 0 0;font-size:24px;font-weight:700;color:#1c1917;">{{ (int) $report['today']['ordersRegistered'] }}</p>
                                        </td></tr>
                                    </table>
                                </td>
                                <td width="50%" valign="top" style="padding:0 0 12px 6px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;background-color:#fafaf9;">
                                        <tr><td style="padding:14px 16px;">
                                            <p style="margin:0;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;">Pieces completed today</p>
                                            <p style="margin:6px 0 0;font-size:24px;font-weight:700;color:#1c1917;">{{ (int) $report['today']['piecesCompleted'] }}</p>
                                        </td></tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" valign="top" style="padding:0 6px 0 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;background-color:#fafaf9;">
                                        <tr><td style="padding:14px 16px;">
                                            <p style="margin:0;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;">Payments received</p>
                                            <p style="margin:6px 0 0;font-size:19px;font-weight:700;color:#15803d;">{{ $money($report['today']['paymentsIn']) }}</p>
                                            <p style="margin:2px 0 0;font-size:11px;color:#78716c;">{{ (int) $report['today']['paymentsInCount'] }} payment(s)</p>
                                        </td></tr>
                                    </table>
                                </td>
                                <td width="50%" valign="top" style="padding:0 0 0 6px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;background-color:#fafaf9;">
                                        <tr><td style="padding:14px 16px;">
                                            <p style="margin:0;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;">Expenses out</p>
                                            <p style="margin:6px 0 0;font-size:19px;font-weight:700;color:#b91c1c;">{{ $money($report['today']['expensesOut']) }}</p>
                                            <p style="margin:2px 0 0;font-size:11px;color:#78716c;">{{ (int) $report['today']['expensesOutCount'] }} expense(s)</p>
                                        </td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ===================== PRODUCTION ===================== --}}
                @if ($prod !== null)
                <tr>
                    <td style="padding:20px 32px 4px;">
                        <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Production health</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;">
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Orders in production</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ (int) $prod['ordersInProduction'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Active tasks</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ (int) $prod['activeTasks'] }} <span style="font-weight:400;color:#78716c;">({{ (int) $prod['ongoing'] }} ongoing · {{ (int) $prod['assigned'] }} assigned)</span></td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Awaiting quality inspection</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ (int) $prod['awaitingInspection'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Ready for pickup</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ (int) $prod['readyForPickup'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Blocked tasks</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:{{ $prod['blocked'] > 0 ? '#b91c1c' : '#1c1917' }};">{{ (int) $prod['blocked'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Overdue pieces</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:{{ $prod['overdue'] > 0 ? '#b91c1c' : '#1c1917' }};">{{ (int) $prod['overdue'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:13px;color:#44403c;">On-time delivery (last 30 days)</td>
                                <td align="right" style="padding:12px 16px;font-size:13px;font-weight:700;">{{ $prod['onTimeRate30d'] === null ? '—' : (int) $prod['onTimeRate30d'].'%' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

                {{-- ===================== FINANCIAL ===================== --}}
                @if ($fin !== null)
                <tr>
                    <td style="padding:20px 32px 4px;">
                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Financial health — month to date</p>
                        <p style="margin:0 0 12px;font-size:11px;color:#78716c;">Cash figures use recorded payments and committed expenses only.</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;">
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Order value registered</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ $money($fin['revenueMtd']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Payments received (cash in)</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:#15803d;">{{ $money($fin['paymentsMtd']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Outstanding customer balances</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:#b45309;">{{ $money($fin['outstandingBalances']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Expenses (committed)</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:#b91c1c;">{{ $money($fin['expensesMtd']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Salaries (approved payroll)</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:#b91c1c;">{{ $money($fin['salariesMtd']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Staff advances outstanding</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;">{{ $money($fin['advancesOutstanding']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;color:#44403c;">Net operating result</td>
                                <td align="right" style="padding:12px 16px;border-bottom:1px solid #e7e5e4;font-size:13px;font-weight:700;color:{{ $fin['netResultMtd'] >= 0 ? '#15803d' : '#b91c1c' }};">{{ $money($fin['netResultMtd']) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:13px;color:#44403c;">Net cash movement (payments − paid expenses)</td>
                                <td align="right" style="padding:12px 16px;font-size:13px;font-weight:700;color:{{ $fin['cashMovementMtd'] >= 0 ? '#15803d' : '#b91c1c' }};">{{ $money($fin['cashMovementMtd']) }}</td>
                            </tr>
                        </table>

                        @if (!empty($fin['expenseCategories']))
                            <p style="margin:16px 0 8px;font-size:12px;font-weight:700;color:#44403c;">Top expense categories</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;">
                                @foreach ($fin['expenseCategories'] as $category)
                                    <tr>
                                        <td style="padding:9px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:12px;color:#44403c;">{{ $category['name'] }}</td>
                                        <td align="right" style="padding:9px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:12px;font-weight:700;">{{ $money($category['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>
                @endif

                {{-- ===================== STAFF WORKLOAD ===================== --}}
                @if ($staff !== null && count($staff) > 0)
                <tr>
                    <td style="padding:20px 32px 4px;">
                        <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Staff workload — active staff</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;">
                            <tr style="background-color:#fafaf9;">
                                <td style="padding:10px 16px;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;border-bottom:1px solid #e7e5e4;">Staff</td>
                                <td align="center" style="padding:10px 8px;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;border-bottom:1px solid #e7e5e4;">Active</td>
                                <td align="center" style="padding:10px 8px;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;border-bottom:1px solid #e7e5e4;">Overdue</td>
                                <td align="center" style="padding:10px 16px;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#78716c;border-bottom:1px solid #e7e5e4;">Done today</td>
                            </tr>
                            @foreach ($staff as $row)
                                <tr>
                                    <td style="padding:10px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:13px;font-weight:600;">{{ $row['name'] }}</td>
                                    <td align="center" style="padding:10px 8px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:13px;">{{ (int) $row['active'] }}</td>
                                    <td align="center" style="padding:10px 8px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:13px;font-weight:{{ $row['overdue'] > 0 ? '700' : '400' }};color:{{ $row['overdue'] > 0 ? '#b91c1c' : '#1c1917' }};">{{ (int) $row['overdue'] }}</td>
                                    <td align="center" style="padding:10px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif font-size:13px;">{{ (int) $row['completedToday'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endif

                {{-- ===================== ATTENTION LIST ===================== --}}
                @if ($alerts !== null)
                    @if (count($alerts['overdueTasks']) > 0)
                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b91c1c;">Needs attention — overdue pieces</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;border-left:3px solid #b91c1c;">
                                @foreach ($alerts['overdueTasks'] as $task)
                                    <tr>
                                        <td style="padding:10px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif">
                                            <p style="margin:0;font-size:13px;font-weight:700;">{{ $task['piece'] }} <span style="font-weight:400;color:#78716c;">· {{ $task['orderNumber'] }}</span></p>
                                            <p style="margin:3px 0 0;font-size:12px;color:#78716c;">{{ $task['customer'] }} · {{ $task['staff'] }} · {{ $task['progress'] }}% done · <span style="color:#b91c1c;font-weight:700;">{{ $task['daysOverdue'] }} day(s) late</span></p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    @if (count($alerts['blockedTasks']) > 0)
                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Blocked tasks</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;border-left:3px solid #b45309;">
                                @foreach ($alerts['blockedTasks'] as $task)
                                    <tr>
                                        <td style="padding:10px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif">
                                            <p style="margin:0;font-size:13px;font-weight:700;">{{ $task['piece'] }} <span style="font-weight:400;color:#78716c;">· {{ $task['orderNumber'] }}</span></p>
                                            <p style="margin:3px 0 0;font-size:12px;color:#78716c;">{{ $task['staff'] }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    @if (count($alerts['dueSoonOrders']) > 0)
                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#b45309;">Deliveries due within 48 hours</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;">
                                @foreach ($alerts['dueSoonOrders'] as $order)
                                    <tr>
                                        <td style="padding:10px 16px;@if(! $loop->last)border-bottom:1px solid #e7e5e4;@endif">
                                            <p style="margin:0;font-size:13px;font-weight:700;">{{ $order['orderNumber'] }} <span style="font-weight:400;color:#78716c;">· due {{ $order['deliveryDate'] }}</span></p>
                                            <p style="margin:3px 0 0;font-size:12px;color:#78716c;">{{ $order['customer'] }} · {{ $order['phone'] }} · {{ $order['progress'] }}% produced</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    @if (count($alerts['overdueTasks']) === 0 && count($alerts['blockedTasks']) === 0 && count($alerts['dueSoonOrders']) === 0)
                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e7e5e4;background-color:#fafaf9;border-left:3px solid #15803d;">
                                <tr><td style="padding:14px 16px;font-size:13px;color:#44403c;">
                                    No overdue pieces, blocked tasks or imminent deliveries — production is on track.
                                </td></tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                @endif

                {{-- ===================== FOOTER ===================== --}}
                <tr>
                    <td style="background-color:#fafaf9;border-top:1px solid #e7e5e4;padding:20px 32px;">
                        <p style="margin:0;font-size:12px;color:#78716c;">
                            Generated {{ $report['generatedAt'] instanceof \DateTimeInterface ? $report['generatedAt']->format('d M Y H:i') : '' }} ({{ $tzName }}) by the OSSZ ATELIER management application.
                        </p>
                        <p style="margin:8px 0 0;font-size:12px;color:#78716c;">
                            Manage recipients: <strong>Administration → Daily Report</strong> in the application.
                            You are receiving this message because your address is registered on the daily report distribution list.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
