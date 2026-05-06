<?php
// registration.php - Admin: User Verification with View Modal
session_start();
require_once "../db_connection.php";

// Require admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=" . urlencode("Access denied."));
    exit;
}

// Fetch pending users (only basic info for table)
$pending = [];
$stmt = $conn->prepare("SELECT id, username, role, status FROM users WHERE status = 'pending' ORDER BY id DESC");
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $pending[] = $row;
$stmt->close();

// Fetch active users
$active = [];
$stmt = $conn->prepare("SELECT id, username, role, status FROM users WHERE status = 'active' ORDER BY id DESC");
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $active[] = $row;
$stmt->close();

// Fetch archived users (optional, kept for consistency)
$archived = [];
$stmt = $conn->prepare("SELECT id, username, role, status FROM users WHERE status = 'archived' ORDER BY id DESC");
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $archived[] = $row;
$stmt->close();

// Helper function to get farmer details (used in modal data via AJAX)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>User Verification | PLAMAL</title>
    <link rel="stylesheet" href="css/registration.css">
    <link rel="stylesheet" href="css/mobileview.css">
</head>
<body>

<div class="sidebar">
    <h2 class="logo">🌿 PLAMAL</h2>
    <div class="farmer-info">
        <img src="images/icon.png" alt="Farmer" class="avatar">
        <p class="farmer-name">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    <nav class="menu">
        <a href="admin_dashboard.php" class="menu-item">🏚️ Dashboard</a>
        <a href="user.php" class="menu-item">👥 Manage Users</a>
        <a href="registration.php" class="menu-item active">📃 User Registration</a>
        <a href="farmers_list.php" class="menu-item">👩‍🌾 Farmers</a>
        <a href="logout.php" class="menu-item logout">🚪 Logout</a>
    </nav>
</div>

<div class="admin-container">
    <h1 class="page-title">User Verification</h1>
    <p class="subtitle">Manage approval requests from farmers and buyers</p>

    <div class="admin-panel">

        <h3>Pending Users</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody id="pending-body">
                <?php if (empty($pending)): ?>
                    <tr><td colspan="5">No pending users.</td></tr>
                <?php else: ?>
                    <?php foreach ($pending as $u): ?>
                        <tr id="user-p-<?php echo $u['id']; ?>">
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><span class="badge pending">Pending</span></td>
                            <td>
                                <button class="view-btn" onclick="openUserModal(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 style="margin-top:32px;">Active Users</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody id="active-body">
                <?php if (empty($active)): ?>
                    <tr><td colspan="5">No active users.</td></tr>
                <?php else: ?>
                    <?php foreach ($active as $u): ?>
                        <tr id="user-a-<?php echo $u['id']; ?>">
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><span class="badge active">Active</span></td>
                            <td>
                                <button class="view-btn" onclick="openUserModal(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">View</button>
                                <!-- Keep disable button if needed, but view is now primary -->
                                <button class="disable-btn" onclick="disableUser(<?php echo $u['id']; ?>)">Disable</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL for user details -->
<div id="userDetailModal" class="modal">
  <div class="modal-content">
    <button class="close-btn" id="modalCloseBtn">&times;</button>
    <span class="modal-role" id="modalRoleBadge">User</span>

    <div class="modal-avatar">
      <img id="modalPhoto" src="images/default-avatar.png" alt="User photo">
      <h2 id="modalName">Loading...</h2>
      <div id="modalSubline" style="margin-top:6px; color:#6b7280; font-size:14px;"></div>
    </div>

    <!-- FARMER BLOCK -->
    <div id="farmerBlock" style="display:none;">
      <div class="details-grid">
        <span class="detail-label">Registry Number:</span><span class="detail-value" id="f_registry">—</span>
        <span class="detail-label">Phone:</span><span class="detail-value" id="f_phone">—</span>
        <span class="detail-label">Farm Area:</span><span class="detail-value" id="f_farm_area">—</span>
        <span class="detail-label">Province:</span><span class="detail-value" id="f_province">—</span>
        <span class="detail-label">City:</span><span class="detail-value" id="f_city">—</span>
        <span class="detail-label">Barangay:</span><span class="detail-value" id="f_barangay">—</span>
        <span class="detail-label">ZIP:</span><span class="detail-value" id="f_zip">—</span>
        <span class="detail-label">Full Address:</span><span class="detail-value" id="f_full_address">—</span>
      </div>
    </div>

    <!-- BUYER BLOCK -->
    <div id="buyerBlock" style="display:none;">
      <div class="details-grid">
        <span class="detail-label">Phone:</span><span class="detail-value" id="b_phone">—</span>
        <span class="detail-label">Province:</span><span class="detail-value" id="b_province">—</span>
        <span class="detail-label">City:</span><span class="detail-value" id="b_city">—</span>
        <span class="detail-label">Barangay:</span><span class="detail-value" id="b_barangay">—</span>
        <span class="detail-label">ZIP:</span><span class="detail-value" id="b_zip">—</span>
        <span class="detail-label">Full Address:</span><span class="detail-value" id="b_full_address">—</span>
      </div>
    </div>

    <!-- ADMIN BLOCK -->
    <div id="adminBlock" style="display:none;">
      <div class="details-grid">
        <span class="detail-label">Position:</span><span class="detail-value" id="a_position">—</span>
        <span class="detail-label">Phone:</span><span class="detail-value" id="a_phone">—</span>
        <span class="detail-label">Association:</span><span class="detail-value" id="a_association">Plaridel-Malolos Irrigators Association Inc.</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="modal-actions" id="modalActionButtons"></div>
  </div>
