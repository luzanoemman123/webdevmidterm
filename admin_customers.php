<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_block'])) {
    $customerId = (int) ($_POST['customer_id'] ?? 0);
    $blocked = (int) ($_POST['blocked'] ?? 0);

    if ($customerId > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param("ii", $blocked, $customerId);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: admin_customers.php');
    exit();
}

$customers = $conn->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.user_id = u.id) AS total_spent
    FROM users u
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

function customerInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .customer-search-wrap {
            position: relative;
            max-width: 320px;
            margin: 0 0 20px;
        }
        .customer-search-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }
        #customer-search {
            width: 100%;
            background: #05090c;
            border: 1px solid rgba(56, 130, 255, 0.25);
            border-radius: 6px;
            color: #fff;
            font-family: inherit;
            font-size: 12px;
            padding: 10px 12px 10px 34px;
            outline: none;
            transition: border-color 0.2s ease;
        }
        #customer-search:focus { border-color: rgba(56, 130, 255, 0.7); }
        #customer-search::placeholder { color: rgba(255, 255, 255, 0.35); }
        #no-customer-results { display: none; }

        .customer-name-cell { display: flex; align-items: center; gap: 10px; }
        .customer-avatar {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3c82ff, #7c5cff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
        }
        .customer-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .customer-status {
            border-radius: 999px;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .4px;
            padding: 5px 8px;
        }
        .customer-status.active { background: rgba(70, 190, 145, .14); color: #72d6ad; }
        .customer-status.blocked { background: rgba(238, 107, 77, .14); color: #ffb4a2; }
        .block-form { margin: 0; }
        .block-button {
            background: transparent;
            border: 1px solid rgba(238, 107, 77, .55);
            color: #ffb4a2;
            cursor: pointer;
            font: inherit;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 10px;
        }
        .block-button:hover { background: rgba(238, 107, 77, .12); }
        .block-button.unblock { border-color: rgba(70, 190, 145, .55); color: #72d6ad; }
        .block-button.unblock:hover { background: rgba(70, 190, 145, .12); }
    </style>
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">CUSTOMERS</p><h2>Registered accounts</h2></div></div>

            <div class="customer-search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="customer-search" placeholder="Search by name or email...">
            </div>

            <div class="table-wrap">
                <table id="customer-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr data-search="<?= htmlspecialchars(strtolower($customer['name'] . ' ' . $customer['email']), ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <div class="customer-name-cell">
                                    <div class="customer-avatar">
                                        <?php if (!empty($customer['profile_picture'])): ?>
                                            <img src="uploads/avatars/<?= htmlspecialchars($customer['profile_picture'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                        <?php else: ?>
                                            <?= htmlspecialchars(customerInitials($customer['name']), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                    <span><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $customer['order_count'] ?></td>
                            <td>₱<?= number_format((float) $customer['total_spent'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td>
                            <td><span class="customer-status <?= (int) $customer['is_blocked'] === 1 ? 'blocked' : 'active' ?>">
                                <?= (int) $customer['is_blocked'] === 1 ? 'Blocked' : 'Active' ?>
                            </span></td>
                            <td>
                                <form method="post" class="block-form">
                                    <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                                    <input type="hidden" name="blocked" value="<?= (int) $customer['is_blocked'] === 1 ? '0' : '1' ?>">
                                    <button type="submit" name="toggle_block" class="block-button <?= (int) $customer['is_blocked'] === 1 ? 'unblock' : '' ?>">
                                        <?= (int) $customer['is_blocked'] === 1 ? 'Unblock' : 'Block' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="7">No customers yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p id="no-customer-results">No customers match your search.</p>
            </div>
        </section>
    </main>

    <script>
        const customerSearch = document.getElementById('customer-search');
        const customerRows = document.querySelectorAll('#customer-table tbody tr[data-search]');
        const noResults = document.getElementById('no-customer-results');

        if (customerSearch) {
            customerSearch.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                let visibleCount = 0;
                customerRows.forEach(function (row) {
                    const matches = row.dataset.search.includes(term);
                    row.style.display = matches ? '' : 'none';
                    if (matches) visibleCount++;
                });
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>