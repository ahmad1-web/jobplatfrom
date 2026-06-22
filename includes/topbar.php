<?php
/**
 * includes/topbar.php
 * Shared top navigation bar
 * Requires: $page_title (string)
 */
?>
<header class="topbar">
    <button class="topbar-toggle" onclick="toggleSidebar()" title="Toggle Menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title">
        <?= e($page_title ?? 'Dashboard') ?>
        <?php if (!empty($page_subtitle)): ?>
        <div class="topbar-subtitle"><?= e($page_subtitle) ?></div>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <span class="topbar-date">
            <i class="bi bi-calendar3"></i>
            <?= date('D, d M Y') ?>
        </span>
        <a href="<?= ($user_role==='admin') ? '../' : '../' ?>logout.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</header>
