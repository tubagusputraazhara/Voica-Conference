@php
    // Gunakan data goals dari controller, atau dummy data jika belum ada
    $goalsData = $goals ?? collect([
        (object)['id' => 1, 'namaGoal' => 'Dana Darurat', 'tanggalTarget' => '100 hari lagi', 'nominalBerjalan' => 500000, 'targetNominal' => 1000000],
        (object)['id' => 2, 'namaGoal' => 'Haji dan Umroh', 'tanggalTarget' => '5 tahun lagi', 'nominalBerjalan' => 500000, 'targetNominal' => 1000000],
    ]);
@endphp

<!-- Goals Header -->
<div class="header">
    <div class="header-text">
        <h1>Target Keuangan</h1>
        <p>🎯 Wujudkan impianmu satu per satu!</p>
    </div>
    <button class="voice-btn voice-btn-header" onclick="startVoiceRecording()">
        <svg width="24" height="30" viewBox="0 0 38 48" fill="none">
            <path d="M38 20.8929C38 20.6571 37.7927 20.4643 37.5394 20.4643H34.0849C33.8315 20.4643 33.6242 20.6571 33.6242 20.8929C33.6242 28.4089 27.0779 34.5 19 34.5C10.9221 34.5 4.37576 28.4089 4.37576 20.8929C4.37576 20.6571 4.16849 20.4643 3.91515 20.4643H0.460606C0.207273 20.4643 0 20.6571 0 20.8929C0 29.9304 7.28909 37.3875 16.697 38.4429V43.9286H8.33121C7.54243 43.9286 6.90909 44.6946 6.90909 45.6429V47.5714C6.90909 47.8071 7.0703 48 7.26606 48H30.7339C30.9297 48 31.0909 47.8071 31.0909 47.5714V45.6429C31.0909 44.6946 30.4576 43.9286 29.6688 43.9286H21.0727V38.4696C30.59 37.5054 38 30.0054 38 20.8929ZM19 30C24.4064 30 28.7879 25.9714 28.7879 21V9C28.7879 4.02857 24.4064 0 19 0C13.5936 0 9.21212 4.02857 9.21212 9V21C9.21212 25.9714 13.5936 30 19 30Z" fill="white"/>
        </svg>
        <span class="voice-btn-text">Transaksi dengan Suara</span>
    </button>
</div>

<!-- Add Goal Button (Controls Section) -->
<div class="goals-actions">
    <button class="btn-add-goal" onclick="openGoalModal()">+ Tambah Target</button>
</div>

