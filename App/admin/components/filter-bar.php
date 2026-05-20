<?php
// ============================================================
// RuangKita - Component Filter Bar
// File: App/admin/components/filter-bar.php
// ============================================================
?>
<form class="admin-filters" method="GET" action="dashboard.php">
    <div class="filter-field search-field">
        <label for="search">Search Aspirasi</label>
        <input type="search" id="search" name="search" placeholder="Cari judul, isi, atau mahasiswa" value="<?= e($search); ?>">
    </div>

    <div class="filter-field">
        <label for="category">Kategori</label>
        <select id="category" name="category">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category); ?>" <?= $categoryFilter === $category ? 'selected' : ''; ?>>
                    <?= e($category); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Semua status</option>
            <?php foreach ($statusOptions as $value => $label): ?>
                <option value="<?= e($value); ?>" <?= $statusFilter === $value ? 'selected' : ''; ?>>
                    <?= e($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="sort">Sort</label>
        <select id="sort" name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Terbaru</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Terlama</option>
            <option value="votes" <?= $sort === 'votes' ? 'selected' : ''; ?>>Paling banyak vote</option>
        </select>
    </div>

    <button type="submit" class="filter-submit">Terapkan</button>
</form>
