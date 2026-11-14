<?php
/** @var array $usListings Supplied by the page that includes this component. */
require_once __DIR__ . '/../../../backend/includes/public_listing_data.php';
?>
<div class="us-listing-grid">
<?php foreach ($usListings as $place):
    $cover = us_public_cover($place);
    $remaining = us_public_remaining($place);
    $isAvailable = $place['availability'] === 'available' && $remaining > 0;
    $statusText = $isAvailable ? 'Available · ' . $remaining . ' space(s)' : ($place['availability'] === 'soon' ? 'Available soon' : 'Currently unavailable');
?>
    <article class="us-listing-card">
        <a class="us-listing-photo" href="/public/listing.php?id=<?= (int)$place['listing_id'] ?>" aria-label="View boarding details">
            <?php if ($cover): ?>
                <img src="<?= us_public_e($cover) ?>" alt="Photo of <?= us_public_e($place['title'] ?: 'boarding place') ?>" loading="lazy">
            <?php else: ?>
                <div class="us-listing-placeholder">UniStay</div>
            <?php endif; ?>
            <span class="us-listing-status <?= $isAvailable ? 'is-available' : 'is-waiting' ?>"><?= us_public_e($statusText) ?></span>
        </a>
        <div class="us-listing-info">
            <p class="us-listing-university"><?= us_public_e($place['university_name'] ?: 'University boarding') ?></p>
            <h3><?= us_public_e($place['title'] ?: 'Boarding place #' . $place['listing_id']) ?></h3>
            <p class="us-listing-address"><?= us_public_e($place['address'] ?: $place['university_city'] ?: 'Location not specified') ?></p>
            <div class="us-listing-bottom">
                <div><strong>LKR <?= number_format((float)$place['rent_amount'], 2) ?></strong><span> / month</span></div>
                <a class="us-listing-details" href="/public/listing.php?id=<?= (int)$place['listing_id'] ?>">View Details</a>
            </div>
        </div>
    </article>
<?php endforeach; ?>
</div>
