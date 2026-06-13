<form class="admin-filters" method="GET" action="dashboard.php">
    <div class="filter-field search-field">
        <label for="department-search">Cari Aspirasi</label>
        <input type="search" id="department-search" name="search" value="<?= e($search); ?>"
            placeholder="Cari judul atau isi aspirasi">
    </div>

    <div class="filter-field">
        <label for="department-category">Kategori</label>
        <select id="department-category" name="category" data-auto-submit>
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $categoryOption): ?>
                <option value="<?= e($categoryOption); ?>" <?= $category === $categoryOption ? 'selected' : ''; ?>>
                    <?= e($categoryOption); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="department-status">Status</label>
        <select id="department-status" name="status" data-auto-submit>
            <option value="">Semua status</option>
            <?php foreach (['in_process', 'communicated', 'resolved'] as $statusValue): ?>
                <option value="<?= e($statusValue); ?>" <?= $status === $statusValue ? 'selected' : ''; ?>>
                    <?= e($statusLabels[$statusValue]['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
