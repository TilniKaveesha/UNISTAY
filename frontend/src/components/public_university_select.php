<?php
require_once __DIR__ . '/../../../backend/includes/public_listing_data.php';
$usUniversities = [];
try {
    $usUniversities = us_public_universities(db());
} catch (Throwable $e) {
    error_log('UniStay university dropdown: ' . $e->getMessage());
}
?>
<select name="university_id" id="university" class="form-control">
    <option value="">All universities</option>
    <?php foreach ($usUniversities as $school): ?>
        <option value="<?= (int)$school['university_id'] ?>"><?= us_public_e($school['name']) ?></option>
    <?php endforeach; ?>
</select>
