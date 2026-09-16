<!-- Global Voice Button -->
<button class="voice-btn" id="voiceBtn" onclick="startVoiceRecording()">
    <svg width="24" height="30" viewBox="0 0 38 48" fill="none">
        <path d="M38 20.8929C38 20.6571 37.7927 20.4643 37.5394 20.4643H34.0849C33.8315 20.4643 33.6242 20.6571 33.6242 20.8929C33.6242 28.4089 27.0779 34.5 19 34.5C10.9221 34.5 4.37576 28.4089 4.37576 20.8929C4.37576 20.6571 4.16849 20.4643 3.91515 20.4643H0.460606C0.207273 20.4643 0 20.6571 0 20.8929C0 29.9304 7.28909 37.3875 16.697 38.4429V43.9286H8.33121C7.54243 43.9286 6.90909 44.6946 6.90909 45.6429V47.5714C6.90909 47.8071 7.0703 48 7.26606 48H30.7339C30.9297 48 31.0909 47.8071 31.0909 47.5714V45.6429C31.0909 44.6946 30.4576 43.9286 29.6688 43.9286H21.0727V38.4696C30.59 37.5054 38 30.0054 38 20.8929ZM19 30C24.4064 30 28.7879 25.9714 28.7879 21V9C28.7879 4.02857 24.4064 0 19 0C13.5936 0 9.21212 4.02857 9.21212 9V21C9.21212 25.9714 13.5936 30 19 30Z" fill="white" />
    </svg>
    <span class="voice-btn-text">Transaksi dengan Suara</span>
</button>

<!-- Voice Transaction Modal -->
<div class="modal-overlay" id="voiceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="color: #00456A;">Tambah Transaksi</h2>
            <button class="close-btn" onclick="closeVoiceModal()">&times;</button>
        </div>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 25px;">Isi form di bawah ini untuk mencatat transaksi Anda</p>

        <form id="transactionForm" onsubmit="saveTransaction(event)">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="jenis" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                        💸 Jenis Transaksi <span style="color: #ED6363;">*</span>
                    </label>
                    <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Pilih jenis transaksi</p>
                    <select id="jenis" name="jenis" required style="font-size: 16px; padding: 14px;">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Pemasukan">📈 Pemasukan</option>
                        <option value="Pengeluaran">📉 Pengeluaran</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kategori" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                        🏷️ Kategori <span style="color: #ED6363;">*</span>
                    </label>
                    <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Pilih kategori yang sesuai</p>
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
                        <option value="Tabungan">💰 Tabungan</option>
                        <option value="Sedekah">🤲 Sedekah</option>
                        <option value="Gaji">💼 Gaji</option>
                        <option value="Bonus">🎁 Bonus</option>
                        <option value="Penjualan">💵 Penjualan</option>
                        <option value="Lainnya">📝 Lainnya</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="jumlah" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    💵 Berapa Jumlahnya? <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Masukkan nominal transaksi</p>
                <input type="text" id="jumlah" name="jumlah" placeholder="Contoh: 50.000" required style="font-size: 16px; padding: 14px;">
                <input type="hidden" id="jumlah_raw" name="jumlah_raw">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">💡 Angka akan otomatis diformat dengan pemisah ribuan</p>
            </div>

            <div class="form-group">
                <label for="keterangan" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                    📝 Keterangan <span style="color: #ED6363;">*</span>
                </label>
                <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Berikan catatan untuk transaksi ini</p>
                <textarea id="keterangan" name="keterangan" placeholder="Contoh: Makan siang di warteg" required style="font-size: 16px; padding: 14px; min-height: 80px;"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="budget" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                        💰 Budget (Opsional)
                    </label>
                    <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Alokasikan ke budget tertentu</p>
                    <select id="budget" name="budget_id" style="font-size: 16px; padding: 14px;">
                        <option value="">-- Pilih Budget --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="goal" style="font-size: 16px; font-weight: 700; color: #2C3E50;">
                        🎯 Target (Opsional)
                    </label>
                    <p style="font-size: 13px; color: #666; margin: 5px 0 8px 0;">Alokasikan ke target tertentu</p>
                    <select id="goal" name="goal_id" style="font-size: 16px; padding: 14px;">
                        <option value="">-- Pilih Target --</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions" style="margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeVoiceModal()" style="font-size: 16px; padding: 14px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 14px;">💾 Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Memproses...</div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast">
    <div class="toast-icon" id="toastIcon">✅</div>
    <div class="toast-message" id="toastMessage">Transaksi berhasil disimpan!</div>
