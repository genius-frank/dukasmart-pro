<?php
require_once 'auth.php';
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect_to('dashboard.php');
}

$page_title = 'Billing & Plans';
$s = get_settings();
$plans = [
    'starter' => ['name' => 'Starter', 'price' => 500, 'description' => 'For a small shop getting organized.', 'features' => ['POS sales', 'Products and stock alerts', 'Credit book', 'Daily reports']],
    'growth' => ['name' => 'Growth', 'price' => 900, 'description' => 'For a busy shop with a small team.', 'features' => ['Everything in Starter', 'Cashier accounts', 'Profit reports', 'Expenses and monthly reports']],
    'pro' => ['name' => 'Pro', 'price' => 1500, 'description' => 'For shops ready to scale confidently.', 'features' => ['Everything in Growth', 'Priority support', 'Advanced reports', 'Early access to new features']],
];
$selected_plan = $s['plan_code'] ?? 'starter';
$trial_end = !empty($s['trial_started']) ? date('Y-m-d', strtotime($s['trial_started'] . ' +30 days')) : null;
$today = date('Y-m-d');
$active = !empty($s['subscription_expires']) && $s['subscription_expires'] >= $today;
$latest_payment = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT * FROM subscription_payments ORDER BY submitted_at DESC LIMIT 1'));
$plan_limits = [
  'starter' => ['products' => '100 products', 'cashiers' => '1 cashier', 'reports' => 'Daily reports'],
  'growth' => ['products' => '1,000 products', 'cashiers' => '5 cashiers', 'reports' => 'Daily, monthly and profit reports'],
  'pro' => ['products' => '10,000 products', 'cashiers' => '25 cashiers', 'reports' => 'Advanced reports and priority support'],
];

require_once 'layout_header.php';
?>

<div class="card" style="border-left:4px solid var(--accent);">
  <div class="card-title">Billing & Plans</div>
  <p style="color:var(--muted);max-width:760px;">DukaSmart Pro is a subscription service. Your shop gets one 30-day trial, then you choose a monthly plan. Your data stays in your shop account while the subscription is active.</p>
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;font-size:13px;">
    <span class="pill <?= $active ? 'pill-green' : 'pill-gray' ?>"><?= $active ? 'Subscription active' : 'Trial or payment required' ?></span>
    <span class="pill pill-blue">Current plan: <?= htmlspecialchars($plans[$selected_plan]['name'] ?? 'Starter') ?></span>
    <?php if ($trial_end && !$active): ?><span class="pill pill-gray">Trial ends: <?= htmlspecialchars($trial_end) ?></span><?php endif; ?>
    <?php if ($active): ?><span class="pill pill-gray">Renews/ends: <?= htmlspecialchars($s['subscription_expires']) ?></span><?php endif; ?>
  </div>
</div>

<div class="stats-grid" style="margin-top:20px;">
<?php foreach ($plans as $code => $plan): ?>
  <div class="card" style="border-top:4px solid <?= $code === 'growth' ? 'var(--primary)' : 'var(--border)' ?>;">
    <div class="card-title"><?= htmlspecialchars($plan['name']) ?> <?= $code === 'growth' ? '<span class="pill pill-blue">Most popular</span>' : '' ?></div>
    <p style="color:var(--muted);min-height:42px;"><?= htmlspecialchars($plan['description']) ?></p>
    <div style="font-size:30px;font-weight:700;margin:14px 0;">KES <?= number_format($plan['price']) ?><small style="font-size:12px;color:var(--muted);"> / month</small></div>
    <ul style="display:grid;gap:8px;padding-left:20px;color:var(--muted);min-height:120px;">
    <?php foreach ($plan['features'] as $feature): ?><li><?= htmlspecialchars($feature) ?></li><?php endforeach; ?>
    <li><?= htmlspecialchars($plan_limits[$code]['products']) ?></li>
    <li><?= htmlspecialchars($plan_limits[$code]['cashiers']) ?></li>
    <li><?= htmlspecialchars($plan_limits[$code]['reports']) ?></li>
    </ul>
    <form method="POST" action="select_plan.php">
      <input type="hidden" name="plan_code" value="<?= htmlspecialchars($code) ?>">
      <button class="btn <?= $selected_plan === $code ? 'btn-success' : 'btn-primary' ?> btn-block" type="submit"><?= $selected_plan === $code ? 'Selected Plan' : 'Choose ' . htmlspecialchars($plan['name']) ?></button>
    </form>
  </div>
<?php endforeach; ?>
</div>

<div class="card mt-2">
  <div class="card-title">How payment works today</div>
  <p style="color:var(--muted);line-height:1.8;">Choose a plan above, then contact DukaSmart support for payment instructions. After payment is confirmed, the administrator activates your plan. Automated M-Pesa renewals can be connected later when the business has a payment account.</p>
  <form method="POST" action="submit_payment.php" style="margin-top:18px;">
    <div class="form-grid">
      <div class="form-group">
        <label>Plan to activate</label>
        <select name="plan_code" required>
          <?php foreach ($plans as $code => $plan): ?><option value="<?= htmlspecialchars($code) ?>" <?= $selected_plan === $code ? 'selected' : '' ?>><?= htmlspecialchars($plan['name']) ?> - KES <?= number_format($plan['price']) ?>/month</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>M-Pesa/payment reference</label>
        <input type="text" name="payment_reference" required minlength="4" maxlength="80" placeholder="e.g. QWE123ABC456">
      </div>
    </div>
    <button class="btn btn-success" type="submit">Submit Payment Reference</button>
  </form>
  <?php if ($latest_payment): ?><p class="text-muted" style="font-size:12px;margin-top:14px;">Latest request: <strong><?= htmlspecialchars($latest_payment['plan_code']) ?></strong> · <?= htmlspecialchars($latest_payment['status']) ?> · <?= htmlspecialchars($latest_payment['submitted_at']) ?></p><?php endif; ?>
</div>

<?php require_once 'layout_footer.php'; ?>