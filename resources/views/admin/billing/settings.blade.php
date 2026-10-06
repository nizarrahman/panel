@extends('layouts.admin')

@section('title')
    Billing Settings
@endsection

@section('content-header')
    <h1>Billing Settings<small>Konfigurasi payment gateway dan toko.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Billing Settings</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <form action="{{ route('admin.billing.settings.update') }}" method="POST">
            @csrf
            @method('PATCH')

            {{-- General --}}
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shopping-cart"></i> General</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Aktifkan Billing / Shop</label>
                        <div>
                            <input type="checkbox" name="billing_enabled" value="1"
                                {{ ($settings['billing_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                            <small class="text-muted"> Jika dinonaktifkan, halaman shop tidak bisa diakses user.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Judul Shop</label>
                        <input type="text" name="shop_title" class="form-control"
                            value="{{ $settings['shop_title'] ?? 'Shop' }}" placeholder="Shop">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Shop</label>
                        <textarea name="shop_description" class="form-control" rows="3"
                            placeholder="Deskripsi toko...">{{ $settings['shop_description'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Midtrans --}}
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-credit-card"></i> Midtrans Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Environment</label>
                        <div>
                            <input type="checkbox" name="midtrans_production" value="1"
                                {{ ($settings['midtrans_production'] ?? '0') === '1' ? 'checked' : '' }}>
                            <small class="text-muted"> Aktifkan untuk Production (matikan untuk Sandbox/Testing).</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Server Key</label>
                        <input type="text" name="midtrans_server_key" class="form-control"
                            value="{{ $settings['midtrans_server_key'] ?? '' }}"
                            placeholder="SB-Mid-server-XXXX atau Mid-server-XXXX">
                        <p class="text-muted small">Digunakan server-side. Jangan share ke publik.</p>
                    </div>
                    <div class="form-group">
                        <label>Client Key</label>
                        <input type="text" name="midtrans_client_key" class="form-control"
                            value="{{ $settings['midtrans_client_key'] ?? '' }}"
                            placeholder="SB-Mid-client-XXXX atau Mid-client-XXXX">
                        <p class="text-muted small">Digunakan di frontend untuk Midtrans Snap.</p>
                    </div>
                    <div class="callout callout-info">
                        <p><strong>Webhook URL:</strong></p>
                        <code>{{ url('/billing/webhook/midtrans') }}</code>
                        <p class="mt-2">Daftarkan URL ini di <a href="https://dashboard.midtrans.com" target="_blank">Midtrans Dashboard</a> → Settings → Configuration → Payment Notification URL.</p>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
