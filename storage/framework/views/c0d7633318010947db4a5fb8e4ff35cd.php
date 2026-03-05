

<?php $__env->startSection('title', 'Dashboard - AC Servis Masjid'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-th-large"></i> Dashboard</h1>
            <p class="page-subtitle">Selamat datang, <strong><?php echo e(auth()->user()->name); ?></strong></p>
        </div>
        <div class="page-actions">
            <?php if(auth()->user()->isFrontdesk()): ?>
            <button class="btn btn-primary" onclick="openPopup('addMasjidPopup')">
                <i class="fas fa-plus"></i> Tambah Masjid
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Role Badge -->
    <div class="role-info-bar">
        <i class="fas fa-user-shield"></i>
        Anda login sebagai: <strong><?php echo e(ucfirst(auth()->user()->role)); ?></strong>
        <?php if(auth()->user()->isManager()): ?>
        — Anda dapat menyetujui service order di halaman Monitoring
        <?php elseif(auth()->user()->isFrontdesk()): ?>
        — Anda dapat mengelola masjid dan membuat service order
        <?php else: ?>
        — Anda hanya dapat melihat data
        <?php endif; ?>
    </div>

    <!-- Search -->
    <div class="search-bar">
        <form action="<?php echo e(route('dashboard')); ?>" method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari ID atau nama masjid..." 
                       value="<?php echo e(request('search')); ?>" class="search-input">
                <?php if(request('search')): ?>
                    <a href="<?php echo e(route('dashboard')); ?>" class="search-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        <?php if(request('search')): ?>
            <p class="search-result-info">Menampilkan hasil untuk: <strong>"<?php echo e(request('search')); ?>"</strong> (<?php echo e($masjids->count()); ?> ditemukan)</p>
        <?php endif; ?>
    </div>

    <!-- Masjid Cards -->
    <div class="cards-grid" id="masjidGrid">
        <?php $__empty_1 = true; $__currentLoopData = $masjids; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $masjid): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $urgency = $masjid->urgency_status;
            $urgencyLabel = match($urgency) {
                'aman' => 'Aman',
                'harus_servis' => 'Harus Servis',
                'overdue' => 'Overdue',
                default => 'Belum Ada Data',
            };
        ?>
        <div class="masjid-card urgency-<?php echo e($masjid->urgency_status); ?>" data-id="<?php echo e($masjid->id); ?>">
            <div class="card-accent-bar"></div>
            <div class="card-top">
                <span class="card-type-chip <?php echo e($masjid->type); ?>">
                    <?php echo e($masjid->type === 'masjid' ? ' Masjid' : ' Musholla'); ?>

                </span>
                <span class="urgency-pill urgency-<?php echo e($masjid->urgency_status); ?>">
                    <span class="urgency-pulse"></span>
                    <?php echo e($urgencyLabel); ?>

                </span>
            </div>
            <div class="card-body">
                <div><span class="card-id"><?php echo e($masjid->custom_id); ?></span></div>
                <div class="card-name"><?php echo e($masjid->name); ?></div>
                <div class="card-address"><i class="fas fa-map-marker-alt"></i> <?php echo e(Str::limit($masjid->address, 65)); ?></div>
                <div class="card-phone"><i class="fas fa-phone"></i>
                    <?php if(is_array($masjid->phone_numbers)): ?>
                        <?php $__currentLoopData = $masjid->phone_numbers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $phone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="phone-number"><?php echo e($phone); ?></span><?php if(!$loop->last): ?>, <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php elseif(!empty($masjid->phone_numbers)): ?>
                        <span class="phone-number"><?php echo e($masjid->phone_numbers); ?></span>
                    <?php else: ?>
                        <span class="phone-number text-muted">Tidak ada nomor telepon</span>
                    <?php endif; ?>
                </div>
                <div class="card-stats">
                    <span class="card-stat"><i class="fas fa-user"></i> <?php echo e($masjid->dkm_name); ?></span>
                    <span class="card-stat"><i class="fas fa-snowflake"></i> <?php echo e($masjid->acUnits->sum('quantity')); ?> unit AC</span>
                </div>
                <?php
                    $activeOrder = $masjid->serviceOrders->where('status', 'pending')->first()
                        ?? $masjid->serviceOrders->where('status', 'approved')->first();
                ?>
                <?php if($activeOrder): ?>
                <span class="card-order-badge status-<?php echo e($activeOrder->status); ?>">
                    <i class="fas fa-circle-dot"></i>
                    <?php echo e(ucfirst($activeOrder->status)); ?> · <?php echo e($activeOrder->service_date->format('d M Y')); ?>

                </span>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-info" onclick="showDetail(<?php echo e($masjid->id); ?>)">
                    <i class="fas fa-eye"></i> Detail AC
                </button>
                <?php if(auth()->user()->isFrontdesk()): ?>
                <button class="btn btn-sm btn-warning" onclick="openEditAC(<?php echo e($masjid->id); ?>)">
                    <i class="fas fa-tools"></i> Kelola AC
                </button>
                <button class="btn btn-sm btn-secondary" onclick="openEditMasjid(<?php echo e($masjid->id); ?>, '<?php echo e(addslashes($masjid->name)); ?>', '<?php echo e(addslashes($masjid->address)); ?>', '<?php echo e(addslashes($masjid->dkm_name)); ?>', '<?php echo e(addslashes($masjid->marbot_name)); ?>', <?php echo e(json_encode($masjid->phone_numbers)); ?>)">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo e($masjid->id); ?>, '<?php echo e(addslashes($masjid->name)); ?>')">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-mosque"></i></div>
            <h3>Belum Ada Data Masjid</h3>
            <p><?php echo e(request('search') ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Mulai dengan menambahkan masjid pertama.'); ?></p>
            <?php if(auth()->user()->isFrontdesk() && !request('search')): ?>
            <button class="btn btn-primary" onclick="openPopup('addMasjidPopup')">
                <i class="fas fa-plus"></i> Tambah Masjid
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- =============== POPUPS =============== -->

<!-- Add Masjid Popup -->
<div class="popup popup-lg" id="addMasjidPopup">
    <div class="popup-header">
        <h3><i class="fas fa-mosque"></i> Daftarkan Masjid Baru</h3>
        <button class="popup-close" onclick="closePopup('addMasjidPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="addMasjidForm">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipe <span class="required">*</span></label>
                    <select name="type" id="masjidType" class="form-select" required>
                        <option value="masjid"> Masjid</option>
                        <option value="musholla"> Musholla</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="Nama masjid..." required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat <span class="required">*</span></label>
                <textarea name="address" class="form-textarea" placeholder="Alamat lengkap..." required rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama DKM <span class="required">*</span></label>
                    <input type="text" name="dkm_name" class="form-input" placeholder="Ketua DKM..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Marbot <span class="required">*</span></label>
                    <input type="text" name="marbot_name" class="form-input" placeholder="Nama marbot..." required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor HP <span class="required">*</span></label>
                <div id="phoneList">
                    <div class="phone-input-row">
                        <input type="text" name="phone_numbers[]" class="form-input" placeholder="+62..." required>
                        <button type="button" class="btn btn-sm btn-success" onclick="addPhoneField()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Daftarkan
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetMasjidForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add AC Popup (appears after add masjid) -->
<div class="popup popup-lg" id="addACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-snowflake"></i> Tambah Data AC</h3>
        <button class="popup-close" onclick="closePopup('addACPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            Masjid berhasil didaftarkan! Sekarang tambahkan data AC.
        </div>
        <input type="hidden" id="acMasjidId">
        <div id="acUnitsList">
            <!-- AC units will be added dynamically -->
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addACUnit()">
            <i class="fas fa-plus"></i> Tambah Unit AC
        </button>
        <div class="popup-actions" style="margin-top: 1rem">
            <button type="button" class="btn btn-primary" onclick="saveACUnits()">
                <i class="fas fa-save"></i> Konfirmasi
            </button>
            <button type="button" class="btn btn-secondary" onclick="closePopup('addACPopup'); location.reload()">
                Lewati
            </button>
        </div>
    </div>
</div>

<!-- Detail AC Popup -->
<div class="popup popup-lg" id="detailACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-list"></i> Detail Unit AC</h3>
        <button class="popup-close" onclick="closePopup('detailACPopup')">&times;</button>
    </div>
    <div class="popup-body" id="detailACBody">
        <!-- Dynamic content -->
    </div>
</div>

<!-- Edit Masjid Popup -->
<div class="popup popup-lg" id="editMasjidPopup">
    <div class="popup-header">
        <h3><i class="fas fa-edit"></i> Edit Data Masjid</h3>
        <button class="popup-close" onclick="closePopup('editMasjidPopup')">&times;</button>
    </div>
    <div class="popup-body">
        <form id="editMasjidForm">
            <input type="hidden" id="editMasjidId">
            <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" id="editMasjidName" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea id="editMasjidAddress" class="form-textarea" rows="2" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama DKM</label>
                    <input type="text" id="editMasjidDkm" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Marbot</label>
                    <input type="text" id="editMasjidMarbot" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor HP</label>
                <div id="editPhoneList"></div>
                <button type="button" class="btn btn-sm btn-success" onclick="addEditPhoneField()">
                    <i class="fas fa-plus"></i> Tambah Nomor
                </button>
            </div>
            <div class="popup-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closePopup('editMasjidPopup')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit AC Popup -->
<div class="popup popup-lg" id="editACPopup">
    <div class="popup-header">
        <h3><i class="fas fa-tools"></i> Kelola Unit AC</h3>
        <button class="popup-close" onclick="closePopup('editACPopup')">&times;</button>
    </div>
    <div class="popup-body" id="editACBody">
        <!-- Dynamic content -->
    </div>
</div>

<!-- Delete Confirm Popup -->
<div class="popup" id="deletePopup">
    <div class="popup-header">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus</h3>
        <button class="popup-close" onclick="closePopup('deletePopup')">&times;</button>
    </div>
    <div class="popup-body">
        <p>Anda yakin ingin menghapus <strong id="deleteName"></strong>?</p>
        <p class="text-danger text-sm">Semua data AC, Service Order, dan Invoice akan ikut terhapus.</p>
        <div class="popup-actions">
            <button class="btn btn-danger" id="deleteConfirmBtn">
                <i class="fas fa-trash"></i> Hapus
            </button>
            <button class="btn btn-secondary" onclick="closePopup('deletePopup')">Batal</button>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const ROUTES = {
    masjidStore: '<?php echo e(route("masjid.store")); ?>',
    masjidUpdate: (id) => `/masjid/${id}`,
    masjidDestroy: (id) => `/masjid/${id}`,
    masjidDetail: (id) => `/masjid/${id}`,
    acBulk: '<?php echo e(route("ac.bulk")); ?>',
    acUpdate: (id) => `/ac/${id}`,
    acDestroy: (id) => `/ac/${id}`,
};
const isFrontdesk = <?php echo e(auth()->user()->isFrontdesk() ? 'true' : 'false'); ?>;
</script>
<script src="<?php echo e(asset('js/dashboard.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hype G12\ac_beneran_final\resources\views/dashboard.blade.php ENDPATH**/ ?>