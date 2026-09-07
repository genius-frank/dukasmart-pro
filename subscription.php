<?php
// ================================================================
// DukaSmart Pro — Subscription Gate
// How it works:
//   1. New shop gets a 30-day free trial automatically
//   2. After trial, they see the payment page
//   3. They pay via M-Pesa to your till/number
//   4. You run extend_subscription.php?days=30&key=SECRET to unlock them
// ================================================================

function check_subscription() {
    global $conn;

    // Owners must subscribe too. Billing remains available after expiry.
    $current_page = basename($_SERVER['PHP_SELF'] ?? '');
    if ($current_page === 'billing.php' || $current_page === 'logout.php') return;

    $s = get_settings();

    // Set trial start date if not set
    if (empty($s['trial_started'])) {
        mysqli_query($conn, "UPDATE settings SET trial_started=CURDATE() WHERE id=1");
        return; // First visit — allow
    }

    $trial_end = date('Y-m-d', strtotime($s['trial_started'] . ' +30 days'));
    $today     = date('Y-m-d');
    $sub_exp   = $s['subscription_expires'] ?? null;

    // Active subscription
    if ($sub_exp && $sub_exp >= $today) return;

    // Still in trial
    if ($today <= $trial_end) return;

    // Expired — show payment page
    $days_overdue = (int)round((strtotime($today) - strtotime($trial_end)) / 86400);
    require_once 'layout_header.php';
    ?>
    <div style="max-width:520px;margin:40px auto;">
      <div class="card" style="text-align:center;padding:40px;">
        <div style="font-size:48px;margin-bottom:16px;">🔒</div>
        <h2 style="font-family:'DM Serif Display',serif;font-size:24px;margin-bottom:8px;">
          Choose Your Plan
        </h2>
        <p style="color:var(--muted);margin-bottom:24px;line-height:1.7;">
          Your 30-day free trial ended <?= $days_overdue ?> day<?= $days_overdue!=1?'s':'' ?> ago.
          Choose a plan to keep using DukaSmart Pro.
        </p>

        <div style="background:var(--bg);border-radius:12px;padding:20px;margin-bottom:24px;text-align:left;">
          <div style="font-size:12px;color:var(--muted);margin-bottom:4px;font-weight:600;">PLANS FROM KES 500/month</div>
          <div style="font-size:28px;font-weight:700;color:var(--primary);margin-bottom:12px;">Flexible monthly plans</div>
          <div style="font-size:14px;line-height:2;">
            1. Open Billing & Plans<br>
            2. Choose the plan for your shop<br>
            3. Pay using the support instructions<br>
            4. Your administrator activates the subscription
          </div>
        </div>

        <a href="billing.php" class="btn btn-primary btn-block">View Plans & Billing</a>

        <p style="font-size:12px;color:var(--muted);">
          Your data is safe. Everything resumes the moment your subscription is activated.
        </p>
      </div>
    </div>
    <?php
    require_once 'layout_footer.php';
    exit();
}
