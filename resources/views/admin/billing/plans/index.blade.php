@extends('layouts.admin')

@section('title')
    Billing Plans
@endsection

@section('content-header')
    <h1>Billing Plans<small>Kelola paket yang dijual ke user.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.billing.settings') }}">Billing</a></li>
        <li class="active">Plans</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Semua Plan</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.billing.plans.create') }}" class="btn btn-sm btn-success">
                        <i class="fa fa-plus"></i> Tambah Plan
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>RAM</th>
                            <th>CPU</th>
                            <th>Disk</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>{{ $plan->id }}</td>
                                <td><strong>{{ $plan->name }}</strong></td>
                                <td>{{ $plan->formatted_price }}</td>
                                <td>{{ $plan->formatted_memory }}</td>
                                <td>{{ $plan->cpu }}%</td>
                                <td>{{ $plan->formatted_disk }}</td>
                                <td>{{ $plan->duration_days }} hari</td>
                                <td>
                                    @if($plan->is_active)
                                        <span class="label label-success">Aktif</span>
                                    @else
                                        <span class="label label-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.billing.plans.edit', $plan->id) }}"
                                        class="btn btn-xs btn-primary">Edit</a>
                                    <form action="{{ route('admin.billing.plans.destroy', $plan->id) }}"
                                        method="POST" style="display:inline;"
                                        onsubmit="return confirm('Hapus plan ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">Belum ada plan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
