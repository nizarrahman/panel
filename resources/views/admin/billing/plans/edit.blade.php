@extends('layouts.admin')

@section('title')
    {{ $plan ? 'Edit Plan' : 'Tambah Plan' }}
@endsection

@section('content-header')
    <h1>{{ $plan ? 'Edit Plan: ' . $plan->name : 'Tambah Plan Baru' }}</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.billing.plans') }}">Plans</a></li>
        <li class="active">{{ $plan ? 'Edit' : 'Tambah' }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form action="{{ $plan ? route('admin.billing.plans.update', $plan->id) : route('admin.billing.plans.store') }}" method="POST">
            @csrf
            @if($plan) @method('PATCH') @endif

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Informasi Plan</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Nama Plan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $plan->name ?? '') }}" required placeholder="Contoh: Starter, Pro, Elite">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Deskripsi singkat plan...">{{ old('description', $plan->description ?? '') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Harga (Rupiah) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="number" name="price" class="form-control"
                                        value="{{ old('price', $plan->price ?? 0) }}" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Durasi (Hari) <span class="text-danger">*</span></label>
                                <input type="number" name="duration_days" class="form-control"
                                    value="{{ old('duration_days', $plan->duration_days ?? 30) }}" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Resource</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>RAM (MB) <span class="text-danger">*</span></label>
                                <input type="number" name="memory" class="form-control"
                                    value="{{ old('memory', $plan->memory ?? 1024) }}" min="128" required>
                                <p class="text-muted small">1024 = 1 GB</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>CPU (%) <span class="text-danger">*</span></label>
                                <input type="number" name="cpu" class="form-control"
                                    value="{{ old('cpu', $plan->cpu ?? 100) }}" min="1" required>
                                <p class="text-muted small">100 = 1 core</p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Disk (MB) <span class="text-danger">*</span></label>
                                <input type="number" name="disk" class="form-control"
                                    value="{{ old('disk', $plan->disk ?? 5120) }}" min="512" required>
                                <p class="text-muted small">1024 = 1 GB</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Database Limit</label>
                                <input type="number" name="databases" class="form-control"
                                    value="{{ old('databases', $plan->databases ?? 0) }}" min="0">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Backup Limit</label>
                                <input type="number" name="backups" class="form-control"
                                    value="{{ old('backups', $plan->backups ?? 0) }}" min="0">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Allocation Limit</label>
                                <input type="number" name="allocations" class="form-control"
                                    value="{{ old('allocations', $plan->allocations ?? 1) }}" min="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-server"></i> Konfigurasi Server Otomatis</h3>
                    <small class="text-muted" style="display:block;margin-top:4px;">
                        Server akan dibuat otomatis setelah user berhasil membayar.
                    </small>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Egg <span class="text-danger">*</span></label>
                        <select name="egg_id" class="form-control" id="eggSelect">
                            <option value="">-- Pilih Egg --</option>
                            @foreach($eggs as $egg)
                                <option value="{{ $egg->id }}"
                                    data-startup="{{ $egg->startup }}"
                                    data-image="{{ is_array($egg->docker_images) ? array_values($egg->docker_images)[0] : $egg->docker_image }}"
                                    {{ old('egg_id', optional($plan)->egg_id ?? '') == $egg->id ? 'selected' : '' }}>
                                    [{{ $egg->nest->name }}] {{ $egg->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-muted small">Egg yang digunakan untuk membuat server user.</p>
                    </div>
                    <div class="form-group">
                        <label>Startup Command</label>
                        <input type="text" name="startup" id="startupInput" class="form-control"
                            value="{{ old('startup', $plan->startup ?? '') }}"
                            placeholder="Kosongkan untuk pakai default egg">
                        <p class="text-muted small">Kosongkan untuk menggunakan startup command default dari egg.</p>
                    </div>
                    <div class="form-group">
                        <label>Docker Image</label>
                        <input type="text" name="image" id="imageInput" class="form-control"
                            value="{{ old('image', $plan->image ?? '') }}"
                            placeholder="Kosongkan untuk pakai default egg">
                        <p class="text-muted small">Kosongkan untuk menggunakan image default dari egg.</p>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Swap (MB)</label>
                                <input type="number" name="swap" class="form-control"
                                    value="{{ old('swap', $plan->swap ?? 0) }}" min="-1">
                                <p class="text-muted small">-1 = unlimited, 0 = disabled</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Block IO Weight</label>
                                <input type="number" name="io" class="form-control"
                                    value="{{ old('io', $plan->io ?? 500) }}" min="10" max="1000">
                                <p class="text-muted small">10–1000, default 500</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Locations (untuk auto-deploy)</label>
                        <div>
                            @foreach($locations as $location)
                                <label style="margin-right:15px;font-weight:normal;">
                                    <input type="checkbox" name="location_ids[]" value="{{ $location->id }}"
                                        {{ in_array($location->id, old('location_ids', $plan->location_ids ?? [])) ? 'checked' : '' }}>
                                    {{ $location->short }} — {{ $location->long }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-muted small">Pilih lokasi mana server boleh dibuat. Kosong = semua lokasi.</p>
                    </div>

                    {{-- Environment Variables (render dinamis via JS) --}}
                    <div id="envSection" style="display:none;">
                        <hr>
                        <h4>Environment Variables</h4>
                        <p class="text-muted small">Nilai default dari egg akan digunakan jika dikosongkan.</p>
                        <div id="envFields"></div>
                    </div>
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Tampilan</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Urutan Tampil</label>
                                <input type="number" name="sort_order" class="form-control"
                                    value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0">
                                <p class="text-muted small">Angka kecil tampil lebih dulu.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Status</label>
                                <div>
                                    <input type="checkbox" name="is_active" value="1"
                                        {{ ($plan->is_active ?? true) ? 'checked' : '' }}>
                                    <span> Aktif (tampil di shop)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('admin.billing.plans') }}" class="btn btn-default">Batal</a>
                    <button type="submit" class="btn btn-primary pull-right">
                        {{ $plan ? 'Simpan Perubahan' : 'Buat Plan' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    // Semua egg variables di-embed sebagai JSON — tidak perlu reload halaman
    var eggData = {
        @foreach($eggs as $egg)
        "{{ $egg->id }}": {
            "startup": @json($egg->startup),
            "image": @json(is_array($egg->docker_images) ? array_values($egg->docker_images)[0] : ''),
            "variables": [
                @foreach($egg->variables as $variable)
                {
                    "name": @json($variable->name),
                    "env": @json($variable->env_variable),
                    "default": @json($variable->default_value ?? ''),
                    "description": @json($variable->description ?? ''),
                    "required": {{ $variable->required ? 'true' : 'false' }}
                },
                @endforeach
            ]
        },
        @endforeach
    };

    // Nilai env yang sudah tersimpan (saat edit plan)
    var savedEnv = @json(optional($plan)->environment ?? []);

    function renderEnvFields(eggId) {
        var envSection = document.getElementById('envSection');
        var envFields  = document.getElementById('envFields');

        if (!eggId || !eggData[eggId]) {
            envSection.style.display = 'none';
            envFields.innerHTML = '';
            return;
        }

        var vars = eggData[eggId].variables;
        if (!vars || vars.length === 0) {
            envSection.style.display = '';
            envFields.innerHTML = '<p class="text-muted">Egg ini tidak memiliki environment variables.</p>';
            return;
        }

        var html = '';
        vars.forEach(function(v) {
            var savedVal = savedEnv[v.env] !== undefined ? savedEnv[v.env] : v.default;
            html += '<div class="form-group">';
            html += '<label>' + escHtml(v.name);
            if (v.required) html += ' <span class="text-danger">*</span>';
            html += '</label>';
            html += '<input type="text" name="environment[' + escHtml(v.env) + ']"';
            html += ' class="form-control"';
            html += ' value="' + escHtml(savedVal) + '"';
            html += ' placeholder="' + escHtml(v.default) + '">';
            if (v.description) {
                html += '<p class="text-muted small">' + escHtml(v.description) + '</p>';
            }
            html += '</div>';
        });

        envFields.innerHTML = html;
        envSection.style.display = '';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var eggSelect = document.getElementById('eggSelect');

    eggSelect.addEventListener('change', function() {
        var eggId   = this.value;
        var selected = this.options[this.selectedIndex];

        // Auto-fill placeholder startup & image
        var startupInput = document.getElementById('startupInput');
        var imageInput   = document.getElementById('imageInput');

        if (eggId && eggData[eggId]) {
            if (!startupInput.value) startupInput.placeholder = eggData[eggId].startup || '';
            if (!imageInput.value)   imageInput.placeholder   = eggData[eggId].image   || '';
        }

        renderEnvFields(eggId);
    });

    // Render saat halaman pertama kali load (mode edit)
    var initialEggId = eggSelect.value;
    if (initialEggId) {
        renderEnvFields(initialEggId);

        var startupInput = document.getElementById('startupInput');
        var imageInput   = document.getElementById('imageInput');
        if (eggData[initialEggId]) {
            if (!startupInput.value) startupInput.placeholder = eggData[initialEggId].startup || '';
            if (!imageInput.value)   imageInput.placeholder   = eggData[initialEggId].image   || '';
        }
    }
    </script>
@endsection
