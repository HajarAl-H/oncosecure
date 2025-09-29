<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
check_session_timeout();
require_once __DIR__ . '/../includes/header.php';

// Pagination setup
$limit = 10; // logs per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total logs
$countStmt = $pdo->query("SELECT COUNT(*) FROM logs");
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Fetch logs with user names (limited)
$stmt = $pdo->prepare("
    SELECT logs.id, users.name, logs.action, logs.ip, logs.created_at
    FROM logs
    LEFT JOIN users ON logs.user_id = users.id
    ORDER BY logs.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<h4>Logs</h4>
<table class="table">
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Action</th>
      <th>IP</th>
      <th>Created</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    $rowNumber = $offset + 1;
    foreach ($logs as $log): ?>
      <tr>
        <td><?= $rowNumber++ ?></td>
        <td><?= htmlspecialchars($log['name'] ?? 'Unknown') ?></td>
        <td><?= htmlspecialchars($log['action']) ?></td>
        <td><?= htmlspecialchars($log['ip']) ?></td>
        <td><?= htmlspecialchars($log['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Pagination -->
<nav>
  <ul class="pagination justify-content-center">
    <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
    <?php endif; ?>
  </ul>
</nav>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
