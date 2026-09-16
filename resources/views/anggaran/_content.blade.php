<!-- Header -->
<div class="header">
    <div class="header-text">
        <h1>Anggaran</h1>
        <p>💡 Atur anggaran bulanan Anda agar keuangan lebih terkontrol!</p>
    </div>
    <button class="voice-btn voice-btn-header" onclick="startVoiceRecording()">
        <svg width="24" height="30" viewBox="0 0 38 48" fill="none">
            <path d="M38 20.8929C38 20.6571 37.7927 20.4643 37.5394 20.4643H34.0849C33.8315 20.4643 33.6242 20.6571 33.6242 20.8929C33.6242 28.4089 27.0779 34.5 19 34.5C10.9221 34.5 4.37576 28.4089 4.37576 20.8929C4.37576 20.6571 4.16849 20.4643 3.91515 20.4643H0.460606C0.207273 20.4643 0 20.6571 0 20.8929C0 29.9304 7.28909 37.3875 16.697 38.4429V43.9286H8.33121C7.54243 43.9286 6.90909 44.6946 6.90909 45.6429V47.5714C6.90909 47.8071 7.0703 48 7.26606 48H30.7339C30.9297 48 31.0909 47.8071 31.0909 47.5714V45.6429C31.0909 44.6946 30.4576 43.9286 29.6688 43.9286H21.0727V38.4696C30.59 37.5054 38 30.0054 38 20.8929ZM19 30C24.4064 30 28.7879 25.9714 28.7879 21V9C28.7879 4.02857 24.4064 0 19 0C13.5936 0 9.21212 4.02857 9.21212 9V21C9.21212 25.9714 13.5936 30 19 30Z" fill="white"/>
        </svg>
        <span class="voice-btn-text">Transaksi dengan Suara</span>
    </button>
</div>

<!-- Controls Section -->
<div class="controls-section">
    <div class="period-filter">
        <label>📅 Pilih Periode:</label>
        <input type="month" id="periodFilter" value="{{ $periode }}" onchange="window.location.href='?periode=' + this.value">
    </div>
    <button class="add-budget-btn" onclick="openAddModal()">+ Tambah Anggaran</button>
</div>

