<?php
/**
 * VIOLATORS PAGE
 * Location: violators.php
 * 
 * PURPOSE: Display all registered violators with their statistics
 * FLOW: Database Connection → Get Violators → Display Table
 */

$page_title = "Violators Database";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get search term if exists
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Build query with search
$sql = "
    SELECT 
        v.*,
        COUNT(t.id) as total_tickets,
        SUM(CASE WHEN t.status = 'UNPAID' OR t.status = 'OVERDUE' THEN t.total_amount ELSE 0 END) as outstanding_balance
    FROM violators v
    LEFT JOIN tickets t ON v.id = t.violator_id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (v.license_number LIKE ? OR v.first_name LIKE ? OR v.last_name LIKE ? OR v.contact_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$sql .= " GROUP BY v.id ORDER BY v.last_name, v.first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$violators = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1>Violators Database</h1>
        <a href="add_violator.php" class="btn btn-primary">
            ➕ Add New Violator
        </a>
    </div>
    <!-- Search -->
    <div class="card">
        <form method="GET" action="violators.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Search Violators</label>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by license #, name, or contact..." 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="violators.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Violators Table -->
    <div class="card">
        <h2>Registered Violators (<?= count($violators) ?> found)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>License #</th>
                        <th>First Name</th>
                        <th>M.I.</th>
                        <th>Last Name</th>
                        <th>Suffix</th>
                        <th>Contact Number</th>
                        <th>Email</th>
                        <th>Total Tickets</th>
                        <th>Outstanding Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($violators)): ?>
                    <tr>
                        <td colspan="10" class="empty-state">
                            <h3>No violators found</h3>
                            <p>Try adjusting your search</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($violators as $violator): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($violator['license_number']) ?></strong></td>
                            <td><?= htmlspecialchars($violator['first_name']) ?></td>
                            <td><?= htmlspecialchars($violator['middle_initial'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($violator['last_name']) ?></td>
                            <td><?= htmlspecialchars($violator['suffix'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($violator['contact_number']) ?></td>
                            <td><?= htmlspecialchars($violator['email'] ?: '-') ?></td>
                            <td>
                                <span class="badge" style="background: #3498db; color: white;">
                                    <?= $violator['total_tickets'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($violator['outstanding_balance'] > 0): ?>
                                    <strong style="color: #e74c3c;">
                                        ₱<?= number_format($violator['outstanding_balance'], 2) ?>
                                    </strong>
                                <?php else: ?>
                                    <span style="color: #27ae60;">₱0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- FIXED: Using proper dropdown like in tickets.php -->
                                <div class="action-dropdown">
                                    <button class="action-dropdown-toggle" type="button">
                                        Actions ▼
                                    </button>
                                    <div class="action-dropdown-menu">
                                        <!-- View Tickets -->
                                        <a href="tickets.php?search=<?= urlencode($violator['license_number']) ?>" 
                                           class="action-dropdown-item view">
                                            <span>📋</span>
                                            <span>View Tickets</span>
                                        </a>
                                        
                                        <!-- Delete -->
                                        <div class="action-dropdown-divider"></div>
                                        <form method="POST" 
                                              action="process/process_delete.php" 
                                              style="margin: 0;" 
                                              onsubmit="return confirm('Delete this violator? All associated tickets will be removed!')">
                                            <input type="hidden" name="delete_violator" value="<?= $violator['id'] ?>">
                                            <button type="submit" class="action-dropdown-item delete">
                                                <span>🗑️</span>
                                                <span>Delete Violator</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>