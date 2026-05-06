<?php
session_start();
require_once "../db_connection.php"; 

// Only admin may access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

$adminName = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Page</title>
<link rel="stylesheet" href="css/user.css">
<link rel="stylesheet" href="css/mobileview.css">
</head>

<body>

<div class="sidebar">
    <h2 class="logo">🌿 PLAMAL</h2>

    <div class="farmer-info">
        <img src="images/icon.png" alt="Admin" class="avatar">
        <p class="farmer-name">Hello, <?php echo $adminName; ?></p>
    </div>

    <nav class="menu">
        <a href="admin_dashboard.php" class="menu-item">🏚️ Dashboard</a>
        <a href="user.php" class="menu-item active">👥 Manage Users</a>
        <a href="registration.php" class="menu-item">📃 User Registration</a>
        <a href="farmers_list.php" class="menu-item">👩‍🌾 Farmers</a>
        <a href="logout.php" class="menu-item logout">🚪 Logout</a>
    </nav>
</div>

<div class="user-management-container">
    <!-- Add New Admin -->
    <div class="add-user-card">
    <h2>Add New Admin</h2>

    <form id="adminForm" enctype="multipart/form-data" onsubmit="event.preventDefault(); createAdmin();">
        <div class="add-user-grid">

        <input type="text" name="fullname" id="new_fullname" placeholder="Fullname" required>

        <select name="position" id="new_position" required>
            <option value="" disabled selected>Select position</option>
            <option>President</option>
            <option>Vice-President</option>
            <option>Treasurer</option>
            <option>Secretary</option>
            <option>Board Member</option>
        </select>

        <input type="text" name="phone" id="new_phone" placeholder="Phone Number">

        <input type="text" name="username" id="new_username" placeholder="Username" required>

        <div class="pw-wrap">
            <input type="password" name="password" id="new_password" placeholder="Password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('new_password', this)">👁</button>
        </div>

        <div class="pw-wrap">
            <input type="password" name="confirm" id="new_confirm" placeholder="Confirm Password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('new_confirm', this)">👁</button>
        </div>

        <div class="file-field">
        <label class="file-label" for="new_photo">
            Admin Photo <span class="file-hint"></span>
        </label>

        <div class="file-uploader">
            <img id="photoPreview" class="photo-preview" src="images/admin-avatar.png" alt="Admin photo preview">

            <div class="file-meta">
            <div class="file-actions">
                <input type="file" name="photo" id="new_photo" accept="image/*" hidden>
                <button type="button" class="file-btn" onclick="document.getElementById('new_photo').click()">Upload Photo</button>
                <button type="button" class="file-btn secondary" onclick="clearPhoto()">Remove</button>
            </div>

            <div class="file-name" id="photoFileName">No file selected</div>
            <div class="file-help">PNG/JPG up to 5MB.</div>
            </div>
        </div>
        </div>

        <button class="add-user-btn">+ Add User</button>

        </div>
    </form>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <input type="text" id="searchBox" placeholder="Search by username, ID or role">
        <!--<button class="search-btn" onclick="doSearch(document.getElementById('searchBox').value)">🔍</button>-->
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" id="tab-active" onclick="showTab('active-users','tab-active')">Active Users</button>
        <button class="tab" id="tab-archived" onclick="showTab('archived-users','tab-archived')">Archived Users</button>
    </div>

    <!-- ACTIVE USERS -->
    <div id="active-users" class="table-section">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="action-col">Action</th>
                </tr>
            </thead>
            <tbody id="active-body">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- DISABLED USERS -->
    <div id="archived-users" class="table-section hidden">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="action-col">Action</th>
                </tr>
            </thead>
            <tbody id="disabled-body">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>

</div>

<script>

/* Show Password */
function togglePw(inputId, btn) {
  const el = document.getElementById(inputId);
  if (!el) return;
  el.type = (el.type === "password") ? "text" : "password";
  btn.textContent = (el.type === "password") ? "👁" : "🙈";
}

function showTab(tabId, tabButtonId) {
    document.getElementById("active-users").classList.add("hidden");
    document.getElementById("archived-users").classList.add("hidden");
    document.getElementById(tabId).classList.remove("hidden");

    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById(tabButtonId).classList.add("active");
}

