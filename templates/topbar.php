<?php
/**
 * NusaPOS - Layout Topbar
 */
declare(strict_types=1);

$user = current_user() ?? [
    'nama_lengkap' => 'Operator',
    'role' => 'kasir',
    'avatar' => null,
];
$avatarUrl = $user['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap']) . '&background=2563EB&color=fff&size=128';
?>
<header class="app-topbar fixed top-0 left-0 md:left-64 right-0 h-16 bg-surface-container-lowest/95 backdrop-blur-md border-b border-outline-variant/40 z-40 px-4 md:px-space-lg flex items-center justify-between shadow-[0_1px_4px_rgba(0,0,0,0.02)]">
    <!-- Left: Hamburger & Status -->
    <div class="flex items-center gap-space-sm lg:gap-space-md">
        <!-- Hamburger button mobile -->
        <button type="button" onclick="toggleSidebar(true)" aria-label="Buka menu navigasi" class="md:hidden p-2 rounded-xl text-on-surface hover:bg-surface-container transition-colors flex items-center justify-center cursor-pointer">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
        <!-- Desktop Sidebar Toggle Button -->
        <button type="button" onclick="toggleSidebarCollapse()" id="desktop-sidebar-toggle" aria-label="Sembunyikan / Tampilkan Sidebar" title="Sembunyikan / Tampilkan Sidebar (Ctrl+B)" class="hidden md:flex p-2 rounded-xl text-on-surface hover:bg-surface-container transition-colors items-center justify-center cursor-pointer">
            <span class="material-symbols-outlined text-[24px]" id="desktop-sidebar-toggle-icon">menu_open</span>
        </button>

        <div class="flex items-center gap-2 px-space-sm py-1 bg-on-tertiary-container/30 border border-tertiary-fixed rounded-full">
            <span class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></span>
            <span class="font-label-sm text-label-sm text-tertiary font-semibold">Online • Mesin Kasir #01</span>
        </div>
    </div>

    <!-- Right: Profile & Quick Actions -->
    <div class="flex items-center gap-space-md">
        <!-- Date display -->
        <div class="hidden md:flex items-center gap-1.5 text-on-surface-variant font-label-sm">
            <span class="material-symbols-outlined text-[16px]">calendar_today</span>
            <span><?= date('d M Y') ?></span>
        </div>

        <!-- Theme Toggle Button -->
        <button type="button" onclick="toggleTheme()" title="Ganti Mode Gelap / Terang" aria-label="Ganti Mode Gelap / Terang" class="w-9 h-9 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface transition-all flex items-center justify-center border border-outline-variant/30 hover:scale-105 active:scale-95 shadow-xs cursor-pointer">
            <span class="material-symbols-outlined theme-toggle-icon text-[20px] transition-transform duration-300">dark_mode</span>
        </button>

        <div class="h-6 w-px bg-outline-variant/50 hidden md:block"></div>

        <!-- User Profile Pill -->
        <div class="relative group">
            <div class="flex items-center gap-space-sm pl-space-xs cursor-pointer">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profil" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary/20 shadow-sm">
                <div class="hidden md:flex flex-col text-left">
                    <span class="font-label-md text-label-md text-on-surface font-semibold truncate max-w-[140px]"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                    <span class="font-label-sm text-label-sm text-primary font-medium capitalize"><?= $user['role'] === 'admin' ? 'Administrator' : 'Kasir Utama' ?></span>
                </div>
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant group-hover:text-on-surface transition-transform group-hover:rotate-180">arrow_drop_down</span>
            </div>

            <!-- Profile Dropdown Menu -->
            <div class="absolute right-0 top-full mt-2 w-56 bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant/40 py-2 hidden group-hover:block hover:block z-50">
                <div class="px-4 py-2 border-b border-outline-variant/30">
                    <p class="font-label-sm text-on-surface-variant">Masuk sebagai:</p>
                    <p class="font-label-md text-on-surface font-bold truncate"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                    <span class="inline-block mt-1 font-label-sm text-xs px-2 py-0.5 rounded-full <?= $user['role'] === 'admin' ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-surface-container text-on-surface' ?>">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </div>
                
                <!-- Profile / Avatar Trigger -->
                <button type="button" onclick="openMyProfileModal()" class="w-full flex items-center gap-2 px-4 py-2.5 text-on-surface hover:bg-surface-container text-body-sm transition-colors text-left cursor-pointer border-b border-outline-variant/20">
                    <span class="material-symbols-outlined text-[18px] text-primary">account_circle</span>
                    <span>Foto & Profil Saya</span>
                </button>

                <!-- Quick Toggle inside menu -->
                <button type="button" onclick="toggleTheme()" class="w-full flex items-center justify-between px-4 py-2.5 text-on-surface hover:bg-surface-container text-body-sm transition-colors text-left cursor-pointer border-b border-outline-variant/20">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined theme-toggle-icon text-[18px] text-primary">dark_mode</span>
                        <span>Mode Tampilan</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant font-medium theme-status-text">Terang</span>
                </button>

                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="flex items-center gap-2 px-4 py-2.5 text-error hover:bg-error-container text-body-sm transition-colors">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Keluar Sistem</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Modal Profil & Foto Saya (Mandiri untuk Kasir & Admin) -->
