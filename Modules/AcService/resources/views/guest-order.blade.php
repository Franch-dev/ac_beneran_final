@extends('layouts.app')

@section('title', 'Ajukan Service Order - ' . ($moduleName ?? 'AC Service'))

@section('content')
<section class="section">
    <div class="container" style="max-width: 940px;">
        <div class="section-header">
            <div class="features-eyebrow">Permintaan Servis</div>
            <h1>Formulir Service Order untuk Tamu{{ isset($moduleName) ? ' - ' . $moduleName : '' }}</h1>
            <p>Silakan kirimkan permintaan servis AC Anda tanpa harus masuk. Tim kami akan menindaklanjutinya dalam waktu kerja.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success glass-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger glass-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <strong>Perhatikan beberapa hal berikut:</strong>
                <ul style="margin-top: 0.75rem; margin-left: 1rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php $formActionRoute = $formActionRoute ?? 'modules.ac-service.guest-order.store'; @endphp
        <form action="{{ route($formActionRoute) }}" method="POST" class="glass-card" style="padding: 2rem;">
            @csrf
            <div class="form-grid" style="display:grid; gap:1.25rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom:1.5rem;">
                <div class="form-group">
                    <label for="masjid_id">Pilih Masjid</label>
                    <select id="masjid_id" name="masjid_id" class="form-control" required>
                        <option value="">Pilih lokasi servis</option>
                        @foreach($masjids as $masjid)
                            <option value="{{ $masjid->id }}" {{ old('masjid_id') == $masjid->id ? 'selected' : '' }}>
                                {{ $masjid->name }} ({{ $masjid->custom_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="service_date">Tanggal Servis</label>
                    <input id="service_date" type="date" name="service_date" value="{{ old('service_date') }}" class="form-control" min="{{ now()->toDateString() }}" required>
                </div>

                <div class="form-group">
                    <label for="meeting_person">Penanggung Jawab</label>
                    <select id="meeting_person" name="meeting_person" class="form-control" required>
                        <option value="">Pilih peran</option>
                        <option value="dkm" {{ old('meeting_person') == 'dkm' ? 'selected' : '' }}>DKM</option>
                        <option value="marbot" {{ old('meeting_person') == 'marbot' ? 'selected' : '' }}>Marbot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0812xxxx" required>
                </div>
            </div>

            <div class="form-section glass-card" style="padding:1.5rem; margin-bottom:1.5rem;">
                <h2 style="margin:0 0 1rem;">Detail Unit AC</h2>
                <div class="form-grid" style="display:grid; gap:1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div class="form-group">
                        <label for="detail_pk_type">Jenis Unit</label>
                        <select id="detail_pk_type" name="details[0][pk_type]" class="form-control" required>
                            <option value="">Pilih ukuran</option>
                            <option value="1PK" {{ old('details.0.pk_type') == '1PK' ? 'selected' : '' }}>1 PK</option>
                            <option value="2PK" {{ old('details.0.pk_type') == '2PK' ? 'selected' : '' }}>2 PK</option>
                            <option value="5PK" {{ old('details.0.pk_type') == '5PK' ? 'selected' : '' }}>5 PK</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="detail_brand">Merek</label>
                        <input id="detail_brand" type="text" name="details[0][brand]" value="{{ old('details.0.brand') }}" class="form-control" placeholder="Contoh: Panasonic" required>
                    </div>
                    <div class="form-group">
                        <label for="detail_quantity">Jumlah Unit</label>
                        <input id="detail_quantity" type="number" min="1" name="details[0][quantity]" value="{{ old('details.0.quantity', 1) }}" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="notes">Catatan Tambahan</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Contoh: Filter kotor, suara berisik, atau pendinginan kurang maksimal.">{{ old('notes') }}</textarea>
            </div>

            <div class="form-actions" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center;">
                <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                <a href="{{ route($returnRoute ?? 'modules.ac-service.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</section>
@endsection
