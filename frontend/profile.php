<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/includes/profile_module.php';
[$role, $userId] = profile_role_and_id();
$pdo = db();
$path = '/profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $name = profile_validate_name($_POST['full_name'] ?? '');
        $phone = profile_validate_phone($_POST['phone'] ?? '');
        $share = $role === 'owner' && ($_POST['share_phone'] ?? '') === '1' ? 1 : 0;
        if ($share && $phone === null) {
            throw new InvalidArgumentException('Add a contact phone number before enabling sharing.');
        }
        // Only this signed-in user's editable fields may change.
        $q = $pdo->prepare('UPDATE `User` SET full_name = ?, phone = ?, share_phone_with_inquirers = ? WHERE user_id = ? AND role = ?');
        $q->execute([$name, $phone, $share, $userId, $role]);
        module_flash('Profile updated successfully.');
    } catch (InvalidArgumentException $e) {
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        module_fail($e, $path);
    }
    module_redirect($path);
}

$q = $pdo->prepare('SELECT user_id, full_name, email, phone, role, share_phone_with_inquirers FROM `User` WHERE user_id = ? AND role = ? LIMIT 1');
$q->execute([$userId, $role]);
$profile = $q->fetch(PDO::FETCH_ASSOC);
if (!$profile) { http_response_code(404); exit('Account not found.'); }
module_header('My Profile', $role);
?>
<div class="profile-layout">
    <section class="panel profile-panel">
      <div class="profile-heading"><div class="profile-avatar" aria-hidden="true"><?= module_escape(strtoupper(substr(trim((string)$profile['full_name']) ?: 'U', 0, 1))) ?></div><div>
        <h2><?= module_escape((string)($profile['full_name'] ?: 'UniStay Member')) ?></h2>
        <p class="muted"><?= module_escape(profile_display_role($role)) ?> · Account #<?= (int)$profile['user_id'] ?></p>
      </div></div>
      <form method="post" action="/profile.php">
        <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
        <div class="profile-fields">
          <div><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="255" required value="<?= module_escape((string)$profile['full_name']) ?>" autocomplete="name"></div>
          <div><label for="phone">Phone number (optional)</label><input id="phone" name="phone" type="tel" maxlength="20" value="<?= module_escape((string)($profile['phone'] ?? '')) ?>" autocomplete="tel" placeholder="+94 77 123 4567"></div>
          <div><label for="email">Login email</label><input id="email" type="email" value="<?= module_escape((string)$profile['email']) ?>" readonly aria-describedby="email_hint"><p id="email_hint" class="muted small">Managed by your login identity. It cannot be changed here.</p></div>
          <div><label for="role">Account type</label><input id="role" value="<?= module_escape(profile_display_role($role)) ?>" readonly></div>
        </div>
        <?php if ($role === 'owner'): ?>
          <div class="profile-consent"><label class="profile-check"><input type="checkbox" name="share_phone" value="1" <?= (int)$profile['share_phone_with_inquirers'] === 1 ? 'checked' : '' ?>> Allow students with an open or confirmed inquiry for my boarding to view my phone number</label>
          <p class="muted small">Off by default. Students browsing your boarding cannot see your number until they have sent an inquiry, and you can turn sharing off at any time.</p></div>
        <?php else: ?>
          <p class="muted small">Your student account remains yours, even when you become a tenant. Property owners cannot edit it.</p>
        <?php endif; ?>
        <div class="actions"><button type="submit">Save profile</button><a class="btn secondary" href="<?= $role === 'owner' ? '/owner/dashboard.php' : '/student/dashboard.php' ?>">Back to dashboard</a></div>
      </form>
    </section>
    <aside class="panel profile-aside"><h2>Privacy &amp; access</h2><p>Only you can update your name and phone using this page. Your account email, Google identity, and account role cannot be edited here.</p><p><?= $role === 'owner' ? 'Student contact sharing is optional. Your phone is revealed only to authenticated students with eligible inquiries for your properties.' : 'An owner approves accommodation inquiries, but never creates or controls your student account.' ?></p></aside>
</div>
<?php module_footer(); ?>
