<style>
    .admin-sidebar { width: 16rem; }
    @media (max-width: 767px) { .admin-sidebar { transform: translateX(-100%); } .admin-sidebar.open { transform: translateX(0); } }
    .gradient-bar { background: linear-gradient(180deg,#6366f1,#8b5cf6,#ec4899,#f59e0b,#10b981); }
    .nav-link-active { background: rgba(255,255,255,0.1); }
    .nav-link { transition: background .2s, color .2s; }
    .nav-link:hover { background: rgba(255,255,255,0.08); }
    .page-heading { color: #f1f5f9 !important; }
    body { background:#0f172a; }
</style>
<?php
    $current = basename($_SERVER['PHP_SELF']);
    function activeLink($file, $current){ return $file === $current ? 'nav-link-active' : ''; }
?>
<aside id="adminSidebar" class="admin-sidebar fixed top-0 left-0 h-full bg-slate-900 text-slate-100 shadow-xl z-50 flex flex-col border-r border-slate-800 md:translate-x-0 transition-transform duration-200">
    <div class="flex items-center h-16 px-4 border-b border-slate-800">
        <div class="w-10 h-10 rounded-lg gradient-bar flex items-center justify-center text-white shadow">
            <i class="fas fa-hotel"></i>
        </div>
        <div class="ml-3">
            <span class="block text-sm tracking-wide text-slate-400">Admin Panel</span>
            <span class="font-bold text-lg leading-tight"><?php echo APP_NAME; ?></span>
        </div>
        <button id="sidebarClose" class="md:hidden ml-auto text-slate-400 hover:text-white">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 space-y-1">
        <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-sm font-medium <?php echo activeLink('dashboard.php',$current); ?>">
            <i class="fas fa-chart-line w-5 mr-3 text-indigo-400"></i> Dashboard
        </a>
        <a href="<?php echo APP_URL; ?>/admin/reservations.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-sm font-medium <?php echo activeLink('reservations.php',$current); ?>">
            <i class="fas fa-calendar-check w-5 mr-3 text-pink-400"></i> Reservations
        </a>
        <a href="<?php echo APP_URL; ?>/admin/rooms.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-sm font-medium <?php echo activeLink('rooms.php',$current); ?>">
            <i class="fas fa-bed w-5 mr-3 text-violet-400"></i> Rooms
        </a>
        <a href="<?php echo APP_URL; ?>/admin/users.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-sm font-medium <?php echo activeLink('users.php',$current); ?>">
            <i class="fas fa-users w-5 mr-3 text-emerald-400"></i> Users
        </a>
    </nav>
    <div class="mt-auto p-4 border-t border-slate-800">
        <div class="flex items-center mb-3">
            <div class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-300">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="ml-3 text-xs">
                <p class="font-semibold text-slate-200">Admin</p>
                <p class="text-slate-500">Privileged access</p>
            </div>
        </div>
        <a href="<?php echo APP_URL; ?>/logout.php" class="w-full text-center bg-red-600 hover:bg-red-700 text-white p-2 rounded-lg text-sm font-semibold transition-colors">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </a>
    </div>
</aside>
<button id="sidebarToggle" class="fixed bottom-5 right-5 md:hidden bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-full shadow-lg z-40">
    <i class="fas fa-bars"></i>
</button>
<script>
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    function openSidebar(){ sidebar.classList.add('open'); }
    function closeSidebar(){ sidebar.classList.remove('open'); }
    toggleBtn.addEventListener('click', ()=> { if(sidebar.classList.contains('open')){ closeSidebar(); } else { openSidebar(); } });
    closeBtn.addEventListener('click', closeSidebar);
</script>
