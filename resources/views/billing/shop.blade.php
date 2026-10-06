@extends('layouts.billing')

@section('title')
    {{ $shopTitle }}
@endsection

@section('content-header')
    <h1>{{ $shopTitle }}<small>{{ $shopDescription }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">Home</a></li>
        <li class="active">Shop</li>
    </ol>
@endsection

@section('content')
<div id="billing-flash" class="hidden"></div>

<div class="row">
    @forelse($plans as $plan)
        <div class="col-md-4 col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border text-center">
                    <h3 class="box-title" style="font-size:20px;">{{ $plan->name }}</h3>
                </div>
                <div class="box-body text-center">
                    <p style="font-size:28px;font-weight:bold;color:#3c8dbc;">
                        Rp {{ number_format($plan->price, 0, ',', '.') }}
                    </p>
                    <p class="text-muted">Per {{ $plan->duration_days }} hari</p>
                    <hr>
                    <table class="table table-condensed" style="text-align:left;">
                        <tr><td><i class="fa fa-memory"></i> RAM</td><td><strong>{{ $plan->formatted_memory }}</strong></td></tr>
                        <tr><td><i class="fa fa-microchip"></i> CPU</td><td><strong>{{ $plan->cpu }}%</strong></td></tr>
                        <tr><td><i class="fa fa-hdd-o"></i> Disk</td><td><strong>{{ $plan->formatted_disk }}</strong></td></tr>
                        @if($plan->databases > 0)
                            <tr><td><i class="fa fa-database"></i> Database</td><td><strong>{{ $plan->databases }}</strong></td></tr>
                        @endif
                        @if($plan->backups > 0)
                            <tr><td><i class="fa fa-archive"></i> Backup</td><td><strong>{{ $plan->backups }}</strong></td></tr>
                        @endif
                    </table>
                    @if($plan->description)
                        <p class="text-muted small">{{ $plan->description }}</p>
                    @endif
                </div>
                <div class="box-footer text-center">
                    <button class="btn btn-primary btn-block btn-buy"
                        data-plan-id="{{ $plan->id }}"
                        data-plan-name="{{ $plan->name }}"
                        data-plan-price="{{ $plan->price }}">
                        <i class="fa fa-credit-card"></i> Beli Sekarang
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-xs-12">
            <div class="callout callout-info">
                <p>Belum ada plan yang tersedia saat ini.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="row">
    <div class="col-xs-12">
        <a href="{{ route('billing.orders') }}" class="btn btn-default">
            <i class="fa fa-list"></i> Riwayat Pembelian Saya
        </a>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    {{-- Midtrans Snap --}}
    @if($isProduction)
        <script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
    @else
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $clientKey }}"></script>
    @endif

    <script>
        document.querySelectorAll('.btn-buy').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var planId   = this.dataset.planId;
                var planName = this.dataset.planName;
                var self     = this;

                self.disabled = true;
                self.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';

                fetch('{{ route('billing.order.create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ plan_id: planId }),
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        self.disabled = false;
                        self.innerHTML = '<i class="fa fa-credit-card"></i> Beli Sekarang';
                        return;
                    }

                    // Buka Midtrans Snap popup
                    snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            window.location.href = '{{ route('billing.orders') }}?paid=1';
                        },
                        onPending: function(result) {
                            window.location.href = '{{ route('billing.orders') }}?pending=1';
                        },
                        onError: function(result) {
                            alert('Pembayaran gagal. Silakan coba lagi.');
                            self.disabled = false;
                            self.innerHTML = '<i class="fa fa-credit-card"></i> Beli Sekarang';
                        },
                        onClose: function() {
                            self.disabled = false;
                            self.innerHTML = '<i class="fa fa-credit-card"></i> Beli Sekarang';
                        },
                    });
                })
                .catch(function(err) {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    self.disabled = false;
                    self.innerHTML = '<i class="fa fa-credit-card"></i> Beli Sekarang';
                });
            });
        });
    </script>
@endsection
