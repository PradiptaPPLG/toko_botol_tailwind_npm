<!-- Custom Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-9999 flex items-center justify-center opacity-0 invisible">
    <div class="modal-content bg-white rounded-lg shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
            <span class="text-4xl mr-3" id="confirmIcon">⚠️</span>
            <h3 class="text-xl font-bold text-gray-800" id="confirmTitle">Konfirmasi</h3>
        </div>
        <p class="text-gray-600 mb-6" id="confirmMessage">Apakah Anda yakin?</p>
        <div class="flex gap-3">
            <button onclick="window.confirmCallback(false)"
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg transition-all">
                Batal
            </button>
            <button onclick="window.confirmCallback(true)"
                    id="confirmBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition-all">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
// Custom Confirm Function
window.confirmCallback = null;

function customConfirm(message, title = 'Konfirmasi', icon = '⚠️', btnColor = 'red') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        if (!modal) {
            console.error('confirmModal not found! Did you forget to include modal_confirm.php?');
            resolve(window.originalConfirm ? window.originalConfirm(message) : confirm(message));
            return;
        }

        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.getElementById('confirmIcon');
        const btnEl = document.getElementById('confirmBtn');

        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.textContent = icon;

        // Change button color
        btnEl.className = `flex-1 bg-${btnColor}-600 hover:bg-${btnColor}-700 text-white font-bold py-3 px-4 rounded-lg transition-all`;

        window.confirmCallback = (result) => {
            modal.classList.remove('show');
            setTimeout(() => resolve(result), 300);
        };

        modal.classList.add('show');
    });
}

// Quick confirm variants
async function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return await customConfirm(message, 'Hapus Data', '🗑️', 'red');
}

async function confirmSave(message = 'Yakin ingin menyimpan perubahan?') {
    return await customConfirm(message, 'Simpan Perubahan', '💾', 'blue');
}

async function confirmCancel(message = 'Yakin ingin membatalkan?') {
    return await customConfirm(message, 'Batalkan', '❌', 'yellow');
}

async function confirmLogout(message = 'Yakin ingin keluar?') {
    return await customConfirm(message, 'Keluar', '🚪', 'red');
}

async function confirmClear(message = 'Yakin ingin mengosongkan?') {
    return await customConfirm(message, 'Kosongkan', '🗑️', 'red');
}

// Store original confirm for fallback
if (!window.originalConfirm) {
    window.originalConfirm = window.confirm;
}
</script>
