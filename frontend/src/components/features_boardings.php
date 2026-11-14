<?php
require_once __DIR__ . '/../../../backend/includes/public_listing_data.php';
$usListings = [];
$usListingsFailed = false;
try {
    $usListings = us_public_listings(db(), ['availability' => 'public'], 6);
} catch (Throwable $e) {
    error_log('UniStay homepage listings: ' . $e->getMessage());
    $usListingsFailed = true;
}
?>
<section class="section section-light us-public-featured" aria-label="Featured boarding places">
    <div class="container">
        <div class="section-heading">
            <h2>EXPLORE BOARDING PLACES</h2>
            <p>Find real accommodation options shared by boarding owners.</p>
        </div>
        <?php if ($usListingsFailed): ?>
            <p class="us-listing-message">Boarding places could not be loaded right now. Please try again shortly.</p>
        <?php elseif (!$usListings): ?>
            <p class="us-listing-message">No boarding places are available yet. Check back soon.</p>
        <?php else: ?>
            <?php require __DIR__ . '/public_listing_cards.php'; ?>
        <?php endif; ?>
        <div class="us-more-wrap"><a href="/public/listings.php" class="btn btn-primary">Browse All Boarding Places</a></div>
    </div>
</section>
