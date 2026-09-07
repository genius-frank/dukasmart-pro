<?php
require_once 'auth.php';
require_once 'db.php';
$page_title = 'Add Customer';
require_once 'layout_header.php';
?>

<div style="max-width:500px;">
  <div class="card">
    <form method="POST" action="save_customer.php">
      <div class="form-group">
        <label>Customer Name *</label>
        <input type="text" name="name" required placeholder="e.g. Mama Achieng" autofocus>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" placeholder="07XX XXX XXX">
      </div>
      <div class="form-group">
        <label>Address (optional)</label>
        <input type="text" name="address" placeholder="e.g. Near the church, Kawangware">
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-success">✓ Save Customer</button>
        <a href="customers.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'layout_footer.php'; ?>
