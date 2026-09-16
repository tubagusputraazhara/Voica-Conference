<!-- Header -->
<div class="header">
    <h1>Laporan Keuangan</h1>
    <p>📊 Pantau transaksi dan kelola keuangan Anda!</p>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-header">
        <div class="filter-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;">
                <path d="M19 4H5C3.89 4 3 4.9 3 6V8C3 8.55 3.45 9 4 9H20C20.55 9 21 8.55 21 8V6C21 4.9 20.11 4 19 4ZM19 20H5C3.89 20 3 19.1 3 18V16C3 15.45 3.45 15 4 15H20C20.55 15 21 15.45 21 16V18C21 19.1 20.11 20 19 20Z" fill="#2C3E50" />
            </svg>
            Pilih Periode
        </div>
        <button class="btn-download" id="btnDownloadPdf">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 6px;">
                <path d="M19 9H15V3H9V9H5L12 16L19 9ZM5 18V20H19V18H5Z" fill="white" />
            </svg>
            Unduh Laporan
        </button>
    </div>

    <div class="filter-controls">
        <div class="filter-label">Dari</div>

        <select class="filter-select" id="bulanDari">
            <option value="">Bulan</option>
            <option value="1">Januari</option>
            <option value="2">Februari</option>
            <option value="3">Maret</option>
            <option value="4">April</option>
            <option value="5">Mei</option>
            <option value="6">Juni</option>
            <option value="7">Juli</option>
            <option value="8">Agustus</option>
            <option value="9">September</option>
            <option value="10">Oktober</option>
            <option value="11">November</option>
            <option value="12">Desember</option>
        </select>

        <select class="filter-select" id="tahunDari">
            <option value="">Tahun</option>
            @foreach($years as $year)
            <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>

        <div class="filter-separator">-</div>

        <div class="filter-label">Sampai</div>

        <select class="filter-select" id="bulanSampai">
            <option value="">Bulan</option>
            <option value="1">Januari</option>
            <option value="2">Februari</option>
            <option value="3">Maret</option>
            <option value="4">April</option>
            <option value="5">Mei</option>
            <option value="6">Juni</option>
            <option value="7">Juli</option>
            <option value="8">Agustus</option>
            <option value="9">September</option>
            <option value="10">Oktober</option>
            <option value="11">November</option>
            <option value="12">Desember</option>
        </select>

        <select class="filter-select" id="tahunSampai">
            <option value="">Tahun</option>
            @foreach($years as $year)
            <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>

        <button class="btn-search" id="btnCari">Cari</button>
    </div>
</div>

<!-- Summary Cards (Outside table section) -->
<div id="summaryCards" class="summary-cards" style="display: none;">
    <div class="summary-card pemasukan">
        <div class="summary-label">Total Pemasukan</div>
        <div class="summary-value" id="totalPemasukanCard">0</div>
    </div>
    <div class="summary-card pengeluaran">
        <div class="summary-label">Total Pengeluaran</div>
        <div class="summary-value" id="totalPengeluaranCard">0</div>
    </div>
    <div class="summary-card saldo">
        <div class="summary-label">Saldo Akhir</div>
        <div class="summary-value" id="saldoAkhirCard">0</div>
    </div>
</div>

