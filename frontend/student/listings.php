<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$studentId = module_require('student');
$pdo = db();
$q = $pdo->query("SELECT b.listing_id, b.university_id, b.rent_amount, b.capacity, b.availability, (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = 'active') AS occupied FROM `BoardingListing` b WHERE b.availability = 'available' AND b.capacity > 0 ORDER BY b.listing_id DESC LIMIT 100");
$listings = $q->fetchAll();
module_header('Browse Boarding Places', 'student');
?>
<p class="muted">These cards use the existing TiDB schema. The database team can add titles, addresses, amenities and photographs later without changing the inquiry ownership model.</p>
<section class="cards">
<?php foreach ($listings as $item): ?>
    <?php if ((int)$item['occupied'] >= (int)$item['capacity']) continue; ?>
    <article class="card">
        <span class="tag">Listing #<?= (int)$item['listing_id'] ?></span>
        <h2 style="margin-top:13px">University #<?= (int)$item['university_id'] ?></h2>
        <p>Rent <strong><?= module_escape((string)$item['rent_amount']) ?></strong></p>
        <p class="muted">Places left: <?= max(0, (int)$item['capacity'] - (int)$item['occupied']) ?> of <?= (int)$item['capacity'] ?> · <?= module_escape((string)$item['availability']) ?></p>
        <form method="get" action="/student/inquiries.php"><input type="hidden" name="listing_id" value="<?= (int)$item['listing_id'] ?>"><button type="submit">Send inquiry</button></form>
    </article>
<?php endforeach; ?>
<?php if (!$listings): ?><div class="panel">No available listings yet. Ask an owner to publish one.</div><?php endif; ?>
</section>
<?php module_footer(); ?>
