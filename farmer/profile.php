<?php
session_start();
require_once "../db_connection.php";

$farmerId   = (int)$_SESSION['user_id'];
$farmerName = htmlspecialchars($_SESSION['username']);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../login.php?error=" . urlencode("Access denied."));
    exit;
}

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
    <title>My Profile</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/mobileview.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for product sections */
        .product-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 0.5rem;
        }

        .product-tab {
            padding: 0.5rem 1.5rem;
            cursor: pointer;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.2s;
            color: #666;
        }

        .product-tab:hover {
            background: #f0f7f0;
            color: #1e3c2c;
        }

        .product-tab.active {
            background: #2d6a4f;
            color: white;
        }

        .product-section {
            display: none;
            margin-bottom: 2rem;
        }

        .product-section.active {
            display: block;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.active {
            background: #e0f2e2;
            color: #2d6a4f;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .rejection-reason {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #666;
        }

        .product-count {
            background: #e0e8e0;
            color: #1e3c2c;
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #2d6a4f;
        }

        .product-image-preview {
            margin-top: 10px;
            text-align: center;
        }

        .product-image-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            border: 2px solid #2d6a4f;
            padding: 5px;
        }
    </style>
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
        <img src="<?php echo $farmerPhoto; ?>" alt="Farmer" class="avatar">
        <p class="farmer-name">Hello, <?php echo $farmerName; ?>!</p>
    </div>

    <nav class="menu">
            <a href="farmer_dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="crops.php" class="menu-item"> <i class="fas fa-calendar-check"></i> Plans</a>
            <a href="orders.php" class="menu-item"> <i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="stocks.php" class="menu-item"> <i class="fas fa-boxes"></i> Stocks</a>
            <a href="earnings.php" class="menu-item"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <a href="profile.php" class="menu-item active"><i class="fas fa-user-circle"></i>  Profile</a>
            <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
    </div>

    <div class="profile-wrapper">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your personal information and products</p>
        </div>

        <!-- Profile Information -->
        <div class="profile-container">
            <!-- Left Column - Basic Info -->
            <div class="profile-card">
                <div class="profile-header">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='55' fill='%23e0f2e2' stroke='%232d6a4f' stroke-width='4'/%3E%3Ccircle cx='60' cy='45' r='15' fill='%232d6a4f'/%3E%3Cpath d='M30 85 Q60 100, 90 85' stroke='%232d6a4f' stroke-width='8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E" 
                         alt="Profile" class="profile-avatar" id="profileAvatar">
                    <div class="profile-name-section">
                        <h2 id="displayName">Loading...</h2>
                        <span class="profile-badge">Farmer</span>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <div class="detail-label">Registry Number</div>
                        <div class="detail-value" id="displayRegistry">Loading...</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value" id="displayPhone">Loading...</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Farm Area</div>
                        <div class="detail-value" id="displayFarmArea">Loading...</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value" id="displayEmail">Loading...</div>
                    </div>
                </div>

                <div class="detail-item" style="margin-top: 1rem;">
                    <div class="detail-label">Full Address</div>
                    <div class="detail-value" id="displayAddress">Loading...</div>
                </div>

                <button class="edit-btn" onclick="openModal('editProfileModal')">
                    <span>✏️</span> Edit Profile
                </button>
            </div>

            <!-- Right Column - Stats -->
            <div class="profile-card">
                <h3 style="color: #1e3c2c; margin-bottom: 1.5rem;">Account Statistics</h3>
                <div class="profile-details">
                    <div class="detail-item">
                        <div class="detail-label">Member Since</div>
                        <div class="detail-value" id="memberSince">Loading...</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">All Products</div>
                        <div class="detail-value" id="totalProducts">0</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account Status</div>
                        <div class="detail-value">
                            <span class="profile-badge" style="background: #e0f2e2;" id="accountStatus">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="products-header">
            <h2 class="products-title">My Products</h2>
            <button class="add-product-btn" onclick="openModal('addProductModal')">
                <span>➕</span> Add New Product
            </button>
        </div>

        <!-- Active Products Section -->
        <div id="product-section" class="product-section active">
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Rice Variety</th>
                            <th>Price per Sack</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody">
                        <tr><td colspan="4" class="loading">Loading products...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('editProfileModal')">&times;</button>
            <h2>Edit Profile</h2>
            <form id="editProfileForm" onsubmit="saveProfile(event)">
                <div class="form-group">
                    <label>Farmer Name <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
                    <input type="text" id="farmerName" disabled style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;">
                </div>
                <div class="form-group">
                    <label>Registry Number <span style="color: #999; font-size: 12px; margin-left: 5px;">(Cannot be changed)</span></label>
                    <input type="text" id="registryNum" disabled style="background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7;">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" id="phone" required placeholder="e.g., 09123456789">
                </div>
                <div class="form-group">
                    <label>Farm Area</label>
                    <input type="text" id="farmArea" required placeholder="e.g., 5.2 hectares">
                </div>
                <div class="form-group">
                    <label>Full Address</label>
                    <input type="text" id="fullAddress" required placeholder="e.g., #123 Street, Barangay, City">
                </div>
                <div class="form-group">
                    <label>Profile Photo</label>
                    <div class="file-input-area" onclick="document.getElementById('profilePhoto').click()">
                        Click to upload or change photo
                    </div>
                    <input type="file" id="profilePhoto" accept="image/*" style="display: none;" onchange="updateFileName(this, 'editProfileModal')">
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Only phone, farm area, address, and photo can be updated
                    </p>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">Save Changes</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editProfileModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('addProductModal')">&times;</button>
        <h2>Add New Product</h2>
        <form id="addProductForm" onsubmit="addProduct(event)">
            <div class="form-group">
                <label>Rice Variety</label>
                <select id="product_id" required>
                    <option value="">Select Rice Variety</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="submit-btn">Add Product</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addProductModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
            <h2>Edit Product</h2>
            <form id="editProductForm" onsubmit="updateProduct(event)">
                <input type="hidden" id="editProductId">
                <div class="form-group">
                    <label>Product Image</label>
                    <div class="file-input-area" onclick="document.getElementById('editProductImage').click()">
                        Click to upload new image (optional)
                    </div>
                    <input type="file" id="editProductImage" accept="image/*" style="display: none;" onchange="updateFileName(this, 'editProductModal')">
                </div>
                <div class="form-group">
                    <label>Rice Variety</label>
                    <input type="text" id="editRiceVariety" required>
                </div>
                <div class="form-group">
                    <label>Price per Sack (₱)</label>
                    <input type="number" id="editPrice" required min="0" step="0.01">
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">Update Product</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editProductModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentProducts = [];

        // Load profile data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProfileData();
            loadProductOptions();
        });

        function loadProfileData() {
            fetch('get_profile_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update profile info
                        updateProfileInfo(data.user, data.farmer);
                        
                        // Update stats
                        updateStats(data.stats);
                        
                        // Store products and render tables
                        currentProducts = data.products;
                        renderProductsTables(data.products);
                        
                        // Fill edit profile modal with current data
                        fillEditProfileModal(data.farmer);
                    } else {
                        alert('Error loading profile data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading profile data');
                });
        }

        function updateProfileInfo(user, farmer) {
            if (farmer) {
                document.getElementById('displayName').innerText = farmer.farmer_name || user.username;
                document.getElementById('displayRegistry').innerText = farmer.registry_num || 'Not registered';
                document.getElementById('displayPhone').innerText = farmer.phone || 'Not provided';
                document.getElementById('displayFarmArea').innerText = farmer.farm_area || 'Not specified';
                document.getElementById('displayAddress').innerText = farmer.full_address || 'No address provided';
                
                if (farmer.photo) {
                    document.getElementById('profileAvatar').src = '../uploads/' + farmer.photo;
                }
            } else {
                document.getElementById('displayName').innerText = user.username;
                document.getElementById('displayRegistry').innerText = 'Not registered';
                document.getElementById('displayPhone').innerText = 'Not provided';
                document.getElementById('displayFarmArea').innerText = 'Not specified';
                document.getElementById('displayAddress').innerText = 'No address provided';
            }
            
            document.getElementById('displayEmail').innerText = user.email || 'No email';
            document.getElementById('accountStatus').innerText = user.status || 'Active';
        }

        function updateStats(stats) {
            document.getElementById('memberSince').innerText = stats.memberSince;
            document.getElementById('totalProducts').innerText = stats.totalProducts;
        }

        function formatDate(dt) {
            if (!dt) return '-';
            const d = new Date(dt.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dt;
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function renderProductsTables(products) {
            const body = document.getElementById('productsBody');
            
            console.log('Rendering products:', products);

            if (!products || products.length === 0) {
                body.innerHTML = '<tr><td colspan="4" class="loading">No products yet. Click "Add New Product" to get started.</td></tr>';
                return;
            }

            body.innerHTML = products.map(product => {
                let statusClass = 'status-badge';
                let statusText = product.status || 'pending';
                
                switch(product.status) {
                    case 'active':
                        statusClass += ' active';
                        statusText = 'Active';
                        break;
                    case 'pending':
                        statusClass += ' pending';
                        statusText = 'Pending Approval';
                        break;
                    case 'rejected':
                        statusClass += ' rejected';
                        statusText = 'Rejected';
                        break;
                    default:
                        statusClass += ' pending';
                        statusText = 'Pending';
                }
                
                return `
                    <tr>
                        <td>${escapeHtml(product.rice_variety)}</td>
                        <td>₱${Number(product.price_per_sack).toFixed(2)}</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                        <td>
                            <button onclick="deleteProduct(${product.id})" class="delete-btn" style="background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer;">Delete</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function fillEditProfileModal(farmer) {
            if (farmer) {
                document.getElementById('farmerName').value = farmer.farmer_name || '';
                document.getElementById('registryNum').value = farmer.registry_num || '';
                document.getElementById('phone').value = farmer.phone || '';
                document.getElementById('farmArea').value = farmer.farm_area || '';
                document.getElementById('fullAddress').value = farmer.full_address || '';
            }
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

     function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        
        // Reset form when closing add product modal
        if (modalId === 'addProductModal') {
            document.getElementById('addProductForm').reset();
            // Remove any image-related resets
        } else if (modalId === 'editProductModal') {
            document.getElementById('editProductForm').reset();
            const fileArea = document.querySelector('#editProductModal .file-input-area');
            if (fileArea) {
                fileArea.innerHTML = 'Click to upload new image (optional)';
                fileArea.classList.remove('has-file');
            }
        }
    }
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Update file input display
        function updateFileName(input, modalId) {
            const modal = document.getElementById(modalId);
            const fileArea = modal.querySelector('.file-input-area');
            if (input.files && input.files[0]) {
                fileArea.innerHTML = `Selected: ${input.files[0].name}`;
                fileArea.classList.add('has-file');
            } else {
                if (modalId === 'editProductModal') {
                    fileArea.innerHTML = 'Click to upload new image (optional)';
                } else {
                    fileArea.innerHTML = 'Click to upload or change photo';
                }
                fileArea.classList.remove('has-file');
            }
        }

        // Show product image preview when selecting a variety
        function showProductImagePreview(select) {
            const selectedOption = select.options[select.selectedIndex];
            const imageUrl = selectedOption.getAttribute('data-image');
            const previewDiv = document.getElementById('productImagePreview');
            const previewImg = document.getElementById('selectedProductImage');
            
            if (imageUrl && select.value !== '') {
                previewImg.src = imageUrl;
                previewDiv.style.display = 'block';
            } else {
                previewDiv.style.display = 'none';
                previewImg.src = '';
            }
        }

        // Save profile
        function saveProfile(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('farmerName', document.getElementById('farmerName').value);
            formData.append('registryNum', document.getElementById('registryNum').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('farmArea', document.getElementById('farmArea').value);
            formData.append('fullAddress', document.getElementById('fullAddress').value);
            
            const photoInput = document.getElementById('profilePhoto');
            if (photoInput.files[0]) {
                formData.append('profilePhoto', photoInput.files[0]);
            }
            
            fetch('save_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    closeModal('editProfileModal');
                    loadProfileData();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving profile');
            });
        }
        
        // Add product - WITHOUT image upload
        function addProduct(event) {
            event.preventDefault();
            
            console.log("Adding product...");
            
            const productSelect = document.getElementById('product_id');
            const productId = productSelect.value;
            
            console.log("Selected product ID:", productId);
            
            if (!productId) {
                alert('Please select a rice variety');
                return;
            }
            
            // Check for duplicate in current products
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const selectedProductName = selectedOption ? selectedOption.textContent.split(' - ')[0] : '';
            
            if (currentProducts && currentProducts.length > 0) {
                const existingProduct = currentProducts.find(p => p.rice_variety === selectedProductName);
                if (existingProduct) {
                    alert('❌ You already have "' + selectedProductName + '" in your products');
                    return;
                }
            }
            
            const formData = new FormData();
            formData.append('product_id', productId);
            // NO image appended here
            
            const submitBtn = document.querySelector('#addProductModal .submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Adding...';
            submitBtn.disabled = true;
            
            fetch('add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Response data:", data);
                
                if (data.success) {
                    alert('✓ Product added successfully!');
                    closeModal('addProductModal');
                    loadProfileData(); // Reload the page data
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
         
        // Update product
        function updateProduct(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('product_id', document.getElementById('editProductId').value);
            formData.append('riceVariety', document.getElementById('editRiceVariety').value);
            formData.append('price', document.getElementById('editPrice').value);
            
            const imageInput = document.getElementById('editProductImage');
            if (imageInput.files[0]) {
                formData.append('productImage', imageInput.files[0]);
            }
            
            fetch('edit_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product updated successfully! It will be reviewed again by admin.');
                    closeModal('editProductModal');
                    loadProfileData();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product');
            });
        }

        // Delete product
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('delete_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully');
                        loadProfileData();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting product');
                });
            }
        }

        function loadProductOptions() {
            console.log("Loading product options...");
            
            fetch('get_rice_products.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Product options received:", data);
                    
                    const select = document.getElementById('product_id');
                    
                    if (!data.success) {
                        console.error("Error from server:", data.error);
                        select.innerHTML = '<option value="">Error loading products</option>';
                        return;
                    }
                    
                    if (!data.products || data.products.length === 0) {
                        select.innerHTML = '<option value="">No products available</option>';
                        return;
                    }
                    
                    select.innerHTML = '<option value="">Select Rice Variety</option>';
                    
                    data.products.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.id;
                        option.textContent = `${product.name} - ₱${parseFloat(product.price).toFixed(2)}`;
                        
                        if (product.image) {
                            option.setAttribute('data-image', '../uploads/' + product.image);
                        }
                        
                        select.appendChild(option);
                    });
                    
                    console.log(`Loaded ${data.products.length} products`);
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    const select = document.getElementById('product_id');
                    select.innerHTML = '<option value="">Error loading products</option>';
                });
        }

        function toggleTopbar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>