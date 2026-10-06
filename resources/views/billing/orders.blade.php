@extends('layouts.billing')

@section('title')
    Riwayat Pembelian
@endsection

@section('content-header')
    <h1>Riwayat Pembelian<small>Order yang telah kamu buat.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">Home</a></li>
        <li><a href="{{ route('billing.shop') }}">Shop</a></li>
        <li class="active">Riwayat</li>
    </ol>
@endsection

@section('content')

@if(request('paid'))
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> Pembayaran berhasil! Order sedang diproses.</div>
@endif
@if(request('pending'))
    <div class="alert alert-warning"><i class="fa fa-clock-o"></i> Pembayaran pending. Selesaikan pembayaran untuk mengaktifkan plan.</div>
@endif

<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Order Saya</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Plan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><code>{{ $order->order_id }}</code></td>
                                <td>{{ $order->plan->name ?? '-' }}</td>
                                <td>Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'paid'      => 'success',
                                            'pending'   => 'warning',
                                            'failed'    => 'danger',
                                            'expired'   => 'default',
                                            default     => 'info',
                                        };
                                    @endphp
                                    <span class="label label-{{ $badge }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $order->paid_at ? $order->paid_at->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada order. <a href="{{ route('billing.shop') }}">Lihat shop</a>.
                                </td>
                            </tr>
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
