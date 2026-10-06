@extends('layouts.admin')

@section('title')
    Billing Orders
@endsection

@section('content-header')
    <h1>Orders<small>Riwayat semua transaksi.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.billing.settings') }}">Billing</a></li>
        <li class="active">Orders</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Semua Transaksi</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th>Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><code>{{ $order->order_id }}</code></td>
                                <td>
                                    <a href="{{ route('admin.users.view', $order->user_id) }}">
                                        {{ $order->user->username ?? '-' }}
                                    </a>
                                </td>
                                <td>{{ $order->plan->name ?? '-' }}</td>
                                <td>Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'paid'      => 'success',
                                            'pending'   => 'warning',
                                            'failed'    => 'danger',
                                            'expired'   => 'default',
                                            'cancelled' => 'default',
                                            default     => 'info',
                                        };
                                    @endphp
                                    <span class="label label-{{ $badge }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>{{ $order->payment_type ?? '-' }}</td>
                                <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $order->paid_at ? $order->paid_at->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
