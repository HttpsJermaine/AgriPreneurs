<?php
session_start();
require_once "../db_connection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date("Y");
$farmerId   = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

// Get farmer photo from farmer_details table
$stmt = $conn->prepare("SELECT photo FROM farmer_details WHERE user_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$farmerPhoto = !empty($row['photo']) ? "../uploads/" . $row['photo'] : "../images/default-avatar.png";

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans and Rotation</title>
<link rel="stylesheet" href="css/crops.css">
<link rel="stylesheet" href="css/mobileview.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar">
     <div class="mobile-topbar">
        <span class="mobile-logo">🌿 PLAMAL</span>
       <button class="mobile-toggle" onclick="toggleTopbar()">
      <i class="fas fa-bars"></i>
    </button>
  </div>
  
  <h2 class="logo">🌿 PLAMAL</h2>

  <div class="farmer-info">
    <img src="<?php echo $farmerPhoto; ?>" alt="Farmers" class="avatar">
    <p class="farmer-name">Hello, <?php echo $farmerName; ?>!</p>
  </div>

  <nav class="menu">
        <a href="farmer_dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="crops.php" class="menu-item active"> <i class="fas fa-calendar-check"></i> Plans</a>
        <a href="orders.php" class="menu-item"> <i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="stocks.php" class="menu-item"> <i class="fas fa-boxes"></i> Stocks</a>
        <a href="earnings.php" class="menu-item"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a href="profile.php" class="menu-item"><i class="fas fa-user-circle"></i>  Profile</a>
        <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="page">
  <div class="topbar">
    <h1>Plans and Rotation</h1>
    <div class="meta">
      <span class="pill">👤 <?php echo $farmerName; ?></span>
      <span class="pill">Year: <b id="yearLabel"><?php echo $year; ?></b></span>
    </div>
  </div>

  <!-- ===================== MY PLANS (SEASONS) ===================== -->
  <section class="section">
    <div class="section-head">
      <h2>My Plans</h2>
      <button class="icon-btn" id="openAddBtn" aria-label="Add plan">+</button>
    </div>

    <div class="section-body">
      <div class="season-grid">
        <div class="season-card">
          <div class="season-top">
            <div class="season-badge wet">Wet Season</div>
            
          </div>

          <div class="season-details">
            <div class="sd"><b>Sales on:</b> October – November</div>
          </div>

          <div class="season-actions">
            <button class="btn-primary" onclick="openSeason('wet')">Open Folder</button>
          </div>
        </div>

        <div class="season-card">
          <div class="season-top">
            <div class="season-badge dry">Dry Season</div>
            
          </div>

          <div class="season-details">
            
            <div class="sd"><b>Sales on:</b> March – April</div>
          </div>

          <div class="season-actions">
            <button class="btn-primary" onclick="openSeason('dry')">Open Folder</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== ROTATION SUGGESTIONS ===================== -->
  <section class="section">

  <div class="rot-tools" style="display:flex; gap:10px; align-items:center; margin-bottom:10px; margin-top:10px;">
  <div style="font-weight:700; margin-left:10px;">Sales Year:</div>
  <select id="salesYearSelect" style="padding:8px 10px; border-radius:10px; border:1px solid #dfe7df;">
    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
    <option value="<?php echo $year-1; ?>"><?php echo $year-1; ?></option>
    <option value="<?php echo $year-2; ?>"><?php echo $year-2; ?></option>
  </select>
  </div>

    <div class="section-head">
      <h2>Rotation Suggestions</h2>
    </div>

    <div class="section-body">
      <div class="rot-grid">

        <div class="rot-box" id="rotWetBox">
          <div class="rot-head">
            <div class="rot-title">Wet Season Suggestion</div>
            <div class="rot-range">Based on sales: Nov – Dec</div>
          </div>

          <div class="rotation-card" id="rotWetCard" style="display:none;">
            <img class="rotation-img" id="rotWetImg" src="../images/no-image.png" alt="Suggested variety">
            <div class="rotation-info">
              <div class="kv"><b>Rice Variety:</b> <span id="rotWetName"></span></div>
              <div class="kv"><b>Total Sold:</b> <span id="rotWetSold"></span></div>
              <div class="kv"><b>Why recommended:</b> <span id="rotWetWhy"></span></div>
              <button class="use-btn" type="button" onclick="openUseSuggestion('wet')">USE</button>
            </div>
          </div>

          <div class="rot-empty" id="rotWetEmpty">No wet season suggestion yet.</div>
        </div>

        <div class="rot-box" id="rotDryBox">
          <div class="rot-head">
            <div class="rot-title">Dry Season Suggestion</div>
            <div class="rot-range">Based on sales: Mar – Apr</div>
          </div>

          <div class="rotation-card" id="rotDryCard" style="display:none;">
            <img class="rotation-img" id="rotDryImg" src="../images/no-image.png" alt="Suggested variety">
            <div class="rotation-info">
              <div class="kv"><b>Rice Variety:</b> <span id="rotDryName"></span></div>
              <div class="kv"><b>Total Sold:</b> <span id="rotDrySold"></span></div>
              <div class="kv"><b>Why recommended:</b> <span id="rotDryWhy"></span></div>
              <button class="use-btn" type="button" onclick="openUseSuggestion('dry')">USE</button>
            </div>
          </div>

          <div class="rot-empty" id="rotDryEmpty">No dry season suggestion yet.</div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- ===================== SEASON FOLDER MODAL ===================== -->
<div class="backdrop" id="seasonBackdrop" style="z-index:9999;">
  <div class="modal">
    <div class="modal-head">
      <h3 id="seasonTitle">Season Plans</h3>
      <button class="btn-close" type="button" onclick="closeSeasonModal()">X</button>
    </div>

    <div class="modal-body">
      <div id="seasonPlansGrid" class="plans-grid"></div>
    </div>
  </div>
</div>

<!-- ===================== ADD PLAN MODAL ===================== -->
<div class="backdrop" id="addBackdrop">
  <div class="modal">
    <div class="modal-head">
      <h3>Add Crop Plan</h3>
      <button class="close-btn" type="button" id="closeAdd"></button>
    </div>

    <form class="modal-body" id="addPlanForm" enctype="multipart/form-data">
      <input type="hidden" name="plan_year" id="planYear" value="<?php echo $year; ?>">

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Season:</label><br>
        <label><input type="radio" name="season" value="wet" required> Wet Season</label>
        <label style="margin-left:12px;"><input type="radio" name="season" value="dry" required> Dry Season</label>
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Image (optional)</label><br>
        <input type="file" name="image" accept="image/*">
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Rice Variety</label><br>
        <input type="text" name="rice_variety" required
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;">
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Date of Planting</label><br>
        <input type="date" name="planting_date" id="plantingDate" required
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;">
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Notes</label><br>
        <textarea name="notes" rows="4"
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;"></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" id="cancelAdd">Cancel</button>
        <button type="submit" class="btn-save">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- ===================== USE SUGGESTION MODAL ===================== -->
<div class="backdrop" id="useBackdrop" style="z-index:10001;">
  <div class="modal">
    <div class="modal-head">
      <h3 id="useTitle">Use Rotation Suggestion</h3>
      <button class="close-btn" type="button" onclick="closeUseModal()"></button>
    </div>

    <form class="modal-body" id="useForm">
      <input type="hidden" name="product_id" id="use_product_id">
      <input type="hidden" name="plan_year" id="use_plan_year" value="<?php echo $year; ?>">
      <input type="hidden" name="season" id="use_season">

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Rice Variety</label><br>
        <input type="text" name="rice_variety" id="use_rice_variety" required
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;">
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Date of Planting</label><br>
        <input type="date" name="planting_date" id="use_planting_date" required
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;">
      </div>

      <div style="margin-bottom:10px;">
        <label style="font-weight:700;">Notes</label><br>
        <textarea name="notes" id="use_notes" rows="4"
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;"></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeUseModal()">Cancel</button>
        <button type="submit" class="btn-save">Insert</button>
      </div>
    </form>
  </div>
</div>

<script>

const salesYearSelect = document.getElementById('salesYearSelect');

function getPlanningYear(){
  return parseInt(document.getElementById('yearLabel').innerText.trim(), 10);
}

function getSalesYear(){
  // keep suggestions year retained even when planning year changes
  const saved = localStorage.getItem('rotation_sales_year');
  if (saved) return parseInt(saved, 10);

  // default: suggestions based on previous year sales when planning year changes
  // e.g. planning 2027 => sales 2026
  const planning = getPlanningYear();
  return planning - 1;
}

function setSalesYear(y){
  localStorage.setItem('rotation_sales_year', String(y));
  if (salesYearSelect) salesYearSelect.value = String(y);
}

if (salesYearSelect){
  setSalesYear(getSalesYear());

  salesYearSelect.addEventListener('change', () => {
    setSalesYear(parseInt(salesYearSelect.value, 10));
    loadRotationSuggestions(); 
  });
}

(function ensureSalesYearOption(){
  if (!salesYearSelect) return;
  const y = getSalesYear();
  const exists = Array.from(salesYearSelect.options).some(o => parseInt(o.value,10) === y);
  if (!exists){
    const opt = document.createElement("option");
    opt.value = String(y);
    opt.textContent = String(y);
    salesYearSelect.insertBefore(opt, salesYearSelect.firstChild);
  }
  salesYearSelect.value = String(y);
})();

/* ===== helpers ===== */
function todayISO(){
  const d = new Date();
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const dd = String(d.getDate()).padStart(2,'0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}
function esc(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

/* ===== Add modal open/close ===== */
const addBackdrop = document.getElementById('addBackdrop');
document.getElementById('openAddBtn').addEventListener('click', () => addBackdrop.classList.add('show'));
document.getElementById('closeAdd').addEventListener('click', () => addBackdrop.classList.remove('show'));
document.getElementById('cancelAdd').addEventListener('click', () => addBackdrop.classList.remove('show'));

const plantingDate = document.getElementById('plantingDate');
plantingDate.min = todayISO();

/* ===== save plan ===== */
document.getElementById('addPlanForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);

  const res = await fetch('process_add_plan.php', { method:'POST', body: fd });
  const data = await res.json();
  if (!data.success) return alert(data.error || 'Failed to save plan.');

  addBackdrop.classList.remove('show');
  e.target.reset();
  plantingDate.min = todayISO();
  alert('Plan saved!');
});

/* ===== Season folder modal ===== */
const seasonBackdrop = document.getElementById('seasonBackdrop');
const seasonPlansGrid = document.getElementById('seasonPlansGrid');
let currentSeasonOpen = null;

async function openSeason(season){
  currentSeasonOpen = season;

  const year = document.getElementById('yearLabel').innerText.trim();
  document.getElementById('seasonTitle').innerText = (season === 'wet' ? 'Wet Season Plans' : 'Dry Season Plans');

  const res = await fetch(`process_get_season_plans.php?year=${encodeURIComponent(year)}&season=${encodeURIComponent(season)}`);
  const data = await res.json();
  if (!data.success) return alert(data.error || 'Failed to load plans.');

  renderSeasonPlans(data.plans || []);
  seasonBackdrop.classList.add('show');
}
function closeSeasonModal(){
  seasonBackdrop.classList.remove('show');
}

function renderSeasonPlans(plans){
  if (!plans.length){
    seasonPlansGrid.innerHTML = `<div style="color:#6b7b6b;">No plans yet.</div>`;
    return;
  }

  seasonPlansGrid.innerHTML = plans.map(p => `
    <div class="plan-card" style="position:relative;">
      <button class="kebab" type="button" onclick="toggleMenu(${p.id})">⋮</button>

      <div class="menu-pop" id="menu_${p.id}">
        <button type="button" class="danger"
          onclick="event.stopPropagation(); deletePlan(${p.id})">🗑️ Delete</button>
      </div>

      <div class="plan-img">
        <img src="${resolvePlanImage(p.image_path)}" alt=""
             onerror="this.onerror=null; this.src='images/farmer-icon.png';">
      </div>

      <div class="plan-body">
        <div class="plan-title">${esc(p.rice_variety)}</div>
        <div class="plan-meta">Planting Date: ${esc(p.planting_date)}</div>
        <div class="plan-notes">${esc(p.notes || '')}</div>
      </div>
    </div>
  `).join('');
}

function resolvePlanImage(imagePath){
  if (!imagePath) return 'images/farmer-icon.png';

  let p = String(imagePath).trim().replace(/\\/g,'/');
  if (p.startsWith("../")) p = p.slice(3);

  if (/^(https?:\/\/|\/)/i.test(p)) return p;

  if (p.startsWith("uploads/")) return "../" + p; 
  return "../uploads/products/" + p;
}


function toggleMenu(id){
  const menu = document.getElementById(`menu_${id}`);
  document.querySelectorAll('.menu-pop.show').forEach(m => {
    if (m !== menu) m.classList.remove('show');
  });
  menu.classList.toggle('show');
}

// close menu on outside click
document.addEventListener('click', (e) => {
  const isKebab = e.target.classList && e.target.classList.contains('kebab');
  const isMenu = e.target.closest && e.target.closest('.menu-pop');
  if (!isKebab && !isMenu) {
    document.querySelectorAll('.menu-pop.show').forEach(m => m.classList.remove('show'));
  }
});

/* ===== Rotation suggestions (wet+dry) ===== */
let suggestions = { wet:null, dry:null };

async function loadRotationSuggestions(){
  const salesYear = getSalesYear(); // ✅ use retained Sales Year, not planning year
  const res = await fetch(`process_get_rotation_suggestions.php?year=${encodeURIComponent(salesYear)}`);
  const data = await res.json();

  // wet
  if (data.success && data.wet){
    suggestions.wet = data.wet;
    document.getElementById('rotWetEmpty').style.display = 'none';
    document.getElementById('rotWetCard').style.display = 'flex';
    document.getElementById('rotWetImg').src = data.wet.image_url || 'images/farmer-icon.png';
    document.getElementById('rotWetName').innerText = data.wet.product_name;
    document.getElementById('rotWetSold').innerText = `${data.wet.total_sold_qty} ${data.wet.unit || ''}`;
    document.getElementById('rotWetWhy').innerText = data.wet.why;
  } else {
    suggestions.wet = null;
    document.getElementById('rotWetEmpty').style.display = 'block';
    document.getElementById('rotWetCard').style.display = 'none';
  }

  // dry
  if (data.success && data.dry){
    suggestions.dry = data.dry;
    document.getElementById('rotDryEmpty').style.display = 'none';
    document.getElementById('rotDryCard').style.display = 'flex';
    document.getElementById('rotDryImg').src = data.dry.image_url || 'images/farmer-icon.png';
    document.getElementById('rotDryName').innerText = data.dry.product_name;
    document.getElementById('rotDrySold').innerText = `${data.dry.total_sold_qty} ${data.dry.unit || ''}`;
    document.getElementById('rotDryWhy').innerText = data.dry.why;
  } else {
    suggestions.dry = null;
    document.getElementById('rotDryEmpty').style.display = 'block';
    document.getElementById('rotDryCard').style.display = 'none';
  }
}

loadRotationSuggestions();

/* ===== Use suggestion modal ===== */
const useBackdrop = document.getElementById('useBackdrop');
const useForm = document.getElementById('useForm');
document.getElementById('use_planting_date').min = todayISO();

function openUseSuggestion(season){
  const s = suggestions[season];
  if (!s) return alert('No suggestion loaded.');

  const year = document.getElementById('yearLabel').innerText.trim();

  document.getElementById('useTitle').innerText = `Use ${season === 'wet' ? 'Wet' : 'Dry'} Season Suggestion`;
  document.getElementById('use_season').value = season;

  document.getElementById('use_product_id').value = s.product_id;
  document.getElementById('use_plan_year').value = year;

  document.getElementById('use_rice_variety').value = s.product_name || '';
  document.getElementById('use_planting_date').value = todayISO();
  document.getElementById('use_notes').value = s.why || '';

  useBackdrop.classList.add('show');
}
function closeUseModal(){
  useBackdrop.classList.remove('show');
}

useForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(useForm);

  const res = await fetch('process_use_rotation_suggestion.php', { method:'POST', body: fd });
  const data = await res.json();
  if (!data.success) return alert(data.error || 'Failed to insert.');

  closeUseModal();
  alert('Inserted into season folder!');
});

async function deletePlan(id){
  try{
    if (!id) return alert("Missing plan ID.");

    if (!confirm("Delete this plan?")) return;

    const fd = new FormData();
    fd.append("id", id);

    const res = await fetch("process_delete_plan.php", { method:"POST", body: fd });

    const text = await res.text(); // safer than res.json()
    let data;
    try { data = JSON.parse(text); }
    catch(e){
      console.error("Delete returned non-JSON:", text);
      return alert("Delete failed: PHP returned non-JSON. Check console.");
    }

    if (!data.success) return alert(data.error || "Delete failed.");

    // close menu if open
    const menu = document.getElementById(`menu_${id}`);
    if (menu) menu.classList.remove("show");

    // refresh current season list
    if (currentSeasonOpen) openSeason(currentSeasonOpen);
    else location.reload();

  } catch(err){
    console.error(err);
    alert("Delete failed. Check console.");
  }
}

 function toggleTopbar() {
  document.querySelector('.sidebar').classList.toggle('active');
}

</script>

</body>
</html>