/* CREATE NEW ADMIN ACCOUNT */
async function createAdmin() {
const form = document.getElementById("adminForm");
const fd = new FormData(form);
const password = fd.get("password");
const confirm  = fd.get("confirm");

if (password !== confirm) {
  alert("Passwords do not match");
  return;
}

if (password.length < 6) {
  alert("Password must be at least 6 characters");
  return;
}

const res = await fetch("admin_create_user.php", {
  method: "POST",
  body: fd
});

let data;
try { data = await res.json(); }
catch {
  alert("Server error. Check admin_create_user.php");
  return;
}

if (data.success) {
  alert("Admin created!");
  form.reset();
  doSearch("");
} else {
  alert(data.error || "Failed.");
}
}

/* SEARCH USERS */
let searchTimer = null;

document.getElementById("searchBox").addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        doSearch(document.getElementById("searchBox").value);
    }, 300);
});

async function doSearch(query = "") {
    const res = await fetch("admin_search_users.php?q=" + encodeURIComponent(query));
    const data = await res.json();

    if (!data.success) {
        alert(data.error || "Search failed");
        return;
    }

    populateTables(data.data);
}

/* -------------------------------
   POPULATE TABLES WITH AJAX DATA
--------------------------------*/
function populateTables(groups) {
    const activeBody = document.getElementById("active-body");
    const disabledBody = document.getElementById("disabled-body");

    // ACTIVE USERS
    activeBody.innerHTML = groups.active.length
        ? groups.active.map(u =>
            `<tr>
                <td>${u.id}</td>
                <td>${escapeHtml(u.username)}</td>
                <td>${escapeHtml(u.role)}</td>
                <td><button class='archive-btn' onclick='disableUser(${u.id})'>Disable</button></td>
            </tr>`
        ).join('')
        : `<tr><td colspan="4">No active users found.</td></tr>`;

    // DISABLED USERS
    disabledBody.innerHTML = groups.disabled.length
        ? groups.disabled.map(u =>
            `<tr>
                <td>${u.id}</td>
                <td>${escapeHtml(u.username)}</td>
                <td>${escapeHtml(u.role)}</td>
                <td><button class='restore-btn' onclick='restoreUser(${u.id})'>Restore</button></td>
            </tr>`
        ).join('')
        : `<tr><td colspan="4">No disabled users found.</td></tr>`;
}

// Escape HTML
function escapeHtml(text) {
    return text.replace(/[&<>"']/g, m => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;',
        '"':'&quot;', "'":'&#39;'
    }[m]));
}

/* -------------------------------
   DISABLE & RESTORE USER (AJAX)
--------------------------------*/
async function disableUser(id) {
    if (!confirm("Disable this user?")) return;

    const res = await fetch("admin_disable_user.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "user_id=" + encodeURIComponent(id)
    });

    const data = await res.json();
    if (data.success) {
        doSearch(document.getElementById('searchBox').value);
    } else {
        alert("Disable failed: " + data.error);
    }
}

async function restoreUser(id) {
    if (!confirm("Restore this user?")) return;

    const res = await fetch("admin_restore_user.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "user_id=" + encodeURIComponent(id)
    });

    const data = await res.json();
    if (data.success) {
        doSearch(document.getElementById('searchBox').value);
    } else {
        alert("Restore failed: " + data.error);
    }
}

/* INITIAL LOAD */
doSearch("");

const photoInput = document.getElementById("new_photo");
const photoPreview = document.getElementById("photoPreview");
const photoFileName = document.getElementById("photoFileName");

if (photoInput) {
  photoInput.addEventListener("change", () => {
    const file = photoInput.files && photoInput.files[0];
    if (!file) {
      photoFileName.textContent = "No file selected";
      photoPreview.src = "images/admin-avatar.png";
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      alert("File too large. Max 5MB.");
      clearPhoto();
      return;
    }

    photoFileName.textContent = file.name;

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.src = e.target.result; };
    reader.readAsDataURL(file);
  });
}

function clearPhoto() {
  if (!photoInput) return;
  photoInput.value = "";
  photoFileName.textContent = "No file selected";
  photoPreview.src = "images/admin-avatar.png";
}

</script>

</body>
</html>