<!-- Budget Cards -->
@if(count($budgetsWithProgress) > 0)
    <div class="budget-grid" id="budgetGrid">
        @foreach($budgetsWithProgress as $index => $budget)
            <div class="budget-card" onclick="showHistory({{ $budget['id'] }})" style="cursor: pointer;">
                <div class="budget-header color-{{ ($index % 5) + 1 }}">
                    <div class="budget-icon">{{ $budget['icon'] }}</div>
                    <div class="budget-title">{{ $budget['namaBudget'] }}</div>
                    <div class="budget-actions">
                        <button class="budget-action-btn" onclick="event.stopPropagation(); openEditModal({{ json_encode($budget) }})" title="Edit Anggaran">✏️</button>
                        <button class="budget-action-btn" onclick="event.stopPropagation(); deleteBudget({{ $budget['id'] }})" title="Hapus Anggaran">🗑️</button>
                    </div>
                </div>
                <div class="budget-body">
                    <div class="budget-row">
                        <span class="budget-label">💸 Terpakai</span>
                        <span class="budget-value">{{ $budget['terpakai_formatted'] }}</span>
                    </div>
                    <div class="budget-row">
                        <span class="budget-label">💰 Anggaran</span>
                        <span class="budget-value">{{ $budget['jumlah_formatted'] }}</span>
                    </div>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ min($budget['persentase'], 100) }}%;"></div>
                        </div>
                        <div class="progress-info">
                            <span>{{ $budget['persentase'] }}% Terpakai</span>
                            <span>Sisa {{ $budget['sisa_formatted'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <div class="empty-state-icon">💰</div>
        <h3>Belum Ada Anggaran</h3>
        <p>Mulai atur keuangan Anda dengan menambahkan anggaran bulanan!</p>
        <button class="add-budget-btn" onclick="openAddModal()">+ Tambah Anggaran Pertama</button>
    </div>
@endif

<!-- Add/Edit Budget Modal -->
<div class="modal-overlay" id="budgetModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle" style="color: #00456A;">Tambah Anggaran Baru</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 25px;">Isi form di bawah ini untuk mengatur anggaran bulanan</p>
        
        <form id="budgetForm" onsubmit="saveBudget(event)">
            @csrf
            <input type="hidden" id="budgetId" value="">
            
            <div class="form-group">
                <label for="namaBudget" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    📝 Nama Anggaran <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Berikan nama yang mudah diingat</p>
                <input type="text" id="namaBudget" name="namaBudget" placeholder="Contoh: Belanja Bulanan, Uang Makan" required style="font-size: 16px; padding: 14px;">
            </div>

            <div class="form-group">
                <label for="kategori" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    🏷️ Kategori <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Pilih kategori yang sesuai (icon akan muncul otomatis)</p>
                <select id="kategori" name="kategori" required style="font-size: 16px; padding: 14px;">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Makanan">🍔 Makanan</option>
                    <option value="Transportasi">🚗 Transportasi</option>
                    <option value="Hiburan">🎬 Hiburan</option>
                    <option value="Belanja">🛍️ Belanja</option>
                    <option value="Jalan-Jalan">✈️ Jalan-Jalan</option>
                    <option value="Kesehatan">🏥 Kesehatan</option>
                    <option value="Pendidikan">📚 Pendidikan</option>
                    <option value="Tagihan">💳 Tagihan</option>
                    <option value="Lainnya">💰 Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    💵 Berapa Anggarannya? <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Total uang yang dialokasikan untuk kategori ini</p>
                <input type="text" id="jumlah" name="jumlah" placeholder="Contoh: 2.000.000" required style="font-size: 16px; padding: 14px;">
                <input type="hidden" id="jumlah_raw" name="jumlah_raw">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">💡 Angka akan otomatis diformat dengan pemisah ribuan</p>
            </div>

            <div class="form-group" id="periodeGroup">
                <label for="periode" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    📅 Untuk Bulan Apa? <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Pilih bulan dan tahun untuk anggaran ini</p>
                <input type="month" id="periode" name="periode" value="{{ $periode }}" required style="font-size: 16px; padding: 14px;">
            </div>

            <div class="modal-actions" style="margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()" style="font-size: 16px; padding: 14px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 14px;">💾 Simpan Anggaran</button>
            </div>
        </form>
    </div>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="historyModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="historyModalTitle">Riwayat Transaksi</h2>
            <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
        </div>
        <div class="history-list" id="historyList" style="max-height: 400px; overflow-y: auto;">
            <!-- Transaksi akan dimuat di sini -->
            <div style="text-align:center; padding:20px;">Memuat data...</div>
        </div>
    </div>
</div>

<style>
    /* Controls Section */
    .controls-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .period-filter {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .period-filter label {
        font-size: 16px;
        font-weight: 600;
        color: #2C3E50;
    }

    .period-filter input[type="month"] {
        padding: 10px 20px;
        border: 2px solid #00456A;
        border-radius: 10px;
        background: white;
        font-size: 16px;
        font-family: 'Inter', sans-serif;
        color: #2C3E50;
        cursor: pointer;
        min-width: 200px;
    }

    .add-budget-btn {
        background: rgba(0, 69, 106, 0.7);
        color: white;
        padding: 12px 30px;
        border-radius: 100px;
        border: 1px solid #00456A;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .add-budget-btn:hover {
        background: #00456A;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 69, 106, 0.3);
    }

    /* Budget Cards Grid */
    .budget-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .budget-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        border: 1px solid #5B9E9D;
    }

    .budget-header {
        padding: 15px 20px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .budget-header.color-1 { background: #6B9BD1; }
    .budget-header.color-2 { background: #D1786B; }
    .budget-header.color-3 { background: #D19E6B; }
    .budget-header.color-4 { background: #6BC1D1; }
    .budget-header.color-5 { background: #9E6BD1; }

    .budget-icon {
        width: 45px;
        height: 45px;
        background: #DDE6E6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .budget-title {
        flex: 1;
        text-align: center;
        font-size: 22px;
        font-weight: 600;
    }

    .budget-actions {
        display: flex;
        gap: 8px;
    }

    .budget-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        font-size: 16px;
    }

    .budget-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .budget-body {
        padding: 20px 25px;
    }

    .budget-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .budget-label, .budget-value {
        opacity: 0.7;
        font-weight: 600;
    }

    .budget-progress {
        margin-top: 20px;
    }

    .progress-bar {
        width: 100%;
        height: 15px;
        background: #E0E0E0;
        border-radius: 20px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: #6B9BD1;
        border-radius: 20px;
        transition: width 0.3s ease;
    }

    .progress-info {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 14px;
        opacity: 0.7;
        font-weight: 600;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        border: 2px dashed #00456A;
    }

    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #2C3E50;
        font-size: 24px;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #666;
        font-size: 16px;
        margin-bottom: 20px;
    }

    .helper-text {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .budget-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .controls-section {
            flex-direction: column;
            align-items: stretch;
            width: 100% !important;
            margin-bottom: 15px !important;
        }

        .period-filter {
            flex-direction: column;
            align-items: stretch;
            width: 100% !important;
        }

        .period-filter input {
            width: 100% !important;
            min-width: unset !important;
        }

        .add-budget-btn {
            width: 100% !important;
            text-align: center !important;
        }
        
        /* Budget Cards - Full Width */
        .budget-card {
            width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            border-radius: 12px !important;
        }
        
        .budget-header {
            padding: 12px 15px;
        }
        
        .budget-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }
        
        .budget-title {
            font-size: 18px;
        }
        
        .budget-action-btn {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }
        
        .budget-body {
            padding: 15px 20px;
        }
        
        .budget-row {
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .budget-label, .budget-value {
            font-size: 16px;
        }
        
        .progress-bar {
            height: 12px;
        }
        
        .progress-info {
            font-size: 13px;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        /* Modal Form Responsive */
        .modal-content {
            width: 95%;
            max-width: 95%;
            padding: 20px;
            padding-bottom: 80px; /* Extra space for bottom navbar */
            max-height: 85vh; /* Reduced to ensure scrollability */
            overflow-y: auto;
        }
        
        .modal-header h2 {
            font-size: 20px;
        }
        
        .modal-content p {
            font-size: 13px;
        }
        
        .form-group label {
            font-size: 15px !important;
        }
        
        .form-group input,
        .form-group select {
            font-size: 15px !important;
            padding: 12px !important;
        }
        
        .modal-actions {
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px; /* Extra margin at bottom */
        }
        
        .modal-actions .btn {
            width: 100%;
            font-size: 15px !important;
            padding: 12px !important;
        }
    }
</style>

<script>
    let isEditMode = false;

    function openAddModal() {
        isEditMode = false;
        document.getElementById('modalTitle').textContent = 'Tambah Anggaran Baru';
        document.getElementById('budgetForm').reset();
        document.getElementById('budgetId').value = '';
        document.getElementById('periodeGroup').style.display = 'block';
        document.getElementById('budgetModal').classList.add('active');
    }

    function openEditModal(budget) {
        isEditMode = true;
        document.getElementById('modalTitle').textContent = 'Edit Anggaran';
        document.getElementById('budgetId').value = budget.id;
        document.getElementById('namaBudget').value = budget.namaBudget;
        document.getElementById('kategori').value = budget.kategori;
        document.getElementById('jumlah').value = budget.jumlah;
        document.getElementById('periode').value = budget.periode;
        document.getElementById('periodeGroup').style.display = 'none';
        document.getElementById('budgetModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('budgetModal').classList.remove('active');
    }

    async function saveBudget(event) {
        event.preventDefault();

        const budgetId = document.getElementById('budgetId').value;
        const formData = {
            namaBudget: document.getElementById('namaBudget').value,
            kategori: document.getElementById('kategori').value,
            jumlah: parseFloat(document.getElementById('jumlah').value),
            periode: document.getElementById('periode').value,
        };

        try {
            const url = isEditMode ? `/api/budget/${budgetId}` : '/api/budget';
            const method = isEditMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                closeModal();
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: isEditMode ? 'Anggaran berhasil diperbarui.' : 'Anggaran baru berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: result.message || 'Gagal menyimpan anggaran',
                    icon: 'error',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat menyimpan anggaran',
                icon: 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
        }
    }

    async function deleteBudget(id) {
        const result = await Swal.fire({
            title: 'Hapus Anggaran?',
            text: 'Apakah Anda yakin ingin menghapus anggaran ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ED6363',
            cancelButtonColor: '#999',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/api/budget/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    title: 'Terhapus!',
                    text: 'Anggaran berhasil dihapus.',
                    icon: 'success',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.message || 'Gagal menghapus anggaran',
                    icon: 'error',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat menghapus anggaran',
                icon: 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
        }
    }


    // Close modal when clicking outside
    document.getElementById('budgetModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('historyModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeHistoryModal();
        }
    });

    // History Functions
    function showHistory(budgetId) {
        const modal = document.getElementById('historyModal');
        const list = document.getElementById('historyList');
        const title = document.getElementById('historyModalTitle');
        
        modal.classList.add('active');
        list.innerHTML = '<div style="text-align:center; padding:20px;">Memuat data...</div>';
        
        fetch(`/api/budget/${budgetId}/transactions`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    title.textContent = `Riwayat: ${data.budget_name}`;
                    if(data.transactions.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Belum ada transaksi untuk anggaran ini.</div>';
                    } else {
                        let html = '';
                        data.transactions.forEach(trx => {
                            const date = new Date(trx.tanggal).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                            const amount = new Intl.NumberFormat('id-ID').format(trx.jumlah);
                            html += `
                                <div class="transaction-item" style="margin-bottom:10px; border-left: 4px solid #ED6363; background: #FFF5F5; padding: 10px; border-radius: 5px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div class="transaction-name" style="font-weight:600;">${trx.keterangan || 'Pengeluaran'}</div>
                                        <div class="transaction-amount expense" style="color:#ED6363; font-weight:bold;">- Rp${amount}</div>
                                    </div>
                                    <div class="transaction-date" style="font-size:12px; color:#888; margin-top:4px;">${date}</div>
                                </div>
                            `;
                        });
                        list.innerHTML = html;
                    }
                } else {
                    list.innerHTML = '<div style="color:red; text-align:center;">Gagal memuat data.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                list.innerHTML = '<div style="color:red; text-align:center;">Terjadi kesalahan.</div>';
            });
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').classList.remove('active');
    }

    // ========== RUPIAH FORMATTING FUNCTIONS ==========
    
    function formatRupiah(angka) {
        if (!angka) return '';
        const number = typeof angka === 'string' ? parseFloat(angka.replace(/\./g, '')) : angka;
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function unformatRupiah(formatted) {
        if (!formatted) return 0;
        const cleaned = formatted.toString().replace(/\./g, '');
        return parseFloat(cleaned) || 0;
    }

    // Update saveBudget untuk parse formatted number
    const originalSaveBudget = saveBudget;
    saveBudget = async function(event) {
        event.preventDefault();

        const budgetId = document.getElementById('budgetId').value;
        const jumlahFormatted = document.getElementById('jumlah').value;
        const jumlahRaw = unformatRupiah(jumlahFormatted);
        
        // Validasi: jumlah tidak boleh 0 atau negatif
        if (!jumlahRaw || jumlahRaw <= 0) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Jumlah anggaran harus lebih dari 0!',
                icon: 'warning',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        const formData = {
            namaBudget: document.getElementById('namaBudget').value,
            kategori: document.getElementById('kategori').value,
            jumlah: jumlahRaw,
            periode: document.getElementById('periode').value,
        };

        try {
            const url = isEditMode ? `/api/budget/${budgetId}` : '/api/budget';
            const method = isEditMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                closeModal();
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: isEditMode ? 'Anggaran berhasil diperbarui.' : 'Anggaran baru berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: result.message || 'Gagal menyimpan anggaran',
                    icon: 'error',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat menyimpan anggaran',
                icon: 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
        }
    };

    // Update openEditModal untuk format jumlah
    const originalOpenEditModal = openEditModal;
    openEditModal = function(budget) {
        isEditMode = true;
        document.getElementById('modalTitle').textContent = 'Edit Anggaran';
        document.getElementById('budgetId').value = budget.id;
        document.getElementById('namaBudget').value = budget.namaBudget;
        document.getElementById('kategori').value = budget.kategori;
        document.getElementById('jumlah').value = formatRupiah(budget.jumlah);
        document.getElementById('periode').value = budget.periode;
        document.getElementById('periodeGroup').style.display = 'none';
        document.getElementById('budgetModal').classList.add('active');
    };

    // Event listener untuk auto-format
    document.addEventListener('DOMContentLoaded', function() {
        const jumlahInput = document.getElementById('jumlah');
        
        if (jumlahInput) {
            jumlahInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\./g, '');
                value = value.replace(/[^\d]/g, '');
                
                if (value) {
                    e.target.value = formatRupiah(value);
                    document.getElementById('jumlah_raw').value = value;
                } else {
                    e.target.value = '';
                    document.getElementById('jumlah_raw').value = '';
                }
            });
            
            jumlahInput.addEventListener('keypress', function(e) {
                if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }
                
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
