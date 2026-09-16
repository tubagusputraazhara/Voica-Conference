// Voica App JavaScript - Extracted from app.blade.php for better performance

// AJAX navigation: fetch page content and replace .main-content, keep sidebar static
function fetchAndSwap(url, push = true) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(resp => {
            if (!resp.ok) throw new Error('Network response was not ok');
            return resp.text();
        })
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
            if (push) history.pushState({ url: url }, '', url);
            // Re-run any inline scripts in the inserted HTML
            Array.from(document.getElementById('main-content').querySelectorAll('script')).forEach(oldScript => {
                const s = document.createElement('script');
                if (oldScript.src) { s.src = oldScript.src; }
                s.textContent = oldScript.textContent;
                document.body.appendChild(s).parentNode.removeChild(s);
            });
        })
        .catch(err => console.error('AJAX navigation error:', err));
}

document.addEventListener('click', function (e) {
    const a = e.target.closest('a.ajax-link');
    if (!a) return;

    try {
        const targetUrl = new URL(a.href, location.origin);
        const currentSection = (location.pathname.split('/')[1] || '').toLowerCase();
        const targetSection = (targetUrl.pathname.split('/')[1] || '').toLowerCase();

        if (a.hasAttribute('data-reload') || currentSection !== targetSection) {
            window.location.href = a.href;
            return;
        }
    } catch (err) {
        console.warn('URL parse failed, falling back to AJAX navigation', err);
    }

    e.preventDefault();
    fetchAndSwap(a.href, true);
});

window.addEventListener('popstate', function (e) {
    const url = (e.state && e.state.url) || location.pathname;
    fetchAndSwap(url, false);
});

// Voice Modal Functions
function closeVoiceModal() {
    const modal = document.getElementById('voiceModal');
    if (modal) {
        modal.classList.remove('active');
        document.getElementById('voiceTransactionForm').reset();
    }
}

async function saveVoiceTransaction(event) {
    event.preventDefault();

    const form = event.target;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const data = {
        jenis: document.getElementById('jenis').value,
        kategori: document.getElementById('kategori').value,
        jumlah: parseFloat(document.getElementById('jumlah').value),
        keterangan: document.getElementById('keterangan').value,
        budget_id: null,
        goal_id: null
    };

    try {
        const response = await fetch('/api/voice-transaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            closeVoiceModal();

            Swal.fire({
                title: 'Berhasil!',
                text: 'Transaksi berhasil disimpan!',
                icon: 'success',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Gagal!',
                text: result.message || 'Gagal menyimpan transaksi',
                icon: 'error',
                confirmButtonColor: '#00456A',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Terjadi kesalahan saat menyimpan transaksi',
            icon: 'error',
            confirmButtonColor: '#00456A',
            confirmButtonText: 'OK'
        });
    }
}

// Close modal when clicking outside
document.addEventListener('click', function (e) {
    const modal = document.getElementById('voiceModal');
    if (e.target === modal) {
        closeVoiceModal();
    }
});

// Make functions globally available
window.closeVoiceModal = closeVoiceModal;
window.saveVoiceTransaction = saveVoiceTransaction;
