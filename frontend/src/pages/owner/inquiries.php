
<?php
$inquiries = [
    [
        'id' => 1,
        'student_name' => 'Kavindu Perera',
        'email' => 'kavindu@example.com',
        'phone' => '077 123 4567',
        'university' => 'University of Moratuwa',
        'listing' => 'Comfortable Student Boarding',
        'date' => '2026-11-06',
        'message' => 'Hello, I am interested in this boarding place. Is a room currently available?',
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'student_name' => 'Nethmi Fernando',
        'email' => 'nethmi@example.com',
        'phone' => '071 234 5678',
        'university' => 'University of Moratuwa',
        'listing' => 'Modern Girls Boarding',
        'date' => '2026-11-05',
        'message' => 'I would like to know more about the facilities and monthly rent.',
        'status' => 'accepted'
    ],
    [
        'id' => 3,
        'student_name' => 'Sahan Silva',
        'email' => 'sahan@example.com',
        'phone' => '076 345 6789',
        'university' => 'University of Colombo',
        'listing' => 'Comfortable Student Boarding',
        'date' => '2026-11-04',
        'message' => 'Is this boarding place close to the university? I am looking for accommodation next month.',
        'status' => 'rejected'
    ],
    [
        'id' => 4,
        'student_name' => 'Tharushi Jayawardena',
        'email' => 'tharushi@example.com',
        'phone' => '075 456 7890',
        'university' => 'University of Moratuwa',
        'listing' => 'Budget Student Rooms',
        'date' => '2026-11-07',
        'message' => 'I am interested in booking a room. Please let me know if it is available.',
        'status' => 'pending'
    ]
];

$successMessage = '';
$errorMessage = '';

// Demo action: this only changes the status for the current request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiryId = filter_input(INPUT_POST, 'inquiry_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$inquiryId || !in_array($action, ['accept', 'reject'], true)) {
        $errorMessage = 'Invalid inquiry action.';
    } else {
        $found = false;

        foreach ($inquiries as &$inquiry) {
            if ($inquiry['id'] === $inquiryId) {
                $found = true;

                if ($inquiry['status'] !== 'pending') {
                    $errorMessage = 'This inquiry has already been reviewed.';
                } else {
                    $inquiry['status'] = $action === 'accept' ? 'accepted' : 'rejected';
                    $successMessage = 'Inquiry ' . $action . 'ed successfully (demo only).';
                }

                break;
            }
        }

        unset($inquiry);

        if (!$found) {
            $errorMessage = 'Inquiry not found.';
        }
    }
}

$totalInquiries = count($inquiries);
$pendingCount = 0;
$acceptedCount = 0;
$rejectedCount = 0;

foreach ($inquiries as $inquiry) {
    if ($inquiry['status'] === 'pending') {
        $pendingCount++;
    } elseif ($inquiry['status'] === 'accepted') {
        $acceptedCount++;
    } else {
        $rejectedCount++;
    }
}

include __DIR__ . '/../../components/header.php';
?>

<head>
    <link rel="stylesheet" href="/UNISTAY/frontend/src/styles/style.css">
</head>

<main class="owner-inquiries-page">
    <section class="owner-page-heading">
        <div>
            <span class="owner-page-eyebrow">OWNER PORTAL</span>
            <h1>Student Inquiries</h1>
            <p>Review and manage students interested in your boarding places.</p>
        </div>
    </section>

    <?php if ($successMessage): ?>
        <div class="owner-alert owner-alert-success">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="owner-alert owner-alert-error">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="owner-inquiry-stats">
        <div class="owner-inquiry-stat">
            <span>Total Inquiries</span>
            <strong><?= $totalInquiries ?></strong>
        </div>
        <div class="owner-inquiry-stat">
            <span>Pending</span>
            <strong><?= $pendingCount ?></strong>
        </div>
        <div class="owner-inquiry-stat">
            <span>Accepted</span>
            <strong><?= $acceptedCount ?></strong>
        </div>
        <div class="owner-inquiry-stat">
            <span>Rejected</span>
            <strong><?= $rejectedCount ?></strong>
        </div>
    </section>

    <section class="owner-inquiry-list">
        <div class="owner-section-heading">
            <div>
                <h2>Received Inquiries</h2>
                <p>Student requests for your boarding listings.</p>
            </div>
        </div>

        <?php foreach ($inquiries as $inquiry): ?>
            <article class="owner-inquiry-card">
                <div class="owner-inquiry-card-top">
                    <div>
                        <span class="owner-inquiry-id">
                            INQUIRY #<?= $inquiry['id'] ?>
                        </span>
                        <h3><?= htmlspecialchars($inquiry['student_name']) ?></h3>
                        <p class="owner-inquiry-university">
                            <?= htmlspecialchars($inquiry['university']) ?>
                        </p>
                    </div>

                    <span class="owner-inquiry-status status-<?= $inquiry['status'] ?>">
                        <?= ucfirst($inquiry['status']) ?>
                    </span>
                </div>

                <div class="owner-inquiry-details">
                    <div>
                        <span>Email</span>
                        <strong><?= htmlspecialchars($inquiry['email']) ?></strong>
                    </div>
                    <div>
                        <span>Phone</span>
                        <strong><?= htmlspecialchars($inquiry['phone']) ?></strong>
                    </div>
                    <div>
                        <span>Interested Listing</span>
                        <strong><?= htmlspecialchars($inquiry['listing']) ?></strong>
                    </div>
                    <div>
                        <span>Submitted Date</span>
                        <strong><?= htmlspecialchars($inquiry['date']) ?></strong>
                    </div>
                </div>

                <div class="owner-inquiry-message">
                    <span>Student Message</span>
                    <p><?= htmlspecialchars($inquiry['message']) ?></p>
                </div>

                <?php if ($inquiry['status'] === 'pending'): ?>
                    <div class="owner-inquiry-actions">
                        <form method="POST">
                            <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="owner-inquiry-accept">
                                Accept Inquiry
                            </button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="owner-inquiry-reject">
                                Reject Inquiry
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php include __DIR__ . '/../../components/footer.php'; ?>