</div>

<script>
// POST helper
async function postJson(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    });
    return res.json();
}

// Approve/Reject/Disable functions (unchanged)
function approveUser(userId) {
    if (!confirm('Approve this user?')) return;
    postJson('admin_approve_user.php', { user_id: userId })
    .then(r => {
        if (r.success) location.reload();
        else alert('Approve failed: ' + (r.error || 'unknown'));
    }).catch(() => alert('Network error'));
}

function rejectUser(userId) {
    if (!confirm('Reject this user? (This will go to disabled users)')) return;
    postJson('admin_disable_user.php', { user_id: userId })
    .then(r => {
        if (r.success) location.reload();
        else alert('Reject failed: ' + (r.error || 'unknown'));
    }).catch(() => alert('Network error'));
}

function disableUser(userId) {
    if (!confirm('Disable this user?')) return;
    postJson('admin_disable_user.php', { user_id: userId })
    .then(r => {
        if (r.success) location.reload();
        else alert('Disable failed: ' + (r.error || 'unknown'));
    }).catch(() => alert('Network error'));
}

// ========== MODAL LOGIC ==========
const modal = document.getElementById('userDetailModal');
const closeBtn = document.getElementById('modalCloseBtn');

// Close modal when X is clicked
closeBtn.onclick = function() {
    modal.style.display = 'none';
}

// Close modal when clicking outside content
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Open modal and fetch user details
async function openUserModal(userId, roleFromTable) {
  modal.style.display = 'flex';

  // reset
  document.getElementById('modalName').innerText = 'Loading...';
  document.getElementById('modalSubline').innerText = '';
  document.getElementById('modalPhoto').src = 'images/loading.png';

  // hide all role blocks
  document.getElementById('farmerBlock').style.display = 'none';
  document.getElementById('buyerBlock').style.display = 'none';
  document.getElementById('adminBlock').style.display = 'none';

  const actionDiv = document.getElementById('modalActionButtons');
  actionDiv.innerHTML = '';

  try {
    const response = await fetch(`get_user_details.php?user_id=${userId}`);
    const data = await response.json();

    if (!data.success) {
      alert(data.error || 'Failed to load details');
      modal.style.display = 'none';
      return;
    }

    const role = data.role || roleFromTable || 'user';
    document.getElementById('modalRoleBadge').innerText =
      role.charAt(0).toUpperCase() + role.slice(1);

    document.getElementById('modalName').innerText =
      data.display_name || data.username || 'Unknown';

    // photo
    if (data.photo_url && data.photo_url.trim() !== '') {
      document.getElementById('modalPhoto').src = data.photo_url;
    } else {
      if (role === 'farmer') document.getElementById('modalPhoto').src = 'images/farmer-avatar.png';
      else if (role === 'buyer') document.getElementById('modalPhoto').src = 'images/buyer-avatar.png';
      else document.getElementById('modalPhoto').src = 'images/admin-avatar.png';
    }

    // Subline
    if (role === 'admin' && data.position) {
      document.getElementById('modalSubline').innerText = data.position;
    } else {
      document.getElementById('modalSubline').innerText = `Status: ${data.status || '—'}`;
    }

    // Fill blocks
    if (role === 'farmer') {
      document.getElementById('farmerBlock').style.display = 'block';
      document.getElementById('f_registry').innerText = data.registry_num || '—';
      document.getElementById('f_phone').innerText = data.phone || '—';
      document.getElementById('f_farm_area').innerText = data.farm_area || '—';
      document.getElementById('f_province').innerText = data.province || '—';
      document.getElementById('f_city').innerText = data.city || '—';
      document.getElementById('f_barangay').innerText = data.barangay || '—';
      document.getElementById('f_zip').innerText = data.zip || '—';
      document.getElementById('f_full_address').innerText = data.full_address || '—';
    }

    if (role === 'buyer') {
      document.getElementById('buyerBlock').style.display = 'block';
      document.getElementById('b_phone').innerText = data.phone || '—';
      document.getElementById('b_province').innerText = data.province || '—';
      document.getElementById('b_city').innerText = data.city || '—';
      document.getElementById('b_barangay').innerText = data.barangay || '—';
      document.getElementById('b_zip').innerText = data.zip || '—';
      document.getElementById('b_full_address').innerText = data.full_address || '—';
    }

    if (role === 'admin') {
      document.getElementById('adminBlock').style.display = 'block';
      document.getElementById('a_position').innerText = data.position || '—';
      document.getElementById('a_phone').innerText = data.phone || '—';
      
    }

    // Actions based on status
    const status = data.status || 'pending';
    if (status === 'pending') {
      actionDiv.innerHTML = `
        <button class="approve-btn" onclick="approveUser(${userId}); modal.style.display='none'">Approve</button>
        <button class="reject-btn" onclick="rejectUser(${userId}); modal.style.display='none'">Decline</button>
      `;
    } else {
      actionDiv.innerHTML = `<p style="text-align:center; color:#666; width:100%;">User is ${status}</p>`;
    }

  } catch (e) {
    console.error(e);
    alert('Error fetching user details.');
    modal.style.display = 'none';
  }
}

</script>
</body>
</html>