<!-- Table Section (Scrollable) -->
<div class="table-section">
    <div id="periodInfo" style="margin-bottom: 15px; font-size: 16px; font-weight: 600; color: #00456A; display: none;">
        Periode: <span id="periodText"></span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Tanggal</th>
                    <th style="text-align: left;">Kategori</th>
                    <th style="text-align: left;">Nominal</th>
                    <th style="text-align: left;">Saldo</th>
                    <th style="text-align: left;">Catatan</th>
                    <th style="text-align: left;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                        Pilih periode dan klik "Cari" untuk menampilkan data
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal-overlay" id="editModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Transaksi</h2>
            <button class="close-btn" onclick="closeEditModal()">&times;</button>
        </div>

        <form id="editForm" onsubmit="saveEdit(event)">
            <input type="hidden" id="editId">

            <div class="form-group">
                <label for="editTanggal">Tanggal *</label>
                <input type="text" id="editTanggal" placeholder="DD/MM/YYYY" required pattern="\d{2}/\d{2}/\d{4}">
                <input type="hidden" id="editTanggalISO">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">Format: DD/MM/YYYY (contoh: 15/12/2024)</p>
            </div>

            <div class="form-group">
                <label for="editJenis">Jenis Transaksi *</label>
                <select id="editJenis" required>
                    <option value="Pemasukan">Pemasukan</option>
                    <option value="Pengeluaran">Pengeluaran</option>
                </select>
            </div>

            <div class="form-group">
                <label for="editKategori">Kategori *</label>
                <select id="editKategori" required>
                    <option value="Makanan">Makanan</option>
                    <option value="Transportasi">Transportasi</option>
                    <option value="Hiburan">Hiburan</option>
                    <option value="Kebutuhan">Kebutuhan</option>
                    <option value="Belanja">Belanja</option>
                    <option value="Kesehatan">Kesehatan</option>
                    <option value="Pendidikan">Pendidikan</option>
                    <option value="Gaji">Gaji</option>
                    <option value="Bonus">Bonus</option>
                    <option value="Investasi">Investasi</option>
                    <option value="Tabungan">Tabungan</option>
                    <option value="Sedekah">Sedekah</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="editJumlah">Jumlah (Rp) *</label>
                <input type="number" id="editJumlah" min="0" step="1000" required>
            </div>

            <div class="form-group">
                <label for="editKeterangan">Keterangan *</label>
                <textarea id="editKeterangan" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="editBudget">Anggaran (Opsional)</label>
                <select id="editBudget">
                    <option value="">-- Tidak Ada --</option>
                </select>
                <p style="font-size: 12px; color: #999; margin-top: 5px;">Alokasikan ke anggaran tertentu</p>
            </div>

            <div class="form-group">
                <label for="editGoal">Target (Opsional)</label>
                <select id="editGoal">
                    <option value="">-- Tidak Ada --</option>
                </select>
                <p style="font-size: 12px; color: #999; margin-top: 5px;">Alokasikan ke target tertentu</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>


