<?php
require_once 'auth.php';
require_once 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php?msg=' . urlencode('Only admin can access settings') . '&type=danger');
}
$page_title = 'Settings';
$s = get_settings();

// Load own profile for the "your profile" section
$my_profile = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=" . (int)$_SESSION['user_id']));
$cashiers = mysqli_query($conn, "SELECT * FROM users ORDER BY role DESC, full_name ASC");

require_once 'layout_header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <!-- Shop Info -->
  <div class="card">
    <div class="card-title">🏪 Shop Information</div>
    <form method="POST" action="update_settings.php">
      <div class="form-group">
        <label>Shop Name</label>
        <input type="text" name="shop_name" value="<?= htmlspecialchars($s['shop_name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="shop_address" value="<?= htmlspecialchars($s['shop_address'] ?? '') ?>" placeholder="e.g. Kawangware, Nairobi">
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="shop_phone" value="<?= htmlspecialchars($s['shop_phone'] ?? '') ?>" placeholder="07XX XXX XXX">
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Currency</label>
          <select name="currency">
            <?php foreach (['KES','UGX','TZS','NGN','GHS','USD','ZAR'] as $c): ?>
            <option value="<?= $c ?>" <?= $s['currency']===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tax Rate (%)</label>
          <input type="number" name="tax_rate" step="0.01" min="0" value="<?= $s['tax_rate'] ?? 0 ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Receipt Footer Message</label>
        <textarea name="receipt_footer" rows="2"><?= htmlspecialchars($s['receipt_footer'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">✓ Save Shop Info</button>
    </form>
  </div>

  <div>
    <!-- Your Profile — update name -->
    <div class="card">
      <div class="card-title">👤 Your Profile</div>
      <form method="POST" action="update_profile.php">
        <div class="form-group">
          <label>Your Full Name</label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($my_profile['full_name'] ?? '') ?>" required placeholder="e.g. John Kamau">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($my_profile['username'] ?? '') ?>" required placeholder="e.g. john">
        </div>
        <button type="submit" class="btn btn-primary">✓ Update Profile</button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="card" id="security">
      <div class="card-title">🔒 Change Password</div>
      <form method="POST" action="change_password.php">
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" required minlength="6">
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-warning">🔑 Update Password</button>
      </form>
    </div>
  </div>
</div>

<!-- Team management -->
<div class="card mt-2" id="team">
  <div class="card-title">👥 Team Members</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php mysqli_data_seek($cashiers, 0); while ($u = mysqli_fetch_assoc($cashiers)): ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
          <td class="text-muted">@<?= htmlspecialchars($u['username']) ?></td>
          <td><span class="pill <?= $u['role']==='admin'?'pill-blue':'pill-gray' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><span class="pill <?= $u['is_active']?'pill-green':'pill-red' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <a href="toggle_user.php?id=<?= $u['id'] ?>"
               onclick="return confirm('<?= $u['is_active']?'Deactivate':'Activate' ?> <?= htmlspecialchars($u['full_name']) ?>?')"
               class="btn btn-sm <?= $u['is_active']?'btn-ghost':'btn-success' ?>">
              <?= $u['is_active']?'Deactivate':'Activate' ?>
            </a>
            <?php else: ?>
            <span class="text-muted" style="font-size:12px;">That's you</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Add cashier -->
  <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
    <div class="card-title">➕ Add New Cashier</div>
    <form method="POST" action="add_user.php">
      <div class="form-grid-3">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" required placeholder="e.g. Mary Wanjiru">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required placeholder="mary">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required minlength="6" placeholder="Min 6 characters">
        </div>
      </div>
      <button type="submit" class="btn btn-success">➕ Add Cashier</button>
    </form>
  </div>

  <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
    <div class="card-title">🔑 Reset a Team Password</div>
    <p class="text-muted" style="font-size:12px;margin-bottom:14px;">Verify the staff member in person or through your trusted shop process before issuing a new password.</p>
    <form method="POST" action="admin_reset_password.php">
      <div class="form-grid">
        <div class="form-group">
          <label>Team member</label>
          <select name="user_id" required>
            <option value="">Select a user</option>
            <?php mysqli_data_seek($cashiers, 0); while ($u = mysqli_fetch_assoc($cashiers)): ?>
            <?php if ($u['id'] != $_SESSION['user_id']): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (@<?= htmlspecialchars($u['username']) ?>)</option><?php endif; ?>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Temporary password</label>
          <input type="password" name="new_password" minlength="6" required autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn btn-warning">Reset Team Password</button>
    </form>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
