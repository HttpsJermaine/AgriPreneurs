<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$msg = "";

/* UPDATE PROFILE */
if (isset($_POST['save_profile'])) {
    $new_phone = trim($_POST['phone'] ?? '');

    if ($new_phone == "") {
        $msg = "Phone number is required.";
    } else {
        // handle photo upload
        $new_photo_name = "";

        if (!empty($_FILES['photo']['name'])) {
            $folder = __DIR__ . "/../uploads/";
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES['photo']['name']);
            $target = $folder . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $new_photo_name = $file_name;
            } else {
                $msg = "Photo upload failed.";
            }
        }

        // Update buyer_details - ONLY phone and photo (name stays the same)
        if ($msg == "") {
            if ($new_photo_name != "") {
                $stmt = $conn->prepare("UPDATE buyer_details SET phone=?, photo=? WHERE user_id=?");
                $stmt->bind_param("ssi", $new_phone, $new_photo_name, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE buyer_details SET phone=? WHERE user_id=?");
                $stmt->bind_param("si", $new_phone, $user_id);
            }
            $stmt->execute();
            $stmt->close();

            $msg = "Profile updated!";
        }
    }
}

/* SAVE ADDRESS */
if (isset($_POST['save_address'])) {
    $address_id = (int)($_POST['address_id'] ?? 0);

    $label    = trim($_POST['label'] ?? '');
    $street   = trim($_POST['street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $zip      = trim($_POST['zip'] ?? '');

    if ($street === "" || $barangay === "" || $city === "" || $province === "" || $zip === "") {
        $msg = "Please fill up all address fields.";
    } else {
        // build full address
        $full_address = trim($street . ", " . $barangay . ", " . $city . ", " . $province . " " . $zip);

        if ($address_id > 0) {
            // UPDATE buyer_addresses
            $stmt = $conn->prepare("
                UPDATE buyer_addresses
                SET label=?, street=?, barangay=?, city=?, province=?, zip=?, full_address=?
                WHERE id=? AND user_id=?
            ");
            $stmt->bind_param("sssssssii", $label, $street, $barangay, $city, $province, $zip, $full_address, $address_id, $user_id);
            $stmt->execute();
            $stmt->close();

            $msg = "Address updated!";
        } else {
            // INSERT buyer_addresses
            $stmt = $conn->prepare("
                INSERT INTO buyer_addresses (user_id, label, street, barangay, city, province, zip, full_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssss", $user_id, $label, $street, $barangay, $city, $province, $zip, $full_address);
            $stmt->execute();
            $stmt->close();

            $msg = "Address added!";
        }

        // SYNC into buyer_details so admin modal can show it
        // (requires UNIQUE KEY on buyer_details.user_id)
        $stmt = $conn->prepare("
            INSERT INTO buyer_details (user_id, address_label, street, barangay, city, province, zip, full_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                address_label=VALUES(address_label),
                street=VALUES(street),
                barangay=VALUES(barangay),
                city=VALUES(city),
                province=VALUES(province),
                zip=VALUES(zip),
                full_address=VALUES(full_address)
        ");
        $stmt->bind_param("isssssss", $user_id, $label, $street, $barangay, $city, $province, $zip, $full_address);
        $stmt->execute();
        $stmt->close();
    }
}

/* -------------------------
   DELETE ADDRESS
--------------------------*/
if (isset($_POST['delete_address'])) {
    $address_id = (int)($_POST['address_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM buyer_addresses WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $msg = "Address deleted!";
}

/* -------------------------
   FETCH PROFILE
--------------------------*/
$stmt = $conn->prepare("SELECT full_name, phone, photo FROM buyer_details WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$full_name = $profile['full_name'] ?? 'Unnamed User';
$phone = $profile['phone'] ?? '';
$photo = $profile['photo'] ?? '';

$photoPath = "images/profile.png"; 

if (!empty($photo)) {
    $fsPath  = __DIR__ . "/../uploads/" . basename($photo); 
    $webPath = "../uploads/" . basename($photo);            

    if (file_exists($fsPath)) {
        $photoPath = $webPath;
    }
}

/* -------------------------
   FETCH ADDRESSES
--------------------------*/
$stmt = $conn->prepare("SELECT * FROM buyer_addresses WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/mobileview.css">
    <style>
        .address-actions { margin-top: 6px; display:flex; gap:10px; flex-wrap:wrap; }
        .addr-btn { cursor:pointer; border:none; background:none; color:#1a73e8; padding:0; text-decoration:underline; }
        .addr-btn.danger { color:#d93025; }
        .address-form, .profile-form { margin-top: 12px; display:none; }
        .address-form.open, .profile-form.open { display:block; }
        .address-form .row, .profile-form .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
        .address-form input, .profile-form input { padding:10px; width: 240px; max-width: 100%; }
        .primary-btn { padding:10px 14px; border:0; cursor:pointer; background:#28a745; color:white; border-radius:6px; }
        .msg { margin: 10px 0; padding: 10px; background:#f1f1f1; border-radius:6px; }
        .address-card { padding:10px; border:1px solid #e5e5e5; border-radius:8px; margin-top:10px; }
    </style>
</head>
<body>

<header>
    <?php require 'header.php'; ?>
</header>

<div class="profile-container">

    <aside class="profile-sidebar">
        <h3 class="sidebar-title">Account Overview</h3>

        <ul class="sidebar-menu">
            <li><a href="orders_list.php">My Orders</a></li>
            <li><a href="profile.php" class="active">Profile</a></li>
        </ul>

        <button class="logout-btn" onclick="openLogout()">
        <span>⏻</span> Log Out
        </button>
    </aside>

    <section class="profile-content">
        <div class="profile-page">

            <div class="left-card">
            <img src="<?= htmlspecialchars($photoPath) ?>" class="avatar" alt="Profile Photo">

            <h2 class="name"><?= htmlspecialchars($full_name) ?></h2>
            <span class="role-badge">Buyer</span>

            <div class="left-info">
                <!--<p><span class="icon">✉</span> <span class="muted">Email not available</span></p>-->
                <p><span class="icon">📞</span> <?= htmlspecialchars($phone ?: 'No contact number') ?></p>
            </div>

            <!-- Edit profile button -->
            <button type="button" class="btn-blue" id="btnEditProfile">Edit Profile</button>

            <!-- Expandable EDIT PROFILE form -->
            <div class="edit-profile-form" id="editProfileForm">
                <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <label>Full Name <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required 
                        style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;" disabled>
                </div>

                <div class="form-row">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                </div>

                <div class="form-row">
                    <label>Photo</label>
                    <input type="file" name="photo" accept="image/*">
                </div>

                <p style="font-size: 12px; color: #666; margin-bottom: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px;">
                    <i class="fas fa-info-circle"></i> Only phone number and photo can be updated. 
                    
                </p>

                <button type="submit" name="save_profile" class="btn-blue">Save Profile</button>
                <button type="button" class="btn-gray" id="btnCancelProfile">Cancel</button>
                </form>
            </div>
            </div>

            <!-- RIGHT CARD -->
            <div class="right-card">
            <h3 class="section-title">Personal Information</h3>

            <div class="info-grid">
                <div>
                <small>Full Name</small>
                <p><?= htmlspecialchars($full_name) ?></p>
                </div>
                <div>
                <small>Contact Number</small>
                <p><?= htmlspecialchars($phone ?: 'N/A') ?></p>
                </div>
            </div>

            <hr class="soft-line">

            <!-- ADDRESS SECTION -->
            <div class="address-top">
                <h3 class="section-title">My Addresses</h3>

                <!-- + Add Address button -->
                <button type="button" class="btn-blue small" id="btnAddAddress">+ Add Address</button>
            </div>

            <?php if ($addresses->num_rows == 0): ?>
                <p class="muted">No saved addresses yet.</p>
            <?php else: ?>
                <?php while($a = $addresses->fetch_assoc()): ?>
                <div class="address-card">
                    <div class="address-label"><?= htmlspecialchars($a['label'] ?: 'Address') ?></div>
                    <div class="address-text">
                    <?= htmlspecialchars($a['full_address'] ?: ($a['street'] . ", " . $a['barangay'] . ", " . $a['city'] . ", " . $a['province'] . " " . $a['zip'])) ?>
                    </div>

                    <!-- icon actions below -->
                    <div class="address-actions-bar">
                    <button type="button" class="icon-btn edit-address-btn"
                    data-id="<?= (int)$a['id'] ?>"
                    data-label="<?= htmlspecialchars($a['label'] ?? '', ENT_QUOTES) ?>"
                    data-street="<?= htmlspecialchars($a['street'] ?? '', ENT_QUOTES) ?>"
                    data-barangay="<?= htmlspecialchars($a['barangay'] ?? '', ENT_QUOTES) ?>"
                    data-city="<?= htmlspecialchars($a['city'] ?? '', ENT_QUOTES) ?>"
                    data-province="<?= htmlspecialchars($a['province'] ?? '', ENT_QUOTES) ?>"
                    data-zip="<?= htmlspecialchars($a['zip'] ?? '', ENT_QUOTES) ?>"
                    >
                    ✏ Edit
                    </button>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this address?');">
                        <input type="hidden" name="address_id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" name="delete_address" class="icon-btn danger">🗑 Delete</button>
                    </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- Expandable address form (your existing one) -->
            <div class="address-form" id="addressForm">
                <form method="POST">
                <input type="hidden" name="address_id" id="address_id">

                <div class="row">
                <input type="text" name="label" id="label" placeholder="Label (Home/Work)">
                <input type="text" name="street" id="street" placeholder="Street / House No." required>
                </div>

                <div class="row">
                <input type="text" name="barangay" id="barangay" placeholder="Barangay" required>
                <input type="text" name="city" id="city" placeholder="City" required>
                </div>

                <div class="row">
                <input type="text" name="province" id="province" placeholder="Province" required>
                <input type="text" name="zip" id="zip" placeholder="ZIP" required>
                </div>

                <button type="submit" name="save_address" class="btn-blue small" id="saveBtn">Save</button>
                <button type="button" class="btn-gray small" id="btnCancel">Cancel</button>
                </form>
            </div>

            </div>
            </div>
    </section>
</div>

<?php require 'footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {

  /* ========== EDIT PROFILE TOGGLE ========== */
  const btnEditProfile = document.getElementById("btnEditProfile");
  const btnCancelProfile = document.getElementById("btnCancelProfile");
  const editProfileForm = document.getElementById("editProfileForm");

  if (btnEditProfile && editProfileForm) {
    btnEditProfile.addEventListener("click", () => {
      editProfileForm.classList.add("open");
    });
  }

  if (btnCancelProfile && editProfileForm) {
    btnCancelProfile.addEventListener("click", () => {
      editProfileForm.classList.remove("open");
    });
  }

    document.querySelectorAll(".edit-address-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("address_id").value = btn.dataset.id;
        document.getElementById("label").value = btn.dataset.label;
        document.getElementById("street").value = btn.dataset.street;
        document.getElementById("barangay").value = btn.dataset.barangay;
        document.getElementById("city").value = btn.dataset.city;
        document.getElementById("province").value = btn.dataset.province;
        document.getElementById("zip").value = btn.dataset.zip;
        document.getElementById("saveBtn").textContent = "Update";
        document.getElementById("addressForm").classList.add("open");
    });
    });

    //force input into all caps
    document.querySelectorAll("input[type='text']").forEach(el => {
    el.addEventListener("input", () => {
        el.value = el.value.toUpperCase();
    });
    });

  /* ========== ADDRESS FORM TOGGLE ========== */
  const form = document.getElementById("addressForm");
  const btnAdd = document.getElementById("btnAddAddress");
  const btnCancel = document.getElementById("btnCancel");

  function openForm() { if (form) form.classList.add("open"); }
  function closeForm() { if (form) form.classList.remove("open"); }

  function clearForm() {
    const el = (id) => document.getElementById(id);
    if (el("address_id")) el("address_id").value = "";
    if (el("label")) el("label").value = "";
    if (el("street")) el("street").value = "";
    if (el("barangay")) el("barangay").value = "";
    if (el("city")) el("city").value = "";
    if (el("province")) el("province").value = "";
    if (el("zip")) el("zip").value = "";
    if (el("saveBtn")) el("saveBtn").textContent = "Save";
  }

  if (btnAdd) {
    btnAdd.addEventListener("click", () => {
      clearForm();
      openForm();
    });
  }

  if (btnCancel) {
    btnCancel.addEventListener("click", () => {
      closeForm();
    });
  }

  // Make editAddress available to onclick="" in HTML
  window.editAddress = function(id, label, street, barangay, city, province, zip) {
    document.getElementById("address_id").value = id;
    document.getElementById("label").value = label;
    document.getElementById("street").value = street;
    document.getElementById("barangay").value = barangay;
    document.getElementById("city").value = city;
    document.getElementById("province").value = province;
    document.getElementById("zip").value = zip;
    document.getElementById("saveBtn").textContent = "Update";
    
    openForm();
  };

  /* ========== LOGOUT MODAL ========== */
  window.openLogout = function() {
    const m = document.getElementById("logoutModal");
    if (m) m.style.display = "flex";
  };

  window.closeLogout = function() {
    const m = document.getElementById("logoutModal");
    if (m) m.style.display = "none";
  };

});
</script>

<!-- Logout Modal -->
<div class="logout-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-icon">⏻</div>

        <h3>Logout</h3>
        <p>Are you sure you want to logout?</p>

        <div class="logout-actions">
            <button class="btn-cancel" onclick="closeLogout()">Cancel</button>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</div>
</body>
</html>