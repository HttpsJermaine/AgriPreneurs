<?php
session_start();

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

$old = $_SESSION['old_register'] ?? [];
unset($_SESSION['old_register']);

function old($key, $default = '') {
    global $old;
    return isset($old[$key]) ? $old[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="css/mobileview.css">
</head>
<body>
<header>
    <?php require 'header.php' ?>
</header>

<div class="register-bg">
<div class="register-card">

    <div class="icon-circle">
        <img src="images/icon.png" alt="Logo">
    </div>

    <h2>Create an Account</h2>
    <p class="subtitle">Select a role to register</p>

    <?php if ($success): ?>
        <p style="color: green; text-align:center; margin-bottom:10px;">
            <?php echo htmlspecialchars($success); ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red; text-align:center; margin-bottom:10px;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="process_register.php" enctype="multipart/form-data">

        <label class="label-header">Register as</label>
        <select id="roleSelect" name="role" class="input-field" required>
            <option value="" disabled <?= old('role') ? '' : 'selected' ?>>Select your role</option>
            <option value="farmer" <?= old('role') === 'farmer' ? 'selected' : '' ?>>Farmer</option>
            <option value="buyer"  <?= old('role') === 'buyer'  ? 'selected' : '' ?>>Buyer</option>
        </select>

        <!-- FARMER FORM -->
        <div id="farmerForm" class="hidden fade">
            <h3 class="section-title">Personal Details</h3>
            <div class="two-col">
                <input type="text" name="farmer_name" class="input-field" placeholder="Full name"
                       value="<?= htmlspecialchars(old('farmer_name')) ?>" required>

                <input type="text" name="farm_area" class="input-field" placeholder="Farm Area (Number of Hectares)"
                       value="<?= htmlspecialchars(old('farm_area')) ?>" required>

                <input type="text" name="farmer_phone" class="input-field" placeholder="Phone Number"
                       value="<?= htmlspecialchars(old('farmer_phone')) ?>" required>

                <input type="text" name="registry_number" class="input-field" placeholder="RSBSA Registered Registry #"
                       value="<?= htmlspecialchars(old('registry_number')) ?>" required>

                <div class="full-width">
                    <label class="label">Farmer Photo</label>
                    <input type="file" name="farmer_photo" class="input-field file-input" accept="image/*">
                </div>
            </div>

            <h3 class="section-title">Address Details</h3>
            <div class="two-col">
                <select id="farmer_province" name="farmer_province_code" class="input-field" required>
                    <option value="" disabled selected>Select Province</option>
                </select>

                <select id="farmer_city" name="farmer_citymun_code" class="input-field" required disabled>
                    <option value="" disabled selected>Select Municipality / City</option>
                </select>

                <select id="farmer_barangay" name="farmer_barangay" class="input-field" required disabled>
                    <option value="" disabled selected>Select Barangay</option>
                </select>

                <input type="text" name="farmer_zip" class="input-field" placeholder="ZIP Code"
                       value="<?= htmlspecialchars(old('farmer_zip')) ?>" required>
            </div>

            <div class="full-width" style="margin-top:10px;">
                <label class="label">Full Address Details</label>
                <input type="text" name="farmer_full_address" class="input-field"
                       placeholder="Enter house number, street number, barangay, city, and province."
                       value="<?= htmlspecialchars(old('farmer_full_address')) ?>" required>
            </div>

            <!-- store readable names -->
            <input type="hidden" id="farmer_province_name" name="farmer_province_name"
                   value="<?= htmlspecialchars(old('farmer_province_name')) ?>">

            <input type="hidden" id="farmer_city_name" name="farmer_city_name"
                   value="<?= htmlspecialchars(old('farmer_city_name')) ?>">

            <h3 class="section-title">Account Details</h3>
            <div class="two-col">
                <input type="email" name="farmer_email" class="input-field" placeholder="Email"
                       value="<?= htmlspecialchars(old('farmer_email')) ?>" required>

                <input type="text" name="farmer_username" class="input-field" placeholder="Username"
                       value="<?= htmlspecialchars(old('farmer_username')) ?>" required>

                <input type="password" id="farmerPass" name="farmer_password" class="input-field" placeholder="Password" required>
                <input type="password" id="farmerConfirm" name="farmer_confirm" class="input-field" placeholder="Confirm Password" required>
            </div>

            <div class="show-pass">
                <input type="checkbox" id="showFarmerPassword">
                <label for="showFarmerPassword">Show Password</label>
            </div>
        </div>

        <!-- Buyer Form -->
        <div id="buyerForm" class="hidden fade">
            <h3 class="section-title">Personal Details</h3>
            <div class="two-col">
                <input type="text" name="buyer_name" class="input-field" placeholder="Full Name"
                       value="<?= htmlspecialchars(old('buyer_name')) ?>" required>

                <input type="tel" name="buyer_phone" class="input-field" placeholder="Phone Number"
                       value="<?= htmlspecialchars(old('buyer_phone')) ?>" required>

                <div class="full-width">
                    <label class="label">Buyer Photo</label>
                    <input type="file" name="buyer_photo" class="input-field file-input" accept="image/*">
                </div>
            </div>

            <h3 class="section-title">Account Details</h3>
            <div class="two-col">
                <input type="email" name="buyer_email" class="input-field" placeholder="Email"
                       value="<?= htmlspecialchars(old('buyer_email')) ?>" required>

                <input type="text" name="buyer_username" class="input-field" placeholder="Username"
                       value="<?= htmlspecialchars(old('buyer_username')) ?>" required>

                <input type="password" id="buyerPass" name="buyer_password" class="input-field" placeholder="Password" required>
                <input type="password" id="buyerConfirm" name="buyer_confirm" class="input-field" placeholder="Confirm Password" required>
            </div>

            <div class="show-pass">
                <input type="checkbox" id="showBuyerPassword">
                <label for="showBuyerPassword">Show Password</label>
            </div>
        </div>

        <button type="submit" class="register-btn">Register</button>

        <p class="bottom-text">Already have an account?
            <a href="login.php">Login here</a>
        </p>

    </form>
</div>
</div>

<?php require 'footer.php' ?>

<script>
const OLD = <?php echo json_encode($old, JSON_UNESCAPED_UNICODE); ?>;

const roleSelect = document.getElementById("roleSelect");
const farmerForm = document.getElementById("farmerForm");
const buyerForm  = document.getElementById("buyerForm");

function setEnabled(container, enabled) {
  const fields = container.querySelectorAll("input, select, textarea");
  fields.forEach(el => el.disabled = !enabled);
}

function showForm(which) {
  if (which === "farmer") {
    farmerForm.classList.remove("hidden");
    buyerForm.classList.add("hidden");
    setEnabled(farmerForm, true);
    setEnabled(buyerForm, false);
  } else if (which === "buyer") {
    buyerForm.classList.remove("hidden");
    farmerForm.classList.add("hidden");
    setEnabled(buyerForm, true);
    setEnabled(farmerForm, false);
  } else {
    farmerForm.classList.add("hidden");
    buyerForm.classList.add("hidden");
    setEnabled(farmerForm, false);
    setEnabled(buyerForm, false);
  }
}

// Restore role on load
const initialRole = (OLD && OLD.role) ? OLD.role : "";
if (initialRole) {
  roleSelect.value = initialRole;
  showForm(initialRole);
} else {
  showForm("");
}

roleSelect.addEventListener("change", () => {
  showForm(roleSelect.value);
});

// Show password toggles 
const farmerToggle = document.getElementById("showFarmerPassword");
if (farmerToggle) {
  farmerToggle.addEventListener("change", function() {
    const t = this.checked ? "text" : "password";
    const p1 = document.getElementById("farmerPass");
    const p2 = document.getElementById("farmerConfirm");
    if (p1) p1.type = t;
    if (p2) p2.type = t;
  });
}

const buyerToggle = document.getElementById("showBuyerPassword");
if (buyerToggle) {
  buyerToggle.addEventListener("change", function() {
    const t = this.checked ? "text" : "password";
    const p1 = document.getElementById("buyerPass");
    const p2 = document.getElementById("buyerConfirm");
    if (p1) p1.type = t;
    if (p2) p2.type = t;
  });
}

const DATA_PATH = "assets/ph-address";
let PH = null;

async function loadPHOnce() {
  if (PH) return PH;

  const [provRes, cityRes, brgyRes, zipRes] = await Promise.all([
    fetch(`${DATA_PATH}/provinces.json`),
    fetch(`${DATA_PATH}/city-mun.json`),
    fetch(`${DATA_PATH}/barangays.json`),
    fetch(`${DATA_PATH}/zipcodes.json`)
  ]);

  if (!provRes.ok || !cityRes.ok || !brgyRes.ok || !zipRes.ok) {
    throw new Error("Failed to load PH address.");
  }

  const provincesRaw = await provRes.json();
  const cityRaw      = await cityRes.json();
  const brgyRaw      = await brgyRes.json();
  const zipRaw       = await zipRes.json();

  const provinces = provincesRaw
    .filter(p => p.prov_code && p.name)
    .map(p => ({ prov_code: String(p.prov_code), name: p.name }))
    .sort((a,b) => a.name.localeCompare(b.name));

  const cities = cityRaw
    .filter(c => c.prov_code && c.mun_code && c.name)
    .map(c => ({
      prov_code: String(c.prov_code),
      mun_code: String(c.mun_code),
      name: c.name
    }));

  const barangays = brgyRaw
    .filter(b => b.mun_code && b.name)
    .map(b => ({ mun_code: String(b.mun_code), name: b.name }));

  // province -> cities
  const cityByProv = new Map();
  for (const c of cities) {
    if (!cityByProv.has(c.prov_code)) cityByProv.set(c.prov_code, []);
    cityByProv.get(c.prov_code).push({ mun_code: c.mun_code, name: c.name });
  }
  for (const [k, arr] of cityByProv.entries()) {
    arr.sort((a,b) => a.name.localeCompare(b.name));
    cityByProv.set(k, arr);
  }

  // mun -> barangays
  const brgyByMun = new Map();
  for (const b of barangays) {
    if (!brgyByMun.has(b.mun_code)) brgyByMun.set(b.mun_code, []);
    brgyByMun.get(b.mun_code).push(b.name);
  }
  for (const [k, arr] of brgyByMun.entries()) {
    arr.sort((a,b) => a.localeCompare(b));
    brgyByMun.set(k, arr);
  }

  // mun -> zip
  const zipByMun = new Map();
  if (Array.isArray(zipRaw)) {
    for (const z of zipRaw) {
      if (!z?.mun_code || !z?.zip) continue;
      zipByMun.set(String(z.mun_code), String(z.zip));
    }
  } else {
    for (const [mun, zip] of Object.entries(zipRaw || {})) {
      zipByMun.set(String(mun), String(zip));
    }
  }

  PH = { provinces, cityByProv, brgyByMun, zipByMun };
  return PH;
}

function fillSelect(selectEl, placeholder, items, valueKey, labelKey) {
  selectEl.innerHTML = "";
  const opt0 = document.createElement("option");
  opt0.value = "";
  opt0.textContent = placeholder;
  opt0.disabled = true;
  opt0.selected = true;
  selectEl.appendChild(opt0);

  for (const it of items) {
    const opt = document.createElement("option");
    opt.value = it[valueKey];
    opt.textContent = it[labelKey];
    selectEl.appendChild(opt);
  }
}

function fillSelectSimple(selectEl, placeholder, items) {
  selectEl.innerHTML = "";
  const opt0 = document.createElement("option");
  opt0.value = "";
  opt0.textContent = placeholder;
  opt0.disabled = true;
  opt0.selected = true;
  selectEl.appendChild(opt0);

  for (const v of items) {
    const opt = document.createElement("option");
    opt.value = v;
    opt.textContent = v;
    selectEl.appendChild(opt);
  }
}

async function setupPH(prefix) {
  const provSel  = document.getElementById(`${prefix}_province`);
  const citySel  = document.getElementById(`${prefix}_city`);
  const brgySel  = document.getElementById(`${prefix}_barangay`);
  const provName = document.getElementById(`${prefix}_province_name`);
  const cityName = document.getElementById(`${prefix}_city_name`);

  // ZIP is an input in your form (farmer_zip). Buyer form has no ZIP input.
  const zipInput = document.querySelector(`input[name="${prefix}_zip"]`);

  if (!provSel || !citySel || !brgySel) return;

  const data = await loadPHOnce();

  // Fill provinces first
  fillSelect(provSel, "Select Province", data.provinces, "prov_code", "name");
  citySel.disabled = true;
  brgySel.disabled = true;

  // Restore old values AFTER options exist
  const provKey = `${prefix}_province_code`;
  const cityKey = `${prefix}_citymun_code`;
  const brgyKey = `${prefix}_barangay`;
  const zipKey  = `${prefix}_zip`;

  const oldProv = OLD?.[provKey] || "";
  const oldCity = OLD?.[cityKey] || "";
  const oldBrgy = OLD?.[brgyKey] || "";
  const oldZip  = OLD?.[zipKey]  || "";

  if (oldZip && zipInput) zipInput.value = oldZip;

  function onProvinceChange() {
    if (provName) provName.value = provSel.options[provSel.selectedIndex]?.text || "";

    const provCode = provSel.value;
    const cities = data.cityByProv.get(provCode) || [];

    fillSelect(citySel, "Select Municipality / City", cities, "mun_code", "name");
    citySel.disabled = cities.length === 0;

    fillSelectSimple(brgySel, "Select Barangay", []);
    brgySel.disabled = true;

    if (cityName) cityName.value = "";
  }

  function onCityChange() {
    if (cityName) cityName.value = citySel.options[citySel.selectedIndex]?.text || "";

    const munCode = String(citySel.value || "");

    // AUTO ZIP FILL 
    if (zipInput && munCode) {
      const z = data.zipByMun.get(munCode);
      if (z) zipInput.value = z; // fill if known, do not clear if unknown
    }

    const brgys = data.brgyByMun.get(munCode) || [];
    fillSelectSimple(brgySel, "Select Barangay", brgys);
    brgySel.disabled = brgys.length === 0;
  }

  provSel.addEventListener("change", onProvinceChange);
  citySel.addEventListener("change", onCityChange);

  // Restore old selections 
  if (oldProv) {
    provSel.value = oldProv;
    onProvinceChange();

    if (oldCity) {
      citySel.value = oldCity;
      onCityChange();

      if (oldBrgy) {
        brgySel.value = oldBrgy;
      }
    }
  }
}

setupPH("farmer").catch(console.error);
setupPH("buyer").catch(console.error);
</script>

</body>
</html>