<div id="modal-my-profile" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col max-h-[90vh]">
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-[22px] text-primary">manage_accounts</span>
                <h3 class="font-headline-md text-base text-on-surface font-bold">Foto & Profil Saya</h3>
            </div>
            <button type="button" onclick="closeModal('modal-my-profile')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/pengguna_action.php" enctype="multipart/form-data" class="p-space-lg overflow-y-auto space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_my_profile">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
            <input type="hidden" name="hapus_avatar" id="myprofile-hapus-avatar" value="0">

            <!-- Avatar Preview & Upload -->
            <div class="flex flex-col items-center justify-center gap-3 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/40 text-center">
                <div class="relative w-24 h-24 rounded-full bg-surface-container-high border-4 border-surface shadow-md overflow-hidden shrink-0 group">
                    <img id="myprofile-avatar-preview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Foto Profil" class="w-full h-full object-cover">
                    <label for="input-myprofile-avatar-file" class="absolute inset-0 bg-inverse-surface/60 text-surface flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-xs font-semibold">
                        <span class="material-symbols-outlined text-[22px]">photo_camera</span>
                        <span>Ubah</span>
                    </label>
                </div>

                <div class="space-y-2 w-full">
                    <div class="flex items-center justify-center gap-2">
                        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface text-xs font-semibold cursor-pointer border border-outline-variant/50 transition-colors shadow-xs">
                            <span class="material-symbols-outlined text-[16px] text-primary">upload_file</span>
                            <span>Pilih Berkas Foto</span>
                            <input type="file" name="avatar_file" id="input-myprofile-avatar-file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewMyProfileAvatar(this)">
                        </label>
                        <button type="button" onclick="clearMyProfileAvatar()" id="btn-myprofile-remove-avatar" class="px-2.5 py-1.5 rounded-lg border border-error/30 text-error hover:bg-error-container text-xs font-medium transition-colors inline-flex items-center gap-1" title="Hapus Foto">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                            <span>Hapus</span>
                        </button>
                    </div>
                    <p id="myprofile-file-chosen-text" class="text-[11px] text-on-surface-variant truncate">Maksimal 3MB (JPG, PNG, WEBP, GIF)</p>

                    <div class="pt-1">
                        <input type="text" name="avatar" id="input-myprofile-avatar-url" placeholder="Atau tempel URL gambar eksternal..." oninput="previewMyProfileUrlAvatar(this.value)"
                            class="w-full px-3 py-1.5 rounded-lg bg-surface-container-lowest text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary placeholder:text-outline">
                    </div>
                </div>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" id="input-myprofile-nama" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required placeholder="Nama Anda"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary text-on-surface">
            </div>

            <!-- Info Akun Non-Editable -->
            <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/30 space-y-1.5 text-xs">
                <div class="flex items-center justify-between text-on-surface-variant">
                    <span>Nama Pengguna:</span>
                    <span class="font-mono font-bold text-on-surface">@<?= htmlspecialchars($user['username'] ?? '') ?></span>
                </div>
                <div class="flex items-center justify-between text-on-surface-variant">
                    <span>Peran:</span>
                    <span class="font-semibold text-primary capitalize"><?= $user['role'] === 'admin' ? 'Administrator' : 'Kasir Utama' ?></span>
                </div>
                <div class="flex items-center justify-between text-on-surface-variant">
                    <span>Cabang:</span>
                    <span class="text-on-surface truncate max-w-[200px]"><?= htmlspecialchars($user['cabang'] ?? 'Cabang Menteng - Jakarta Pusat') ?></span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-outline-variant/30">
                <button type="button" onclick="closeModal('modal-my-profile')" class="px-4 py-2 rounded-xl text-on-surface-variant hover:bg-surface-container text-xs font-semibold transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary/90 text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMyProfileModal() {
    openModal('modal-my-profile');
}
function previewMyProfileAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('myprofile-avatar-preview');
            if (preview) preview.src = e.target.result;
            const text = document.getElementById('myprofile-file-chosen-text');
            if (text) text.textContent = file.name;
            const hapusInput = document.getElementById('myprofile-hapus-avatar');
            if (hapusInput) hapusInput.value = '0';
        };
        reader.readAsDataURL(file);
    }
}
function previewMyProfileUrlAvatar(url) {
    if (url && url.trim() !== '') {
        const preview = document.getElementById('myprofile-avatar-preview');
        if (preview) preview.src = url.trim();
        const hapusInput = document.getElementById('myprofile-hapus-avatar');
        if (hapusInput) hapusInput.value = '0';
    }
}
function clearMyProfileAvatar() {
    const fileInput = document.getElementById('input-myprofile-avatar-file');
    if (fileInput) fileInput.value = '';
    const urlInput = document.getElementById('input-myprofile-avatar-url');
    if (urlInput) urlInput.value = '';
    const text = document.getElementById('myprofile-file-chosen-text');
    if (text) text.textContent = 'Foto dihapus (kembali ke avatar default)';
    const hapusInput = document.getElementById('myprofile-hapus-avatar');
    if (hapusInput) hapusInput.value = '1';
    const preview = document.getElementById('myprofile-avatar-preview');
    if (preview) {
        preview.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent('<?= addslashes($user['nama_lengkap']) ?>') + '&background=2563EB&color=fff&size=128';
    }
}
</script>