<style>
    /* Header */
    .header h1 {
        color: #2C3E50;
        font-size: clamp(24px, 4vw, 40px);
        font-weight: 700;
        margin-bottom: 5px;
    }

    .header p {
        color: #2C3E50;
        font-size: clamp(14px, 2vw, 20px);
    }

    /* Filter Section */
    .filter-section {
        background: white;
        padding: 20px 30px;
        border-radius: 10px;
        border: 1px solid #00456A;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        margin-bottom: 25px;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .filter-title {
        display: flex;
        align-items: center;
        color: #2C3E50;
        font-size: 20px;
        font-weight: 600;
    }

    .filter-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-label {
        color: #676363;
        font-size: 16px;
        font-weight: 500;
        flex-shrink: 0;
    }

    .filter-separator {
        color: #676363;
        font-size: 20px;
        font-weight: 500;
        padding: 0 5px;
        flex-shrink: 0;
    }

    .filter-select {
        padding: 10px 20px;
        border: 2px solid #E3F5FF;
        border-radius: 10px;
        background: #E3E2E2;
        font-size: 16px;
        font-family: 'Inter', sans-serif;
        color: #676363;
        flex: 1;
        min-width: 120px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .filter-select:focus {
        outline: none;
        border-color: #00456A;
        background: white;
    }

    .btn-search {
        background: #557F96;
        color: white;
        padding: 10px 30px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #00456A80;
        flex-shrink: 0;
    }

    .btn-search:hover {
        background: #00456A;
        transform: translateY(-2px);
    }

    .btn-download {
        background: #557F96;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #00456A80;
        display: flex;
        align-items: center;
    }

    .btn-download:hover {
        background: #00456A;
        transform: translateY(-2px);
    }

    /* Summary Cards (Outside table section) */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
        /* Space between cards and table */
    }

    .summary-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        border: 2px solid;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .summary-card.pemasukan {
        border-color: #00A311;
        background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    }

    .summary-card.pengeluaran {
        border-color: #ED6363;
        background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
    }

    .summary-card.saldo {
        border-color: #00456A;
        background: linear-gradient(135deg, #e3f5ff 0%, #ffffff 100%);
    }

    .summary-label {
        font-size: 14px;
        font-weight: 600;
        color: #676363;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 24px;
        font-weight: 800;
        font-family: 'Inter', sans-serif;
    }

    .summary-card.pemasukan .summary-value {
        color: #00A311;
    }

    .summary-card.pengeluaran .summary-value {
        color: #ED6363;
    }

    .summary-card.saldo .summary-value {
        color: #00456A;
    }

    /* Table Section */
    .table-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        border: 1px solid #00456A;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
    }

    /* Table Wrapper - Scrollable */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        /* Smooth scroll on iOS */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }

    thead {
        background: #00456A;
        color: white;
    }

    th {
        padding: 15px 20px;
        text-align: left;
        font-size: 16px;
        font-weight: 600;
        white-space: nowrap;
    }

    td {
        padding: 15px 20px;
        text-align: left;
        font-size: 16px;
        border-bottom: 1px solid #E3F5FF;
        vertical-align: middle;
    }

    /* Nominal and Saldo columns - monospace font for better readability */
    td:nth-child(3),
    td:nth-child(4) {
        font-family: 'Consolas', 'Monaco', monospace;
        font-weight: 600;
    }

    tbody tr:hover {
        background: #F8FCFF;
    }

    /* Action Buttons */
    .btn-action {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 5px 8px;
        border-radius: 5px;
        transition: all 0.2s;
        margin: 0 3px;
    }

    .btn-action:hover {
        transform: scale(1.2);
    }

    .btn-edit:hover {
        background: #E3F5FF;
    }

    .btn-delete:hover {
        background: #FFE4E4;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h2 {
        color: #00456A;
        font-size: 24px;
        font-weight: 700;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 30px;
        cursor: pointer;
        color: #999;
        line-height: 1;
    }

    .close-btn:hover {
        color: #ED6363;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-actions .btn {
        flex: 1;
    }

    @media (max-width: 768px) {
        .summary-cards {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .summary-value {
            font-size: 20px;
        }

        .filter-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-select {
            width: 100%;
        }

        .btn-search,
        .btn-download {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    const API_URL = '{{ route("laporan.transactions") }}';
    const PDF_URL = '{{ route("laporan.export.pdf.secure") }}';

    document.getElementById('btnCari').addEventListener('click', fetchTransactions);
    document.getElementById('btnDownloadPdf').addEventListener('click', downloadPdf);

    async function fetchTransactions() {
        const bulanDari = document.getElementById('bulanDari').value;
        const tahunDari = document.getElementById('tahunDari').value;
        const bulanSampai = document.getElementById('bulanSampai').value;
        const tahunSampai = document.getElementById('tahunSampai').value;

        if (!bulanDari || !tahunDari || !bulanSampai || !tahunSampai) {
            Swal.fire({
                icon: 'warning',
                title: 'Periode Belum Lengkap',
                text: 'Mohon lengkapi semua filter periode',
                confirmButtonColor: '#00456A'
            });
            return;
        }

        // Validate period: "Sampai" must be >= "Dari"
        const dateFrom = new Date(tahunDari, bulanDari - 1, 1);
        const dateTo = new Date(tahunSampai, bulanSampai - 1, 1);

        if (dateTo < dateFrom) {
            Swal.fire({
                icon: 'error',
                title: 'Periode Tidak Valid',
                html: `
                    <p style="font-size: 16px; color: #2C3E50; margin-bottom: 10px;">
                        Periode <strong>"Sampai"</strong> tidak boleh lebih awal dari periode <strong>"Dari"</strong>
                    </p>
                    <p style="font-size: 14px; color: #676363;">
                        Contoh yang benar:<br>
                        <strong>Dari:</strong> Desember 2025<br>
                        <strong>Sampai:</strong> Januari 2026 atau lebih
                    </p>
                `,
                confirmButtonColor: '#00456A',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        try {
            const response = await fetch(`${API_URL}?bulan_dari=${bulanDari}&tahun_dari=${tahunDari}&bulan_sampai=${bulanSampai}&tahun_sampai=${tahunSampai}`);
            const data = await response.json();

            if (data.success) {
                renderTable(data.transactions, data.summary);
                showPeriodInfo(bulanDari, tahunDari, bulanSampai, tahunSampai);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengambil Data',
                    text: 'Terjadi kesalahan saat mengambil data transaksi',
                    confirmButtonColor: '#00456A'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                confirmButtonColor: '#00456A'
            });
        }
    }

    function renderTable(transactions, summary) {
        const tbody = document.getElementById('tableBody');
        const summaryCards = document.getElementById('summaryCards');

        if (transactions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">Tidak ada data untuk periode ini</td></tr>';
            summaryCards.style.display = 'none';
            return;
        }

        let html = '';
        let saldo = 0;

        transactions.forEach(trx => {
            const nominal = parseFloat(trx.jumlah);
            saldo += trx.jenis === 'Pemasukan' ? nominal : -nominal;

            html += `
                <tr>
                    <td>${formatDate(trx.tanggal)}</td>
                    <td>${trx.kategori}</td>
                    <td style="color: ${trx.jenis === 'Pemasukan' ? '#00A311' : '#ED6363'}">
                        ${trx.jenis === 'Pemasukan' ? '+' : '-'} ${formatNumber(nominal)}
                    </td>
                    <td>${formatNumber(saldo)}</td>
                    <td>${trx.keterangan}</td>
                    <td>
                        <button class="btn-action btn-edit" onclick="editTransaction(${trx.id}, '${trx.jenis}', '${trx.kategori}', ${nominal}, '${trx.keterangan}', '${trx.tanggal}')" title="Edit">
                            ✏️
                        </button>
                        
                        <!-- TOMBOL HAPUS DISEMBUNYIKAN - Uncomment untuk mengaktifkan kembali
                        <button class="btn-action btn-delete" onclick="deleteTransaction(${trx.id})" title="Hapus">
                            🗑️
                        </button>
                        -->
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        // Update summary cards (with Rp prefix)
        document.getElementById('totalPemasukanCard').textContent = 'Rp ' + formatNumber(summary.total_pemasukan);
        document.getElementById('totalPengeluaranCard').textContent = 'Rp ' + formatNumber(summary.total_pengeluaran);
        document.getElementById('saldoAkhirCard').textContent = 'Rp ' + formatNumber(summary.saldo_akhir);
        summaryCards.style.display = 'grid';
    }

    function showPeriodInfo(bulanDari, tahunDari, bulanSampai, tahunSampai) {
        const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const periodText = `${months[parseInt(bulanDari)]} ${tahunDari} - ${months[parseInt(bulanSampai)]} ${tahunSampai}`;
        document.getElementById('periodText').textContent = periodText;
        document.getElementById('periodInfo').style.display = 'block';
    }

    function downloadPdf() {
        const bulanDari = document.getElementById('bulanDari').value;
        const tahunDari = document.getElementById('tahunDari').value;
        const bulanSampai = document.getElementById('bulanSampai').value;
        const tahunSampai = document.getElementById('tahunSampai').value;

        if (!bulanDari || !tahunDari || !bulanSampai || !tahunSampai) {
            Swal.fire({
                icon: 'warning',
                title: 'Periode Belum Lengkap',
                text: 'Mohon pilih periode terlebih dahulu',
                confirmButtonColor: '#00456A'
            });
            return;
        }

        // Validate period: "Sampai" must be >= "Dari"
        const dateFrom = new Date(tahunDari, bulanDari - 1, 1);
        const dateTo = new Date(tahunSampai, bulanSampai - 1, 1);

        if (dateTo < dateFrom) {
            Swal.fire({
                icon: 'error',
                title: 'Periode Tidak Valid',
                html: `
                    <p style="font-size: 16px; color: #2C3E50; margin-bottom: 10px;">
                        Periode <strong>"Sampai"</strong> tidak boleh lebih awal dari periode <strong>"Dari"</strong>
                    </p>
                    <p style="font-size: 14px; color: #676363;">
                        Silakan perbaiki periode sebelum mengunduh laporan
                    </p>
                `,
                confirmButtonColor: '#00456A',
                confirmButtonText: 'Mengerti'
            });
            return;
        }

        window.open(`${PDF_URL}?bulan_dari=${bulanDari}&tahun_dari=${tahunDari}&bulan_sampai=${bulanSampai}&tahun_sampai=${tahunSampai}`, '_blank');
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Edit Transaction
    async function editTransaction(id, jenis, kategori, jumlah, keterangan, tanggal) {
        document.getElementById('editId').value = id;

        // Convert tanggal dari YYYY-MM-DD ke DD/MM/YYYY untuk display
        if (tanggal) {
            const dateObj = new Date(tanggal);
            const day = String(dateObj.getDate()).padStart(2, '0');
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            const year = dateObj.getFullYear();

            // Set format DD/MM/YYYY untuk display
            document.getElementById('editTanggal').value = `${day}/${month}/${year}`;
            // Simpan format ISO di hidden field untuk backend
            document.getElementById('editTanggalISO').value = `${year}-${month}-${day}`;
        }

        document.getElementById('editJenis').value = jenis;
        document.getElementById('editKategori').value = kategori;
        document.getElementById('editJumlah').value = jumlah;
        document.getElementById('editKeterangan').value = keterangan;

        // Load budgets and goals
        await loadEditDropdowns(id);

        document.getElementById('editModal').style.display = 'flex';
    }

    // Load budgets and goals for edit modal
    async function loadEditDropdowns(transactionId) {
        try {
            // Fetch Budgets
            const budgetResponse = await fetch('/api/budgets');
            const budgetData = await budgetResponse.json();
            if (budgetData.success) {
                const budgetSelect = document.getElementById('editBudget');
                budgetSelect.innerHTML = '<option value="">-- Tidak Ada --</option>';
                budgetData.data.forEach(b => {
                    budgetSelect.innerHTML += `<option value="${b.id}">${b.namaBudget}</option>`;
                });
            }

            // Fetch Goals
            const goalResponse = await fetch('/api/goals');
            const goalData = await goalResponse.json();
            if (goalData.success) {
                const goalSelect = document.getElementById('editGoal');
                goalSelect.innerHTML = '<option value="">-- Tidak Ada --</option>';
                goalData.data.forEach(g => {
                    goalSelect.innerHTML += `<option value="${g.id}">${g.namaGoal}</option>`;
                });
            }
        } catch (e) {
            console.error('Error loading dropdowns:', e);
            // Jika error, tetap tampilkan modal tapi dropdown kosong
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    async function saveEdit(event) {
        event.preventDefault();

        const id = document.getElementById('editId').value;

        // Convert tanggal dari DD/MM/YYYY ke YYYY-MM-DD untuk backend
        const tanggalDisplay = document.getElementById('editTanggal').value;
        let tanggalISO = document.getElementById('editTanggalISO').value;

        // Jika user ubah tanggal, parse dari format DD/MM/YYYY
        if (tanggalDisplay) {
            const parts = tanggalDisplay.split('/');
            if (parts.length === 3) {
                const day = parts[0];
                const month = parts[1];
                const year = parts[2];
                tanggalISO = `${year}-${month}-${day}`;
            }
        }

        const data = {
            tanggal: tanggalISO,
            jenis: document.getElementById('editJenis').value,
            kategori: document.getElementById('editKategori').value,
            jumlah: parseFloat(document.getElementById('editJumlah').value),
            keterangan: document.getElementById('editKeterangan').value,
            budget_id: document.getElementById('editBudget').value || null,
            goal_id: document.getElementById('editGoal').value || null
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/api/transactions/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                closeEditModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Transaksi berhasil diperbarui',
                    confirmButtonColor: '#00456A'
                }).then(() => {
                    // Reload data
                    document.getElementById('btnCari').click();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: result.message || 'Gagal memperbarui transaksi',
                    confirmButtonColor: '#00456A'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat memperbarui transaksi',
                confirmButtonColor: '#00456A'
            });
        }
    }

    // Delete Transaction
    async function deleteTransaction(id) {
        const result = await Swal.fire({
            title: 'Hapus Transaksi?',
            text: 'Transaksi yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ED6363',
            cancelButtonColor: '#999',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/api/transactions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: 'Transaksi berhasil dihapus',
                    confirmButtonColor: '#00456A'
                }).then(() => {
                    // Reload data
                    document.getElementById('btnCari').click();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: data.message || 'Gagal menghapus transaksi',
                    confirmButtonColor: '#00456A'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan saat menghapus transaksi',
                confirmButtonColor: '#00456A'
            });
        }
    }
</script>