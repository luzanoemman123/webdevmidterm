<?php
require_once __DIR__ . '/partials/admin_guard.php';
require_once __DIR__ . '/config.php';

$imagesPath = file_exists(__DIR__ . '/images') ? __DIR__ . '/images' : dirname(__DIR__) . '/images';

function redirectWithMessage(string $message, string $type = 'success'): never
{
    header('Location: admin_stock.php?message=' . urlencode($message) . '&type=' . urlencode($type));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_product') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $image = basename(trim((string) ($_POST['image'] ?? '')));
        $id = (int) ($_POST['id'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || $description === '' || $price === false || $price < 0 || $stock === false || $stock < 0 || $image === '') {
            redirectWithMessage('Complete every product field with valid values.', 'error');
        }
        if (!file_exists($imagesPath . DIRECTORY_SEPARATOR . $image)) {
            redirectWithMessage('Choose an image that exists in the images folder.', 'error');
        }

        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, active = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $image, $active, $id);
            $stmt->execute();
            $stmt->close();
            redirectWithMessage('Product updated.');
        } else {
            $stmt = $conn->prepare("
                INSERT INTO products (name, description, price, stock, image, active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $image, $active);
            $stmt->execute();
            $stmt->close();
            redirectWithMessage('Product added.');
        }
    }

    if ($action === 'delete_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        redirectWithMessage('Product removed.');
    }
}

$editingProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editingProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$products = $conn->query("SELECT * FROM products ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? 'success';
$imageFiles = array_values(array_filter(
    scandir($imagesPath) ?: [],
    static fn($file) => $file !== '.' && $file !== '..' && is_file($imagesPath . DIRECTORY_SEPARATOR . $file)
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock | Luzano Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include 'partials/admin_nav.php'; ?>

    <main class="admin-shell">
        <?php if ($message): ?><p class="alert <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

        <section class="workspace">
            <div class="panel product-panel">
                <div class="panel-heading"><div><p class="eyebrow">INVENTORY</p><h2>Products</h2></div><a class="secondary-button" href="admin_stock.php">Clear form</a></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><div class="product-name"><img src="images/<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt=""><span><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></small></span></div></td>
                                <td>₱<?= number_format((float) $product['price']) ?></td>
                                <td><?= (int) $product['stock'] ?><?= (int) $product['stock'] <= 5 ? ' ⚠' : '' ?></td>
                                <td><span class="status <?= $product['active'] ? 'live' : 'hidden' ?>"><?= $product['active'] ? 'Live' : 'Hidden' ?></span></td>
                                <td class="actions">
                                    <a href="admin_stock.php?edit=<?= (int) $product['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Remove this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="panel form-panel">
                <p class="eyebrow"><?= $editingProduct ? 'EDIT ITEM' : 'NEW ITEM' ?></p>
                <h2><?= $editingProduct ? 'Update product' : 'Add product' ?></h2>
                <form method="post" class="admin-form">
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="id" value="<?= (int) ($editingProduct['id'] ?? 0) ?>">
                    <label for="name">Product name</label>
                    <input id="name" name="name" value="<?= htmlspecialchars($editingProduct['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <label for="description">Short description</label>
                    <input id="description" name="description" value="<?= htmlspecialchars($editingProduct['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <label for="price">Price (PHP)</label>
                    <input id="price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($editingProduct['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    <label for="stock">Stock quantity</label>
                    <input id="stock" name="stock" type="number" min="0" step="1" value="<?= htmlspecialchars((string) ($editingProduct['stock'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" required>
                    <label for="image">Product image</label>
                    <select id="image" name="image" required>
                        <option value="">Select an image</option>
                        <?php foreach ($imageFiles as $image): ?>
                            <option value="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" <?= (($editingProduct['image'] ?? '') === $image) ? 'selected' : '' ?>><?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="checkbox-label"><input type="checkbox" name="active" <?= (!$editingProduct || !empty($editingProduct['active'])) ? 'checked' : '' ?>> Show on storefront</label>
                    <button class="primary-button" type="submit"><?= $editingProduct ? 'Save changes' : 'Add product' ?></button>
                </form>
            </aside>
        </section>
    </main>
</body>
</html>
