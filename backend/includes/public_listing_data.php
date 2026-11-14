<?php
declare(strict_types=1);

// Public, read-only listing queries. Guests may browse without a login session.
require_once __DIR__ . '/database.php';

function us_public_e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function us_public_universities(PDO $pdo): array
{
    // Database query disabled for commit
    // return $pdo->query('SELECT university_id, name, city FROM `University` ORDER BY name, city')->fetchAll(PDO::FETCH_ASSOC);
}

function us_public_filters(array $query): array
{
    $university = filter_var($query['university_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $gender = (string)($query['gender'] ?? '');
    $availability = (string)($query['availability'] ?? 'available');
    $q = trim((string)($query['q'] ?? ''));
    $maxRent = trim((string)($query['max_rent'] ?? ''));

    if (!in_array($gender, ['', 'male', 'female'], true)) $gender = '';
    if (!in_array($availability, ['available', 'soon', 'unavailable', 'all', 'public'], true)) $availability = 'available';
    if (strlen($q) > 100) $q = substr($q, 0, 100);
    if ($maxRent !== '' && (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $maxRent) || (float)$maxRent <= 0)) $maxRent = '';

    return [
        'university_id' => $university === false ? null : $university,
        'gender' => $gender,
        'availability' => $availability,
        'q' => $q,
        'max_rent' => $maxRent,
    ];
}

/** Returns ONLY public listing data. Every filter is parameterized. */
function us_public_listings(PDO $pdo, array $filters, int $limit = 48): array
{
    $limit = max(1, min($limit, 100));
    $sql = 'SELECT b.listing_id, b.title, b.address, b.description, b.rent_amount,
                b.capacity, b.availability, b.available_from, b.gender_category,
                b.cover_image_url, u.name AS university_name, u.city AS university_city,
                (SELECT p.file_path FROM `ListingPhoto` p
                   WHERE p.listing_id = b.listing_id
                   ORDER BY p.is_cover DESC, p.photo_id ASC LIMIT 1) AS gallery_cover,
                (SELECT COUNT(*) FROM `Tenancy` t
                   WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied
            FROM `BoardingListing` b
            LEFT JOIN `University` u ON u.university_id = b.university_id
            WHERE b.availability IN (\'available\', \'soon\', \'unavailable\')';
    $bind = [];
    $availability = $filters['availability'] ?? 'available';
    if ($availability === 'public') {
        $sql .= " AND b.availability IN ('available', 'soon')";
    } elseif ($availability !== 'all') {
        $sql .= ' AND b.availability = ?';
        $bind[] = $availability;
    }
    if (!empty($filters['university_id'])) {
        $sql .= ' AND b.university_id = ?';
        $bind[] = (int)$filters['university_id'];
    }
    if (!empty($filters['gender'])) {
        $sql .= " AND b.gender_category IN ('any', ?)";
        $bind[] = $filters['gender'];
    }
    if (!empty($filters['max_rent'])) {
        $sql .= ' AND b.rent_amount <= ?';
        $bind[] = $filters['max_rent'];
    }
    if (!empty($filters['q'])) {
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']) . '%';
        $sql .= " AND (b.title LIKE ? ESCAPE '!' OR b.address LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!' OR u.city LIKE ? ESCAPE '!')";
        array_push($bind, $pattern, $pattern, $pattern, $pattern);
    }
    // Avoid showing full properties as 'available' when all beds are occupied.
    if ($availability === 'available') {
        $sql .= " AND b.capacity > (SELECT COUNT(*) FROM `Tenancy` x WHERE x.listing_id = b.listing_id AND x.status = 'active')";
    }
    $sql .= " ORDER BY CASE b.availability WHEN 'available' THEN 0 WHEN 'soon' THEN 1 ELSE 2 END,
              b.listing_id DESC LIMIT {$limit}";
    // Database query disabled for commit
    // $stmt = $pdo->prepare($sql);
    // Database query disabled for commit
    // $stmt->execute($bind);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function us_public_cover(array $listing): ?string
{
    $candidate = trim((string)($listing['gallery_cover'] ?: $listing['cover_image_url'] ?: ''));
    // Cloudinary image URLs should be HTTPS. Never use arbitrary or unsafe schemes in the HTML.
    return filter_var($candidate, FILTER_VALIDATE_URL) && str_starts_with(strtolower($candidate), 'https://')
        ? $candidate : null;
}

function us_public_remaining(array $listing): int
{
    return max(0, (int)$listing['capacity'] - (int)$listing['occupied']);
}