<!-- Goals List -->
<div class="goals-list">
    @forelse($goalsData as $index => $goal)
        <div class="goal-card" data-goal-id="{{ $goal->id }}" onclick="showHistory({{ $goal->id }})" style="cursor: pointer;">
            <div class="goal-header color-{{ ($index % 5) + 1 }}">
                <div class="goal-icon">🎯</div>
                <div class="goal-title">
                    <h3>{{ $goal->namaGoal }}</h3>
                    @php
                        $targetDate = \Carbon\Carbon::parse($goal->tanggalTarget);
                        $daysText = $targetDate->isPast() ? 'Tercapai / Lewat' : $targetDate->diffForHumans(now(), true) . ' lagi';
                    @endphp
                    <p class="goal-days">{{ $daysText }} ({{ $targetDate->format('d M Y') }})</p>
                </div>
                <div class="goal-actions">
                    <button class="goal-btn-edit" title="Edit" onclick="event.stopPropagation(); editGoal({{ $goal->id }}, '{{ $goal->namaGoal }}', {{ $goal->targetNominal }}, {{ $goal->nominalBerjalan }}, '{{ $goal->tanggalTarget }}')">✏️</button>
                    <button class="goal-btn-delete" title="Delete" onclick="event.stopPropagation(); deleteGoal({{ $goal->id }})">🗑️</button>
                </div>
            </div>
            <div class="goal-body">
                <div class="goal-row">
                    <span class="goal-label">Terkumpul</span>
                    <span class="goal-amount">Rp{{ number_format($goal->nominalBerjalan, 0, ',', '.') }}</span>
                </div>
                <div class="goal-row">
                    <span class="goal-label">Target</span>
                    <span class="goal-amount">Rp{{ number_format($goal->targetNominal, 0, ',', '.') }}</span>
                </div>
                <div class="goal-progress">
                    @php
                        $percentage = $goal->targetNominal > 0 ? round(($goal->nominalBerjalan / $goal->targetNominal) * 100) : 0;
                        $sisa = $goal->targetNominal - $goal->nominalBerjalan;
                    @endphp
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ min($percentage, 100) }}%;"></div>
                    </div>
                    <div class="progress-info">
                        <span>{{ $percentage }}% Terkumpul</span>
                        <span>Sisa Rp{{ number_format(max($sisa, 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">🎯</div>
            <h3>Belum Ada Goals</h3>
            <p>Mulai atur target keuangan Anda untuk masa depan yang lebih baik!</p>
            <button class="btn-add-goal" style="margin-top: 10px;" onclick="openGoalModal()">+ Tambah Target Pertama</button>
        </div>
    @endforelse
</div>

<!-- Goal Form (Modal-like) - Add & Edit -->
<div id="goal-form-container" class="goal-form-container" style="display: none;">
    <div class="goal-form">
        <h3 id="form-title" style="text-align: center; color: #00456A; margin-bottom: 10px;">Tambah Target Baru</h3>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 25px;">Isi form di bawah ini untuk membuat target tabungan</p>
        
        <form id="goal-form" method="POST" action="{{ route('goals.store') }}">
            @csrf
            <input type="hidden" id="form-mode" value="add">
            <input type="hidden" id="form-goal-id">
            
            <div class="form-group">
                <label for="namaGoal" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    📝 Nama Target <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Apa yang ingin Anda capai?</p>
                <input type="text" id="namaGoal" name="namaGoal" placeholder="Contoh: Dana Darurat, Umroh, Beli Motor" required style="font-size: 16px; padding: 14px;">
            </div>

            <div class="form-group">
                <label for="targetNominal" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    💰 Berapa Target Uang? <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Total uang yang ingin dikumpulkan</p>
                <input type="text" id="targetNominal" name="targetNominal" placeholder="Contoh: 10.000.000" required style="font-size: 16px; padding: 14px;">
                <input type="hidden" id="targetNominal_raw" name="targetNominal_raw">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">💡 Angka akan otomatis diformat dengan pemisah ribuan</p>
            </div>

            <div class="form-group">
                <label for="nominalBerjalan" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    💵 Sudah Terkumpul Berapa? (Opsional)
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Jika sudah ada tabungan, isi di sini. Jika belum, kosongkan saja</p>
                <input type="text" id="nominalBerjalan" name="nominalBerjalan" placeholder="Contoh: 5.000.000 (atau kosongkan)" style="font-size: 16px; padding: 14px;">
                <input type="hidden" id="nominalBerjalan_raw" name="nominalBerjalan_raw">
            </div>

            <div class="form-group">
                <label for="tanggalTarget" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    📅 Kapan Target Tercapai? <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Pilih tanggal target Anda</p>
                <input type="date" id="tanggalTarget" name="tanggalTarget" required style="font-size: 16px; padding: 14px;">
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <button type="button" class="btn-cancel" id="form-cancel-btn" style="font-size: 16px; padding: 14px;">Batal</button>
                <button type="submit" class="btn-save" id="form-submit-btn" style="font-size: 16px; padding: 14px;">💾 Simpan</button>
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

<!-- Voice Modal, Loading, Toast removed (moved to layout) -->

<style>
    .goals-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 25px;
    }

    .btn-add-goal {
        background: rgba(0, 69, 106, 0.7);
        color: white;
        padding: 12px 28px;
        border-radius: 100px;
        border: 1px solid #00456A;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-add-goal:hover {
        background: #00456A;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 69, 106, 0.3);
    }

    .goals-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .goal-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        border: 1px solid #5B9E9D;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .goal-card:hover {
        transform: translateY(-2px);
        box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.25);
    }

    .goal-header {
        padding: 15px 20px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .goal-header.color-1 { background: #6B9BD1; }
    .goal-header.color-2 { background: #D1786B; }
    .goal-header.color-3 { background: #D19E6B; }
    .goal-header.color-4 { background: #6BC1D1; }
    .goal-header.color-5 { background: #9E6BD1; }

    .goal-icon {
        width: 45px;
        height: 45px;
        background: #DDE6E6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .goal-title {
        flex: 1;
        text-align: center;
        padding: 0 10px;
    }

    .goal-title h3 {
        font-size: 22px;
        font-weight: 600;
        margin: 0;
    }

    .goal-days {
        font-size: 13px;
        opacity: 0.9;
        margin: 2px 0 0 0;
        font-weight: 400;
    }

    .goal-actions {
        display: flex;
        gap: 8px;
    }

    .goal-btn-edit,
    .goal-btn-delete {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .goal-btn-edit:hover,
    .goal-btn-delete:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .goal-body {
        padding: 20px 25px;
    }

    .goal-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .goal-label, .goal-amount {
        opacity: 0.7;
        font-weight: 600;
    }

    .goal-progress {
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

    .goal-form-container {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .goal-form {
        background: white;
        padding: 30px;
        padding-bottom: 80px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
    }

    .goal-form h3 {
        font-size: 24px;
        font-weight: 700;
        color: #2C3E50;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #DDD;
        border-radius: 8px;
        font-size: 16px;
        font-family: 'Inter', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: #2A8576;
        box-shadow: 0 0 5px rgba(42, 133, 118, 0.3);
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-save,
    .btn-cancel {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 100px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-save {
        background: #00456A;
        color: white;
    }

    .btn-save:hover {
        background: #003855;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 69, 106, 0.3);
    }

    .btn-save:disabled {
        background: #999;
        cursor: not-allowed;
    }

    .btn-cancel {
        background: #E3F5FF;
        color: #00456A;
        border: 1px solid #00456A;
    }

    .btn-cancel:hover {
        background: #D0E9F5;
    }

    @media (max-width: 768px) {
        /* Goals Actions */
        .goals-actions {
            width: 100% !important;
            margin-bottom: 15px !important;
        }

        .btn-add-goal {
            width: 100% !important;
            text-align: center !important;
        }

        /* Goals List Responsive */
        .goals-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .goal-card {
            width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            border-radius: 12px !important;
        }
        
        .goal-header {
            padding: 12px 15px;
        }
        
        .goal-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }
        
        .goal-title h3 {
            font-size: 18px;
        }
        
        .goal-days {
            font-size: 12px;
        }
        
        .goal-btn-edit,
        .goal-btn-delete {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }
        
        .goal-body {
            padding: 15px 20px;
        }
        
        .goal-row {
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .goal-label, .goal-amount {
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

        .goal-form {
            max-width: 100%;
            width: 95%;
            padding: 20px;
            padding-bottom: 80px; /* Extra space for bottom navbar */
            max-height: 85vh; /* Reduced to ensure scrollability */
            overflow-y: auto;
        }
        
        .goal-form h3 {
            font-size: 20px;
        }
        
        .goal-form p {
            font-size: 13px;
        }
        
        .goal-form label {
            font-size: 15px !important;
        }
        
        .goal-form input {
            font-size: 15px !important;
            padding: 12px !important;
        }
        
        .goal-form .form-actions {
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px; /* Extra margin at bottom */
        }
        
        .goal-form .btn-save,
        .goal-form .btn-cancel {
            width: 100%;
            font-size: 15px !important;
            padding: 12px !important;
        }
    }
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        border: 2px dashed #00456A;
        margin-top: 20px;
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
</style>

<script>
    // ========== UNIFIED GOAL FORM (ADD & EDIT) ==========
    // Get form elements (they persist in partial)
    const goalForm = document.getElementById('goal-form');
    const goalFormContainer = document.getElementById('goal-form-container');
    const formMode = document.getElementById('form-mode');
    const formGoalId = document.getElementById('form-goal-id');
    const formTitle = document.getElementById('form-title');
    const formSubmitBtn = document.getElementById('form-submit-btn');

    // Function to open form in EDIT mode
    function editGoal(id, namaGoal, targetNominal, nominalBerjalan, tanggalTarget) {
        formMode.value = 'edit';
        formGoalId.value = id;
        formTitle.textContent = 'Edit Goal';
        formSubmitBtn.textContent = 'Update';
        
        document.getElementById('namaGoal').value = namaGoal;
        document.getElementById('targetNominal').value = targetNominal;
        document.getElementById('nominalBerjalan').value = nominalBerjalan;
        document.getElementById('tanggalTarget').value = tanggalTarget;
        
        goalFormContainer.style.display = 'flex';
    }

    // Function to delete goal
    function deleteGoal(id) {
        Swal.fire({
            title: 'Hapus Target?',
            text: 'Apakah Anda yakin ingin menghapus target ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ED6363',
            cancelButtonColor: '#999',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const data = new FormData();
                data.append('_method', 'DELETE');
                data.append('_token', document.querySelector('[name="_token"]').value);

                fetch(`/goals/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: data
                })
                .then(resp => resp.json())
                .then(result => {
                    if (result.success) {
                        const goalCard = document.querySelector(`[data-goal-id="${id}"]`);
                        if (goalCard) {
                            goalCard.remove();
                        }
                        Swal.fire({
                            title: 'Terhapus!',
                            text: 'Target berhasil dihapus.',
                            icon: 'success',
                            confirmButtonColor: '#00456A',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat menghapus target',
                        icon: 'error',
                        confirmButtonColor: '#00456A',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    }

    // Function to update an existing goal card
    function updateGoalCard(goal) {
        const goalCard = document.querySelector(`[data-goal-id="${goal.id}"]`);
        if (goalCard) {
            const percentage = goal.targetNominal > 0 ? Math.round((goal.nominalBerjalan / goal.targetNominal) * 100) : 0;
            
            goalCard.querySelector('h3').textContent = goal.namaGoal;
            goalCard.querySelector('.goal-days').textContent = goal.tanggalTarget;
            
            const rows = goalCard.querySelectorAll('.goal-row');
            rows[0].querySelector('.goal-amount').textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(goal.nominalBerjalan);
            rows[1].querySelector('.goal-amount').textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(goal.targetNominal);
            
            goalCard.querySelector('.progress-fill').style.width = percentage + '%';
            
            const editBtn = goalCard.querySelector('.goal-btn-edit');
            editBtn.setAttribute('onclick', `editGoal(${goal.id}, '${goal.namaGoal}', ${goal.targetNominal}, ${goal.nominalBerjalan}, '${goal.tanggalTarget}')`);
        }
    }

    // Function to add new goal card
    function addGoalCard(goal) {
        const goalsList = document.querySelector('.goals-list');
        if (!goalsList) return;

        // Remove empty state if exists
        const emptyState = goalsList.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const percentage = goal.targetNominal > 0 ? Math.round((goal.nominalBerjalan / goal.targetNominal) * 100) : 0;
        
        // Calculate color index based on current number of cards
        const cardCount = goalsList.querySelectorAll('.goal-card').length;
        const colorIndex = (cardCount % 5) + 1;

        const newCard = document.createElement('div');
        newCard.className = 'goal-card';
        newCard.setAttribute('data-goal-id', goal.id);
        newCard.setAttribute('onclick', `showHistory(${goal.id})`);
        newCard.style.cursor = 'pointer';

        // Calculate days left
        const targetDate = new Date(goal.tanggalTarget);
        const today = new Date();
        const diffTime = targetDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
        let daysText = diffDays > 0 ? diffDays + ' hari lagi' : 'Tercapai / Lewat';
        const dateFormatted = targetDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

        newCard.innerHTML = `
            <div class="goal-header color-${colorIndex}">
                <div class="goal-icon">🎯</div>
                <div class="goal-title">
                    <h3>${goal.namaGoal}</h3>
                    <p class="goal-days">${daysText} (${dateFormatted})</p>
                </div>
                <div class="goal-actions">
                    <button class="goal-btn-edit" title="Edit" onclick="event.stopPropagation(); editGoal(${goal.id}, '${goal.namaGoal}', ${goal.targetNominal}, ${goal.nominalBerjalan}, '${goal.tanggalTarget}')">✏️</button>
                    <button class="goal-btn-delete" title="Delete" onclick="event.stopPropagation(); deleteGoal(${goal.id})">🗑️</button>
                </div>
            </div>
            <div class="goal-body">
                <div class="goal-row">
                    <span class="goal-label">Terkumpul</span>
                    <span class="goal-amount">Rp${new Intl.NumberFormat('id-ID').format(goal.nominalBerjalan)}</span>
                </div>
                <div class="goal-row">
                    <span class="goal-label">Target</span>
                    <span class="goal-amount">Rp${new Intl.NumberFormat('id-ID').format(goal.targetNominal)}</span>
                </div>
                <div class="goal-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${percentage}%;"></div>
                    </div>
                </div>
            </div>
        `;

        // Insert at the beginning (since we order by desc)
        goalsList.insertBefore(newCard, goalsList.firstChild);
    }

    // Function to open goal modal (for add button)
    function openGoalModal() {
        formMode.value = 'add';
        formGoalId.value = '';
        formTitle.textContent = 'Tambah Target Baru';
        formSubmitBtn.textContent = 'Simpan';
        goalForm.reset();
        goalFormContainer.style.display = 'flex';
    }

    // ===== EVENT HANDLERS WITH EVENT DELEGATION =====
    
    // Add goal button
    document.addEventListener('click', function(e) {
        if (e.target.closest('#btn-add-goal')) {
            formMode.value = 'add';
            formGoalId.value = '';
            formTitle.textContent = 'Tambah Goal Baru';
            formSubmitBtn.textContent = 'Simpan';
            goalForm.reset();
            goalFormContainer.style.display = 'flex';
        }
    });

    // Close form button
    document.addEventListener('click', function(e) {
        if (e.target.closest('#form-cancel-btn')) {
            goalFormContainer.style.display = 'none';
            goalForm.reset();
            formMode.value = 'add';
            formGoalId.value = '';
        }
    });

    // Close form when clicking outside
    goalFormContainer.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            goalForm.reset();
            formMode.value = 'add';
            formGoalId.value = '';
        }
    });

    // Handle form submission
    goalForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('.btn-save');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = formMode.value === 'add' ? 'Menyimpan...' : 'Mengupdate...';

        const isEditMode = formMode.value === 'edit';
        const url = isEditMode ? `/goals/${formGoalId.value}` : '{{ route('goals.store') }}';

        const data = new FormData();
        if (isEditMode) {
            data.append('_method', 'PUT');
        }
        data.append('_token', document.querySelector('#goal-form [name="_token"]').value);
        data.append('namaGoal', document.getElementById('namaGoal').value);
        data.append('targetNominal', document.getElementById('targetNominal').value);
        
        const nominalBerjalan = document.getElementById('nominalBerjalan').value;
        data.append('nominalBerjalan', nominalBerjalan === '' ? '0' : nominalBerjalan);
        
        data.append('tanggalTarget', document.getElementById('tanggalTarget').value);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data
        })
        .then(resp => resp.json())
        .then(result => {
            if (result.success) {
                const goal = result.goal;
                
                if (isEditMode) {
                    updateGoalCard(goal);
                } else {
                    addGoalCard(goal);
                }
                
                const messageText = isEditMode 
                    ? 'Target berhasil diperbarui.' 
                    : 'Target baru berhasil ditambahkan.';
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: messageText,
                    icon: 'success',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                });
                
                goalFormContainer.style.display = 'none';
                goalForm.reset();
                formMode.value = 'add';
                formGoalId.value = '';
                
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            } else {
                throw result;
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            let errorMsg = isEditMode 
                ? 'Terjadi kesalahan saat mengupdate target' 
                : 'Terjadi kesalahan saat menambahkan target';
            
            if (err.message) {
                errorMsg = err.message;
            }
            
            Swal.fire({
                title: err.status === 'warning' ? 'Perhatian!' : 'Gagal!',
                text: errorMsg,
                icon: err.status === 'warning' ? 'warning' : 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
        });
    });
</script>

<script>
    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const historyModal = document.getElementById('historyModal');
        if (historyModal) {
            historyModal.addEventListener('click', function(e) {
                if (e.target === this) closeHistoryModal();
            });
        }
    });

    // History Functions
    function showHistory(goalId) {
        const modal = document.getElementById('historyModal');
        const list = document.getElementById('historyList');
        const title = document.getElementById('historyModalTitle');
        
        modal.classList.add('active');
        list.innerHTML = '<div style="text-align:center; padding:20px;">Memuat data...</div>';
        
        fetch(`/api/goals/${goalId}/transactions`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    title.textContent = `Riwayat: ${data.budget_name}`;
                    if(data.transactions.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Belum ada transaksi untuk goal ini.</div>';
                    } else {
                        let html = '';
                        data.transactions.forEach(trx => {
                            const date = new Date(trx.tanggal).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                            const amount = new Intl.NumberFormat('id-ID').format(trx.jumlah);
                            html += `
                                <div class="transaction-item" style="margin-bottom:10px; border-left: 4px solid #6B9BD1; background: #F0F7FF; padding: 10px; border-radius: 5px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div class="transaction-name" style="font-weight:600;">${trx.keterangan || 'Tabungan'}</div>
                                        <div class="transaction-amount income" style="color:#00A311; font-weight:bold;">+ Rp${amount}</div>
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

    // Update editGoal untuk format angka
    const originalEditGoal = editGoal;
    editGoal = function(id, namaGoal, targetNominal, nominalBerjalan, tanggalTarget) {
        formMode.value = 'edit';
        formGoalId.value = id;
        formTitle.textContent = 'Edit Goal';
        formSubmitBtn.textContent = 'Update';
        
        document.getElementById('namaGoal').value = namaGoal;
        document.getElementById('targetNominal').value = formatRupiah(targetNominal);
        document.getElementById('nominalBerjalan').value = formatRupiah(nominalBerjalan);
        document.getElementById('tanggalTarget').value = tanggalTarget;
        
        goalFormContainer.style.display = 'flex';
    };

    // Update form submission untuk parse formatted numbers
    const originalFormSubmit = goalForm.onsubmit;
    goalForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('.btn-save');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = formMode.value === 'add' ? 'Menyimpan...' : 'Mengupdate...';

        const isEditMode = formMode.value === 'edit';
        const url = isEditMode ? `/goals/${formGoalId.value}` : '{{ route('goals.store') }}';

        // Parse formatted numbers
        const targetNominalFormatted = document.getElementById('targetNominal').value;
        const nominalBerjalanFormatted = document.getElementById('nominalBerjalan').value;
        
        const targetNominalRaw = unformatRupiah(targetNominalFormatted);
        const nominalBerjalanRaw = nominalBerjalanFormatted ? unformatRupiah(nominalBerjalanFormatted) : 0;

        // Validasi: target nominal tidak boleh 0 atau negatif
        if (!targetNominalRaw || targetNominalRaw <= 0) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Target nominal harus lebih dari 0!',
                icon: 'warning',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }

        // Validasi: nominal berjalan tidak boleh negatif
        if (nominalBerjalanRaw < 0) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Nominal terkumpul tidak boleh negatif!',
                icon: 'warning',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }

        const data = new FormData();
        if (isEditMode) {
            data.append('_method', 'PUT');
        }
        data.append('_token', document.querySelector('#goal-form [name="_token"]').value);
        data.append('namaGoal', document.getElementById('namaGoal').value);
        data.append('targetNominal', targetNominalRaw);
        data.append('nominalBerjalan', nominalBerjalanRaw);
        data.append('tanggalTarget', document.getElementById('tanggalTarget').value);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data
        })
        .then(resp => resp.json())
        .then(result => {
            if (result.success) {
                const goal = result.goal;
                
                if (isEditMode) {
                    updateGoalCard(goal);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Target berhasil diupdate.',
                        icon: 'success',
                        confirmButtonColor: '#00456A',
                        confirmButtonText: 'OK'
                    });
                } else {
                    addGoalCard(goal);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Target baru berhasil ditambahkan.',
                        icon: 'success',
                        confirmButtonColor: '#00456A',
                        confirmButtonText: 'OK'
                    });
                }
                
                goalFormContainer.style.display = 'none';
                goalForm.reset();
                formMode.value = 'add';
                formGoalId.value = '';
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: result.message || 'Terjadi kesalahan',
                    icon: 'error',
                    confirmButtonColor: '#00456A',
                    confirmButtonText: 'OK'
                });
            }
            
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat menyimpan target',
                icon: 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }, true); // Use capture to override existing handler

    // Event listener untuk auto-format
    document.addEventListener('DOMContentLoaded', function() {
        const targetNominalInput = document.getElementById('targetNominal');
        const nominalBerjalanInput = document.getElementById('nominalBerjalan');
        
        // Function to setup formatting on an input
        function setupFormatting(input, rawInputId) {
            if (!input) return;
            
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\./g, '');
                value = value.replace(/[^\d]/g, '');
                
                if (value) {
                    e.target.value = formatRupiah(value);
                    if (rawInputId) {
                        const rawInput = document.getElementById(rawInputId);
                        if (rawInput) rawInput.value = value;
                    }
                } else {
                    e.target.value = '';
                    if (rawInputId) {
                        const rawInput = document.getElementById(rawInputId);
                        if (rawInput) rawInput.value = '';
                    }
                }
            });
            
            input.addEventListener('keypress', function(e) {
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
        
        setupFormatting(targetNominalInput, 'targetNominal_raw');
        setupFormatting(nominalBerjalanInput, 'nominalBerjalan_raw');
    });
</script>
