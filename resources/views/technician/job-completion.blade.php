@extends('layouts.app')

@section('title', 'Selesaikan Pekerjaan - AC Beneran')

@push('styles')
<style>
    .photo-preview { position: relative; display: inline-block; margin: 8px; }
    .photo-preview img { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border); }
    .photo-preview .remove-btn { position: absolute; top: -8px; right: -8px; background: var(--danger); color: white; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; border: none; }
    .photo-preview .remove-btn:hover { background: #a61c00; }
    .drop-zone { border: 2px dashed var(--border); border-radius: var(--radius-lg); padding: 40px 20px; text-align: center; transition: all 0.2s; background: var(--bg-card); }
    .drop-zone:hover { border-color: var(--primary); background: var(--primary-soft); }
    .drop-zone.dragover { border-color: var(--primary); background: var(--primary-soft); }
    .drop-zone.has-files { border-color: var(--success); background: var(--success-bg); }
    .drop-zone svg { color: var(--text-muted); }
    .drop-zone.has-files svg { color: var(--success); }
    .photo-count { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: var(--success-bg); color: var(--success); border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 500; margin-top: 12px; }
    .info-row { display: flex; flex-direction: column; gap: 4px; }
    .info-label { font-size: 0.8125rem; color: var(--text-muted); }
    .info-value { font-size: 0.9375rem; font-weight: 500; color: var(--text); }
    .fee-alert { background: var(--warning-bg); border: 1px solid #f5d68e; border-radius: var(--radius); padding: 12px 16px; }
    .fee-alert p { font-size: 0.875rem; color: var(--warning); margin: 0; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--primary); font-size: 0.875rem; font-weight: 500; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: var(--primary-dark); }
    .form-hint { font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px; }
    .required { color: var(--danger); }
    .custom-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .custom-checkbox input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
    .custom-checkbox label { font-size: 0.9375rem; font-weight: 500; color: var(--text); cursor: pointer; }
    @media (max-width: 640px) {
        .photo-preview img { width: 90px; height: 90px; }
        .drop-zone { padding: 24px 16px; }
        .info-grid { grid-template-columns: 1fr !important; }
    }
</style>
@endpush

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <a href="{{ route('technician.dashboard') }}" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h1 class="page-title" style="margin-top: 12px;">
                <i class="fas fa-check-circle"></i> Selesaikan Pekerjaan
            </h1>
            <p class="page-subtitle">Order #{{ $serviceOrder->order_number }}</p>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="glass-card mb-6">
        <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 0.9375rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-list" style="color: var(--primary);"></i> Detail Order
            </h3>
        </div>
        <div class="card-body">
            <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="info-row">
                    <span class="info-label">Masjid</span>
                    <span class="info-value">{{ $serviceOrder->masjid->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value">{{ $serviceOrder->service_date->format('d M Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">{{ $serviceOrder::STATUS_LABELS[$serviceOrder->status] ?? $serviceOrder->status }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice</span>
                    <span class="info-value">{{ $serviceOrder->invoice?->invoice_number ?? 'Belum ada' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Completion Form -->
    <form id="completionForm" class="glass-card">
        @csrf

        <!-- Photo Upload -->
        <div class="form-group">
            <label class="form-label">
                Foto Bukti Pekerjaan <span class="required">*</span>
            </label>
            <p class="form-hint" style="margin-bottom: 12px;">Upload minimal 1 foto sebagai bukti pekerjaan selesai (maks 10 foto, 5MB per foto)</p>

            <div id="dropZone" class="drop-zone cursor-pointer">
                <svg class="w-12 h-12" style="width: 48px; height: 48px; margin: 0 auto 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p style="color: var(--text); font-weight: 500;">Klik atau drag & drop foto di sini</p>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px;">JPG, JPEG, PNG, WEBP (maks 5MB)</p>
            </div>

            <input type="file" id="photoInput" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">

            <!-- Photo Previews -->
            <div id="photoPreviews" style="margin-top: 16px; display: flex; flex-wrap: wrap;"></div>

            <p id="photoError" class="alert alert-danger" style="display: none; margin-top: 12px;">Minimal 1 foto wajib diupload</p>
        </div>

        <!-- Completion Notes -->
        <div class="form-group">
            <label for="completion_notes" class="form-label">Catatan Pekerjaan</label>
            <textarea name="completion_notes" id="completion_notes" rows="3"
                class="form-textarea"
                placeholder="Jelaskan pekerjaan yang telah dilakukan...">{{ old('completion_notes') }}</textarea>
        </div>

        <!-- Fee Reporting -->
        <div style="border-top: 1px solid var(--border); padding-top: 24px; margin-top: 8px;">
            <div class="custom-checkbox" style="margin-bottom: 16px;">
                <input type="checkbox" name="has_fees" id="has_fees" value="1"
                    {{ old('has_fees') ? 'checked' : '' }}>
                <label for="has_fees">Ada biaya tambahan yang perlu dilaporkan</label>
            </div>

            <div id="feeForm" style="display: none;">
                <div class="form-group">
                    <label for="fee_description" class="form-label">Deskripsi Biaya <span class="required">*</span></label>
                    <input type="text" name="fee_description" id="fee_description" value="{{ old('fee_description') }}"
                        class="form-input"
                        placeholder="Contoh: Pengganti freon R32, tambahan pipa">
                </div>

                <div class="form-group">
                    <label for="fee_amount" class="form-label">Jumlah Biaya (Rp) <span class="required">*</span></label>
                    <input type="number" name="fee_amount" id="fee_amount" value="{{ old('fee_amount') }}" min="0" step="1000"
                        class="form-input"
                        placeholder="0">
                </div>

                <div class="form-group">
                    <label for="fee_tools_materials" class="form-label">Alat/Bahan yang Digunakan</label>
                    <textarea name="fee_tools_materials" id="fee_tools_materials" rows="2"
                        class="form-textarea"
                        placeholder="Contoh: Freon R32 1 tabung, pipa 3 meter">{{ old('fee_tools_materials') }}</textarea>
                </div>

                <div class="fee-alert">
                    <p><i class="fas fa-info-circle"></i> Biaya tambahan akan dikirim ke frontdesk untuk diedit invoice-nya, lalu disetujui manager.</p>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div style="border-top: 1px solid var(--border); padding-top: 24px; margin-top: 8px;">
            <button type="button" id="submitBtn" onclick="handleSubmit()"
                class="btn btn-success btn-block btn-lg">
                <i class="fas fa-check"></i> Selesaikan Pekerjaan
            </button>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="popup" style="display: none;">
    <div class="popup-header">
        <h3><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> <span id="confirmTitle">Konfirmasi</span></h3>
        <button class="popup-close" onclick="closeConfirmModal()">&times;</button>
    </div>
    <div class="popup-body">
        <p id="confirmMessage" style="color: var(--text-muted);"></p>
        <div class="popup-actions">
            <button type="button" onclick="closeConfirmModal()" class="btn btn-secondary">Batal</button>
            <button type="button" id="confirmBtn" onclick="submitForm()" class="btn btn-success">Ya, Selesaikan</button>
        </div>
    </div>
</div>

<!-- Fee Reminder Modal -->
<div id="feeReminderModal" class="popup" style="display: none;">
    <div class="popup-header">
        <h3><i class="fas fa-question-circle" style="color: var(--google-blue);"></i> Apakah ada biaya tambahan?</h3>
        <button class="popup-close" onclick="closeFeeReminder()">&times;</button>
    </div>
    <div class="popup-body">
        <p style="color: var(--text-muted);">Sebelum menyelesaikan, pastikan sudah melaporkan biaya tambahan jika ada.</p>
        <div class="popup-actions" style="flex-direction: column;">
            <button type="button" onclick="closeFeeReminder(); document.getElementById('has_fees').checked = true; toggleFeeForm();"
                class="btn btn-warning">
                <i class="fas fa-plus-circle"></i> Ada biaya tambahan
            </button>
            <button type="button" onclick="closeFeeReminder(); showConfirmModal();"
                class="btn btn-success">
                <i class="fas fa-check"></i> Tidak ada, selesai
            </button>
            <button type="button" onclick="closeFeeReminder()" class="btn btn-ghost" style="width: 100%;">
                Kembali
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Photo upload handling
    const dropZone = document.getElementById('dropZone');
    const photoInput = document.getElementById('photoInput');
    const photoPreviews = document.getElementById('photoPreviews');
    const photoError = document.getElementById('photoError');
    let selectedFiles = [];

    function showJobMessage(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        console[type === 'error' ? 'error' : 'info'](message);
    }

    async function confirmJobAction(options) {
        if (typeof window.confirmAction !== 'function') {
            return true;
        }

        const result = await window.confirmAction(options);
        return result?.confirmed || result === true;
    }

    // Click to upload
    dropZone.addEventListener('click', () => photoInput.click());

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    // File input change
    photoInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        for (const file of files) {
            if (!validTypes.includes(file.type)) {
                showJobMessage(`File ${file.name} bukan gambar yang valid. Gunakan JPG, PNG, atau WEBP.`, 'warning');
                continue;
            }
            if (file.size > maxSize) {
                showJobMessage(`File ${file.name} terlalu besar. Maksimal 5MB.`, 'warning');
                continue;
            }
            if (selectedFiles.length >= 10) {
                showJobMessage('Maksimal 10 foto.', 'warning');
                break;
            }

            selectedFiles.push(file);
        }

        renderPreviews();
    }

    function renderPreviews() {
        photoPreviews.innerHTML = '';
        dropZone.classList.toggle('has-files', selectedFiles.length > 0);
        photoError.style.display = 'none';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'photo-preview';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-btn" onclick="removePhoto(${index})">×</button>
                `;
                photoPreviews.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function removePhoto(index) {
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    // Fee form toggle
    const hasFees = document.getElementById('has_fees');
    const feeForm = document.getElementById('feeForm');

    hasFees.addEventListener('change', toggleFeeForm);

    function toggleFeeForm() {
        feeForm.style.display = hasFees.checked ? 'block' : 'none';
    }

    // Initialize on page load
    if (hasFees.checked) {
        feeForm.style.display = 'block';
    }

    // Modal helpers
    function showPopup(id) {
        const popup = document.getElementById(id);
        popup.style.display = 'block';
        document.getElementById('overlay').classList.add('active');
        setTimeout(() => popup.classList.add('active'), 10);
    }

    function hidePopup(id) {
        const popup = document.getElementById(id);
        popup.classList.remove('active');
        setTimeout(() => {
            popup.style.display = 'none';
            document.getElementById('overlay').classList.remove('active');
        }, 200);
    }

    function showFeeReminder() {
        showPopup('feeReminderModal');
    }

    function closeFeeReminder() {
        hidePopup('feeReminderModal');
    }

    async function showConfirmModal() {
        const hasFeesChecked = hasFees.checked;
        const feeAmount = Number(document.getElementById('fee_amount')?.value || 0);
        const confirmed = await confirmJobAction({
            type: 'success',
            heading: 'Konfirmasi penyelesaian?',
            message: hasFeesChecked
                ? 'Pekerjaan akan diselesaikan dengan biaya tambahan. Invoice akan diedit frontdesk lalu disetujui manager.'
                : 'Pekerjaan akan diselesaikan tanpa biaya tambahan dan order lanjut ke tahap pembayaran.',
            confirmText: 'Ya, Selesaikan',
            details: [
                { label: 'Foto', value: `${selectedFiles.length} file` },
                { label: 'Biaya Tambahan', value: hasFeesChecked ? `Rp ${feeAmount.toLocaleString('id-ID')}` : 'Tidak ada' },
            ],
        });

        if (confirmed) {
            submitForm();
        }
    }

    // Form submission
    function handleSubmit() {
        // Validate photos
        if (selectedFiles.length === 0) {
            photoError.style.display = 'block';
            return;
        }

        // Show fee reminder if no fees reported
        if (!hasFees.checked) {
            showFeeReminder();
        } else {
            showConfirmModal();
        }
    }

    function submitForm() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-sync fa-spin"></i> Mengirim...';

        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        // Add photos
        selectedFiles.forEach(file => {
            formData.append('photos[]', file);
        });

        // Add other fields
        const completionNotes = document.getElementById('completion_notes').value;
        if (completionNotes) formData.append('completion_notes', completionNotes);

        formData.append('has_fees', hasFees.checked ? '1' : '0');

        if (hasFees.checked) {
            const feeDesc = document.getElementById('fee_description').value;
            const feeAmount = document.getElementById('fee_amount').value;
            const feeTools = document.getElementById('fee_tools_materials').value;

            if (feeDesc) formData.append('fee_description', feeDesc);
            if (feeAmount) formData.append('fee_amount', feeAmount);
            if (feeTools) formData.append('fee_tools_materials', feeTools);
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showJobMessage(data.message, 'success');
                window.location.href = '{{ route("technician.dashboard") }}';
            } else {
                showJobMessage(data.message || 'Terjadi kesalahan.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Selesaikan Pekerjaan';
            }
        })
        .catch(error => {
            showJobMessage('Terjadi kesalahan. Silakan coba lagi.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Selesaikan Pekerjaan';
            console.error('Error:', error);
        });
    }
</script>
@endpush
