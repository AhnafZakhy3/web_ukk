<?php
// management pengguna
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$currentUser = require_admin();
$activeMenu = 'pengguna';
$pageTitle = 'Manajemen Akun & Hak Akses';
$toko = get_toko_info();

$db = get_db();

$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmin = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalKasir = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'kasir'")->fetchColumn();
$totalNonaktif = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'nonaktif'")->fetchColumn();

$filterRole = $_GET['role'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filterRole === 'admin' || $filterRole === 'kasir') {
    $sql .= " AND role = :role";
    $params[':role'] = $filterRole;
}

if ($filterStatus === 'aktif' || $filterStatus === 'nonaktif') {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

if ($searchQuery !== '') {
    $sql .= " AND (nama_lengkap LIKE :q1 OR username LIKE :q2 OR email LIKE :q3)";
    $params[':q1'] = "%{$searchQuery}%";
    $params[':q2'] = "%{$searchQuery}%";
    $params[':q3'] = "%{$searchQuery}%";
}

$sql .= " ORDER BY role ASC, id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/templates/topbar.php';
?>

<div class="app-layout-content pl-0 md:pl-64 pt-16 min-h-screen bg-surface">
    <main class="p-space-md lg:p-space-lg space-y-space-lg">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-xs uppercase tracking-wider text-primary bg-primary-fixed px-2.5 py-0.5 rounded-full font-bold">Keamanan & Otoritas</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                    <span class="text-xs text-on-surface-variant flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-tertiary">verified_user</span> Multi-Factor Enabled
                    </span>
                </div>
                <h1 class="font-headline-lg text-2xl text-on-surface font-bold tracking-tight">Manajemen Akun & Hak Akses</h1>
                <p class="font-body-md text-sm text-on-surface-variant">Kelola akun kasir, staf operasional, dan administrator sistem toko cabang dengan kendali akses bertingkat.</p>
            </div>
            
            <button type="button" onclick="openAddUserModal()" class="inline-flex items-center gap-2 px-space-lg py-2.5 rounded-xl bg-primary text-on-primary hover:bg-secondary font-label-lg text-sm shadow-sm transition-all active:scale-[0.99]">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                <span>+ Tambah Akun Pengguna</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Total Pengguna</span>
                    <div class="font-headline-xl text-2xl text-on-surface font-bold mt-1"><?= $totalUsers ?> Akun</div>
                    <p class="text-xs text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-primary">badge</span>
                        Semua operator terdaftar
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary-fixed text-on-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                </div>
            </div>

            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Administrator</span>
                    <div class="font-headline-xl text-2xl text-secondary font-bold mt-1"><?= $totalAdmin ?> User</div>
                    <p class="text-xs text-secondary mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">shield_person</span>
                        Akses kontrol penuh
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
                </div>
            </div>

            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Kasir & Staf</span>
                    <div class="font-headline-xl text-2xl text-tertiary font-bold mt-1"><?= $totalKasir ?> User</div>
                    <p class="text-xs text-tertiary mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">point_of_sale</span>
                        Operator terminal aktif
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-on-tertiary-container/30 text-tertiary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">support_agent</span>
                </div>
            </div>

            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Akun Nonaktif</span>
                    <div class="font-headline-xl text-2xl text-error font-bold mt-1"><?= $totalNonaktif ?> Akun</div>
                    <p class="text-xs text-error mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">lock</span>
                        Akses login diblokir
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-error-container text-error flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">person_off</span>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30">
            <form method="GET" action="pengguna.php" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Cari nama pengguna, email, atau username..."
                        class="w-full h-11 pl-11 pr-4 rounded-xl bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-xs border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <select name="role" class="h-11 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30 font-label-md cursor-pointer">
                    <option value="all">Semua Peran</option>
                    <option value="admin" <?= ($filterRole === 'admin') ? 'selected' : '' ?>>Administrator</option>
                    <option value="kasir" <?= ($filterRole === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                </select>

                <select name="status" class="h-11 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30 font-label-md cursor-pointer">
                    <option value="all">Semua Status</option>
                    <option value="aktif" <?= ($filterStatus === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= ($filterStatus === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                </select>

                <button type="submit" class="h-11 px-4 rounded-xl bg-primary text-on-primary font-label-md text-xs hover:bg-secondary transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                    <span>Filter</span>
                </button>
            </form>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-outline font-label-sm text-xs uppercase tracking-wider border-b border-outline-variant/30">
                            <th class="py-3.5 px-4 font-semibold">Profil & Nama Lengkap</th>
                            <th class="py-3.5 px-4 font-semibold">Username & Email</th>
                            <th class="py-3.5 px-4 font-semibold">Peran & Hak Akses</th>
                            <th class="py-3.5 px-4 font-semibold">Cabang Operasional</th>
                            <th class="py-3.5 px-4 font-semibold">Aktivitas Terakhir</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20 text-xs">
                        <?php foreach ($usersList as $u): 
                            $avatar = $u['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($u['nama_lengkap']) . '&background=2563EB&color=fff&size=128';
                            $uJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary/20">
                                    <div class="flex flex-col">
                                        <span class="font-headline-sm text-sm text-on-surface font-semibold"><?= htmlspecialchars($u['nama_lengkap']) ?></span>
                                        <span class="text-[11px] text-on-surface-variant"><?= ($u['role'] === 'admin') ? 'Super Admin Toko' : 'Kasir Utama' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col">
                                    <span class="font-mono font-bold text-primary">@<?= htmlspecialchars($u['username']) ?></span>
                                    <span class="text-on-surface-variant"><?= htmlspecialchars($u['email']) ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-sm text-xs font-semibold">
                                        <span class="material-symbols-outlined text-[14px]">shield_person</span>
                                        Administrator
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-xs font-semibold">
                                        <span class="material-symbols-outlined text-[14px]">point_of_sale</span>
                                        Kasir
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-on-surface-variant">
                                <?= htmlspecialchars($u['cabang']) ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-col text-on-surface-variant">
                                    <span><?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Belum pernah login' ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="api/pengguna_action.php?action=toggle_status&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-semibold <?= ($u['status'] === 'aktif') ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container text-outline' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= ($u['status'] === 'aktif') ? 'bg-tertiary' : 'bg-outline' ?>"></span>
                                    <?= ucfirst($u['status']) ?>
                                </a>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" onclick='openEditUserModal(<?= $uJson ?>)' class="p-1.5 rounded-lg text-outline hover:text-primary hover:bg-surface-container transition-colors" title="Ubah Akun">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?>')" class="p-1.5 rounded-lg text-outline hover:text-secondary hover:bg-surface-container transition-colors" title="Reset Kata Sandi">
                                        <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                                    </button>
                                    <a href="api/pengguna_action.php?action=delete&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');" class="p-1.5 rounded-lg text-outline hover:text-error hover:bg-error-container transition-colors" title="Hapus Akun">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div id="modal-user" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col max-h-[90vh]">
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="font-headline-md text-base text-on-surface font-bold" id="modal-user-title">Tambah Akun Pengguna</h3>
            <button type="button" onclick="closeModal('modal-user')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/pengguna_action.php" enctype="multipart/form-data" class="p-space-lg overflow-y-auto space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="user-form-action" value="add">
            <input type="hidden" name="id" id="user-form-id" value="">

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" id="input-user-nama" required placeholder="Contoh: Budi Santoso"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Nama Pengguna (Username) *</label>
                    <input type="text" name="username" id="input-user-username" required placeholder="budisantoso"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Email *</label>
                    <input type="email" name="email" id="input-user-email" required placeholder="budi@nusapos.id"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div id="password-group">
                <label class="block text-xs font-semibold text-on-surface mb-1">Kata Sandi Awal *</label>
                <input type="password" name="password" id="input-user-pwd" placeholder="Minimal 6 karakter"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Peran Akun *</label>
                    <select name="role" id="input-user-role" required class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="kasir">Kasir Utama</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Status Akun</label>
                    <select name="status" id="input-user-status" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Cabang Penempatan</label>
                <input type="text" name="cabang" id="input-user-cabang" value="Cabang Menteng - Jakarta Pusat"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40">
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-semibold text-on-surface">Foto Profil (Opsional)</label>
                
                <div class="flex items-center gap-4 p-3 rounded-xl bg-surface-container-low border border-outline-variant/40">
                    <div class="relative w-16 h-16 rounded-full bg-surface-container-high border-2 border-outline-variant/40 flex items-center justify-center overflow-hidden shrink-0 shadow-xs">
                        <img id="user-avatar-preview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                        <div id="user-avatar-placeholder" class="flex flex-col items-center justify-center text-on-surface-variant/60">
                            <span class="material-symbols-outlined text-[32px]">account_circle</span>
                        </div>
                        <button type="button" id="btn-remove-avatar" onclick="clearUserAvatar()" title="Hapus Foto" class="absolute inset-0 bg-inverse-surface/65 text-surface hidden items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                            <span class="material-symbols-outlined text-[20px] text-error">delete</span>
                        </button>
                    </div>

                    <div class="flex-1 space-y-2">
                        <div>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface text-xs font-semibold cursor-pointer border border-outline-variant/50 transition-colors shadow-xs">
                                <span class="material-symbols-outlined text-[16px] text-primary">upload_file</span>
                                <span>Pilih Berkas Foto</span>
                                <input type="file" name="avatar_file" id="input-user-avatar-file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewSelectedUserAvatar(this)">
                            </label>
                            <span id="user-file-chosen-text" class="text-[11px] text-on-surface-variant ml-2 truncate inline-block max-w-[160px] align-middle">Belum ada file</span>
                        </div>
                        <div>
                            <input type="text" name="avatar" id="input-user-avatar" placeholder="Atau masukkan URL foto eksternal..." oninput="previewUrlUserAvatar(this.value)"
                                class="w-full px-3 py-1.5 rounded-lg bg-surface-container-lowest text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary placeholder:text-outline">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="hapus_avatar" id="input-hapus-avatar" value="0">
            </div>

            <div class="pt-3 border-t border-outline-variant/30 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-user')" class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary text-xs font-bold shadow-sm">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-reset-pwd" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col">
        <div class="p-4 bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="font-headline-md text-sm text-on-surface font-bold">Atur Ulang Kata Sandi</h3>
            <button type="button" onclick="closeModal('modal-reset-pwd')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/pengguna_action.php" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="reset-pwd-id" value="">

            <p class="text-xs text-on-surface-variant">
                Atur ulang kata sandi untuk akun <strong id="reset-pwd-name" class="text-on-surface"></strong>.
            </p>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Kata Sandi Baru</label>
                <input type="text" name="new_password" required value="password123" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-reset-pwd')" class="px-3.5 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-on-secondary text-xs font-bold shadow-sm">Reset Sandi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewSelectedUserAvatar(input) {
        const preview = document.getElementById('user-avatar-preview');
        const placeholder = document.getElementById('user-avatar-placeholder');
        const removeBtn = document.getElementById('btn-remove-avatar');
        const chosenText = document.getElementById('user-file-chosen-text');
        const hapusInput = document.getElementById('input-hapus-avatar');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            chosenText.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
                removeBtn.classList.add('flex');
            };
            reader.readAsDataURL(file);
            hapusInput.value = '0';
        }
    }

    function previewUrlUserAvatar(url) {
        const preview = document.getElementById('user-avatar-preview');
        const placeholder = document.getElementById('user-avatar-placeholder');
        const removeBtn = document.getElementById('btn-remove-avatar');
        const hapusInput = document.getElementById('input-hapus-avatar');

        const trimmed = (url || '').trim();
        if (trimmed !== '') {
            preview.src = trimmed;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            removeBtn.classList.add('flex');
            hapusInput.value = '0';
        } else {
            const fileInput = document.getElementById('input-user-avatar-file');
            if (!fileInput.files || fileInput.files.length === 0) {
                clearUserAvatar();
            }
        }
    }

    function clearUserAvatar() {
        const preview = document.getElementById('user-avatar-preview');
        const placeholder = document.getElementById('user-avatar-placeholder');
        const removeBtn = document.getElementById('btn-remove-avatar');
        const fileInput = document.getElementById('input-user-avatar-file');
        const urlInput = document.getElementById('input-user-avatar');
        const chosenText = document.getElementById('user-file-chosen-text');
        const hapusInput = document.getElementById('input-hapus-avatar');

        fileInput.value = '';
        urlInput.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        removeBtn.classList.remove('flex');
        chosenText.textContent = 'Belum ada file';
        hapusInput.value = '1';
    }

    function openAddUserModal() {
        document.getElementById('modal-user-title').textContent = 'Tambah Akun Pengguna';
        document.getElementById('user-form-action').value = 'add';
        document.getElementById('user-form-id').value = '';
        document.getElementById('input-user-nama').value = '';
        document.getElementById('input-user-username').value = '';
        document.getElementById('input-user-email').value = '';
        document.getElementById('input-user-pwd').value = 'password123';
        document.getElementById('password-group').classList.remove('hidden');
        document.getElementById('input-user-pwd').required = true;
        document.getElementById('input-user-role').value = 'kasir';
        document.getElementById('input-user-status').value = 'aktif';
        document.getElementById('input-user-cabang').value = 'Cabang Menteng - Jakarta Pusat';
        clearUserAvatar();
        document.getElementById('input-hapus-avatar').value = '0';
        openModal('modal-user');
    }

    function openEditUserModal(user) {
        document.getElementById('modal-user-title').textContent = 'Edit Akun Pengguna';
        document.getElementById('user-form-action').value = 'edit';
        document.getElementById('user-form-id').value = user.id;
        document.getElementById('input-user-nama').value = user.nama_lengkap;
        document.getElementById('input-user-username').value = user.username;
        document.getElementById('input-user-email').value = user.email;
        document.getElementById('password-group').classList.add('hidden');
        document.getElementById('input-user-pwd').required = false;
        document.getElementById('input-user-role').value = user.role;
        document.getElementById('input-user-status').value = user.status;
        document.getElementById('input-user-cabang').value = user.cabang;
        document.getElementById('input-hapus-avatar').value = '0';
        
        document.getElementById('input-user-avatar-file').value = '';
        
        const preview = document.getElementById('user-avatar-preview');
        const placeholder = document.getElementById('user-avatar-placeholder');
        const removeBtn = document.getElementById('btn-remove-avatar');
        const chosenText = document.getElementById('user-file-chosen-text');
        const urlInput = document.getElementById('input-user-avatar');

        if (user.avatar) {
            preview.src = user.avatar;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            removeBtn.classList.add('flex');
            chosenText.textContent = user.avatar.startsWith('public/uploads') ? 'Foto lokal tersimpan' : 'URL aktif';
            urlInput.value = user.avatar.startsWith('http') ? user.avatar : '';
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            removeBtn.classList.add('hidden');
            removeBtn.classList.remove('flex');
            chosenText.textContent = 'Belum ada file';
            urlInput.value = '';
        }

        openModal('modal-user');
    }

    function openResetPasswordModal(id, name) {
        document.getElementById('reset-pwd-id').value = id;
        document.getElementById('reset-pwd-name').textContent = name;
        openModal('modal-reset-pwd');
    }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
