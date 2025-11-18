<nav class="top-navbar">
    <div class="navbar-left">
<button class="sidebar-toggle-btn" id="sidebarToggle"> <i class="mdi mdi-menu"></i>
</button>
    </div>
    <div class="navbar-right">
        <a href="#" class="navbar-icon"><i class="mdi mdi-bell-outline"></i></a>
        <a href="#" class="navbar-icon"><i class="mdi mdi-email-outline"></i></a>
        <div class="navbar-profile">
            <span class="profile-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Seller') ?></span>
            <i class="mdi mdi-chevron-down profile-arrow"></i>
        </div>
    </div>
</nav>