</div>

<script>
    // Configuration
    const PARSE_API_URL = '{{ route("voice.parse.text") }}';
    const TRANSACTION_API_URL = '{{ route("voice.transaction.store.secure") }}';

    // Global variables
    let recognition = null;
    let isRecording = false;

    // Initialize Web Speech API
    function initSpeechRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            showToast('❌ Browser Anda tidak support voice recognition.', 'error');
            return false;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();

        recognition.lang = 'id-ID';
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        let gotResult = false;

        recognition.onstart = function() {
            isRecording = true;
            gotResult = false;
            updateVoiceButtonRecording(true);
            showToast('🎤 Mulai berbicara...', 'success');
        };

        recognition.onspeechend = function() {
            // User selesai bicara
            try {
                recognition.stop();
            } catch (e) {
                console.warn(e);
            }

            updateVoiceButtonRecording(false);
            showLoading('Mengenali suara...');
        };

        recognition.onend = function() {
            isRecording = false;
            updateVoiceButtonRecording(false);
            if (!gotResult) {
                hideLoading(); // Hide loading if no result
            }
        };

        recognition.onresult = function(event) {
            gotResult = true;
            updateVoiceButtonRecording(false); // Pastikan mati saat dapat hasil

            const text = event.results[0][0].transcript;
            console.log('Speech recognized:', text);
            sendTextToAPI(text);
        };

        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
            isRecording = false;
            updateVoiceButtonRecording(false);
            hideLoading();

            let msg = 'Terjadi kesalahan.';
            if (event.error === 'no-speech') msg = 'Tidak ada suara terdeteksi.';
            if (event.error === 'not-allowed') msg = 'Akses mikrofon ditolak.';

            showToast('❌ ' + msg, 'error');
        };

        return true;
    }

    // Voice Recording Functions
    function startVoiceRecording() {
        if (isRecording) {
            if (recognition) recognition.stop();
            return;
        }

        if (!recognition) {
            if (!initSpeechRecognition()) return;
        }

        try {
            recognition.start();
        } catch (error) {
            console.error('Error starting recognition:', error);
        }
    }

    function updateVoiceButtonRecording(recording) {
        // Target ALL voice buttons (header and floating)
        const voiceBtns = document.querySelectorAll('.voice-btn');

        voiceBtns.forEach(voiceBtn => {
            const voiceText = voiceBtn.querySelector('.voice-btn-text');

            if (recording) {
                voiceBtn.classList.add('recording');
                if (voiceText) voiceText.textContent = '🔴 Merekam... (Klik Stop)';
            } else {
                voiceBtn.classList.remove('recording');
                if (voiceText) voiceText.textContent = 'Transaksi dengan Suara';
            }
        });
    }

    async function sendTextToAPI(text) {
        showLoading('Memproses teks...');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(PARSE_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    text: text
                })
            });

            const result = await response.json();
            console.log('API Response:', result); // Debug response
            hideLoading();

            if (result.success) {
                // Pass result.data to openVoiceModal for proper filling after dropdowns load
                openVoiceModal(result.data);
                showToast(`✅ Terdeteksi: "${result.raw_text}"`, 'success');
            } else {
                // Fallback error message jika key 'error' tidak ada
                const errorMsg = result.error || result.message || 'Gagal memproses teks.';
                showToast(`❌ ${errorMsg}`, 'error');
            }

        } catch (error) {
            hideLoading();
            console.error('Error sending text:', error);
            showToast('❌ Gagal memproses teks.', 'error');
        }
    }

    function autoFillForm(data) {
        if (data.jenis) document.getElementById('jenis').value = data.jenis;
        if (data.kategori) document.getElementById('kategori').value = data.kategori;
        if (data.jumlah) document.getElementById('jumlah').value = data.jumlah;
        if (data.keterangan) document.getElementById('keterangan').value = data.keterangan;

        // Select budget/goal if returned (must match value in dropdown)
        if (data.budget_id) {
            document.getElementById('budget').value = data.budget_id;
        }
        if (data.goal_id) {
            document.getElementById('goal').value = data.goal_id;
        }
    }

    // Modal & Dropdown Functions
    async function loadDropdowns() {
        try {
            // Fetch Budgets
            const resBudget = await fetch('/api/budgets');
            const dataBudget = await resBudget.json();
            if (dataBudget.success) {
                const select = document.getElementById('budget');
                // Keep first option
                select.innerHTML = '<option value="">Pilih Budget</option>';
                dataBudget.data.forEach(b => {
                    select.innerHTML += `<option value="${b.id}">${b.namaBudget}</option>`;
                });
            }

            // Fetch Goals
            const resGoal = await fetch('/api/goals');
            const dataGoal = await resGoal.json();
            if (dataGoal.success) {
                const select = document.getElementById('goal');
                select.innerHTML = '<option value="">Pilih Goal</option>';
                dataGoal.data.forEach(g => {
                    select.innerHTML += `<option value="${g.id}">${g.namaGoal}</option>`;
                });
            }
        } catch (e) {
            console.error('Error loading dropdowns:', e);
        }
    }

    async function openVoiceModal(prefillData = null) {
        document.getElementById('voiceModal').classList.add('active');
        await loadDropdowns(); // Wait for dropdowns to populate

        if (prefillData) {
            autoFillForm(prefillData);
        }
    }

    function closeVoiceModal() {
        document.getElementById('voiceModal').classList.remove('active');
        document.getElementById('transactionForm').reset();
    }

    // Save Transaction
    async function saveTransaction(event) {
        event.preventDefault();

        showLoading('Menyimpan transaksi...');

        const formData = {
            jenis: document.getElementById('jenis').value,
            kategori: document.getElementById('kategori').value,
            jumlah: parseFloat(document.getElementById('jumlah').value),
            keterangan: document.getElementById('keterangan').value,
            budget_id: document.getElementById('budget').value || null,
            goal_id: document.getElementById('goal').value || null
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(TRANSACTION_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            hideLoading();

            if (result.success) {
                closeVoiceModal();
                showToast('✅ Transaksi berhasil disimpan!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(`❌ ${result.message}`, 'error');
            }

        } catch (error) {
            hideLoading();
            console.error('Error saving transaction:', error);
            showToast('❌ Gagal menyimpan transaksi', 'error');
        }
    }

    // UI Helper Functions
    function showLoading(text) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        if (loadingOverlay && loadingText) {
            loadingText.textContent = text;
            loadingOverlay.classList.add('active');
        }
    }

    function hideLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');

        if (toast && toastIcon && toastMessage) {
            toastIcon.textContent = type === 'success' ? '✅' : '❌';
            toastMessage.textContent = message;
            toast.className = `toast ${type} active`;

            setTimeout(() => toast.classList.remove('active'), 5000);
        }
    }

    // ========== RUPIAH FORMATTING FUNCTIONS ==========

    /**
     * Format angka dengan pemisah ribuan
     * Contoh: 50000 → "50.000"
     */
    function formatRupiah(angka) {
        if (!angka) return '';

        // Convert to number if string
        const number = typeof angka === 'string' ? parseFloat(angka.replace(/\./g, '')) : angka;

        // Format dengan pemisah ribuan
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Hapus format rupiah, kembalikan ke angka murni
     * Contoh: "50.000" → 50000
     */
    function unformatRupiah(formatted) {
        if (!formatted) return 0;

        // Hapus semua titik
        const cleaned = formatted.toString().replace(/\./g, '');

        // Convert to number
        return parseFloat(cleaned) || 0;
    }

    // Update autoFillForm untuk format jumlah
    const originalAutoFillForm = autoFillForm;
    autoFillForm = function(data) {
        if (data.jenis) document.getElementById('jenis').value = data.jenis;
        if (data.kategori) document.getElementById('kategori').value = data.kategori;
        if (data.jumlah) {
            // Format jumlah dengan pemisah ribuan
            document.getElementById('jumlah').value = formatRupiah(data.jumlah);
            document.getElementById('jumlah_raw').value = data.jumlah;
        }
        if (data.keterangan) document.getElementById('keterangan').value = data.keterangan;

        // Select budget/goal if returned
        if (data.budget_id) {
            document.getElementById('budget').value = data.budget_id;
        }
        if (data.goal_id) {
            document.getElementById('goal').value = data.goal_id;
        }
    };

    // Update saveTransaction untuk parse formatted number
    const originalSaveTransaction = saveTransaction;
    saveTransaction = async function(event) {
        event.preventDefault();

        // Parse jumlah yang sudah diformat
        const jumlahFormatted = document.getElementById('jumlah').value;
        const jumlahRaw = unformatRupiah(jumlahFormatted);

        // Validasi: jumlah tidak boleh 0 atau negatif
        if (!jumlahRaw || jumlahRaw <= 0) {
            showToast('❌ Jumlah harus lebih dari 0!', 'error');
            return;
        }

        showLoading('Menyimpan transaksi...');

        const formData = {
            jenis: document.getElementById('jenis').value,
            kategori: document.getElementById('kategori').value,
            jumlah: jumlahRaw,
            keterangan: document.getElementById('keterangan').value,
            budget_id: document.getElementById('budget').value || null,
            goal_id: document.getElementById('goal').value || null
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(TRANSACTION_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            hideLoading();

            // Check if voice-lock verification is required
            if (result.voice_lock_required) {
                closeVoiceModal();
                // Show confirmation before redirect
                Swal.fire({
                    icon: 'warning',
                    title: '🔐 Verifikasi Suara Diperlukan',
                    html: `
                        <p style="font-size: 16px; color: #2C3E50; margin-bottom: 15px;">
                            Untuk keamanan, aksi ini memerlukan verifikasi suara.
                        </p>
                        <p style="font-size: 14px; color: #666;">
                            Anda akan diarahkan ke halaman verifikasi suara.
                        </p>
                    `,
                    confirmButtonColor: '#00456A',
                    confirmButtonText: '🎤 Verifikasi Sekarang',
                    showCancelButton: true,
                    cancelButtonText: 'Batal'
                }).then((swalResult) => {
                    if (swalResult.isConfirmed) {
                        window.location.href = result.verify_url || '/voice-lock/verify';
                    }
                });
                return;
            }

            if (result.success) {
                closeVoiceModal();
                showToast('✅ Transaksi berhasil disimpan!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(`❌ ${result.message}`, 'error');
            }

        } catch (error) {
            hideLoading();
            console.error('Error saving transaction:', error);
            showToast('❌ Gagal menyimpan transaksi', 'error');
        }
    };

    /**
     * Event listener untuk auto-format saat user mengetik
     */
    document.addEventListener('DOMContentLoaded', function() {
        const jumlahInput = document.getElementById('jumlah');

        if (jumlahInput) {
            jumlahInput.addEventListener('input', function(e) {
                // Ambil nilai tanpa format
                let value = e.target.value.replace(/\./g, '');

                // Hanya izinkan angka
                value = value.replace(/[^\d]/g, '');

                // Format dengan pemisah ribuan
                if (value) {
                    e.target.value = formatRupiah(value);
                    document.getElementById('jumlah_raw').value = value;
                } else {
                    e.target.value = '';
                    document.getElementById('jumlah_raw').value = '';
                }
            });

            // Prevent non-numeric input
            jumlahInput.addEventListener('keypress', function(e) {
                // Allow: backspace, delete, tab, escape, enter
                if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                    // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }

                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }
    });
</script>