<?php
session_start();
require_once "../db_connection.php"; // correct path for admin_page/

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
<link rel="stylesheet" href="css/farmers_list.css">
<link rel="stylesheet" href="css/mobileview.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<div class="sidebar">
    <h2 class="logo">🌿 PLAMAL</h2>

    <div class="farmers-info">
        <img src="images/icon.png" alt="Admin" class="farmers_avatar">
        <p class="farmers-name">Hello, <?php echo $adminName; ?></p>
    </div>

    <nav class="menu">
        <a href="admin_dashboard.php" class="menu-item">🏚️ Dashboard</a>
        <a href="user.php" class="menu-item">👥 Manage Users</a>
        <a href="registration.php" class="menu-item">📃 User Registration</a>
        <a href="farmers_list.php" class="menu-item active">👩‍🌾 Farmers</a>
        <a href="logout.php" class="menu-item logout">🚪 Logout</a>
    </nav>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Farmers List</h1>
            <p class="page-subtitle">View and manage all registered farmers and their active products</p>
        </div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search farmers by name or registry number" onkeyup="searchFarmers()">
        </div>
    </div>

    <!-- Farmers Grid -->
    <div class="farmers-grid" id="farmersGrid">
        <div class="loading">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <p>Loading farmers...</p>
        </div>
    </div>

    <!-- Selected Farmer Products Section -->
    <div class="products-section" id="productsSection" style="display: none;">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-box"></i>
                <span id="selectedFarmerName">Farmer's Products</span>
            </div>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th>Rice Variety</th>
                    <th>Price Per Sack</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                <tr>
                    <td colspan="3" class="no-data">
                        Select a farmer to view their products
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Admin Products Management Section -->
<div class="admin-products-section">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-database"></i>
            <span>Rice Products Management</span>
        </div>
        <button class="add-rice-btn" onclick="openRiceProductModal()">
            <i class="fas fa-plus"></i> Add Rice Variety
        </button>
    </div>
    
    <div class="rice-products-table-container">
        <table class="rice-products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Rice Variety</th>
                    <th>Price (₱)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="riceProductsTableBody">
                <tr>
                    <td colspan="4" class="loading">Loading products...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Rice Product Modal -->
<div id="riceProductModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="riceProductModalTitle">Add Rice Product</h2>
            <button class="modal-close" onclick="closeRiceProductModal()">&times;</button>
        </div>
        <form id="riceProductForm" onsubmit="saveRiceProduct(event)">
            <input type="hidden" id="editProductId">
            <div class="form-group">
                <label>Product Image</label>
                <div class="image-preview-area" id="imagePreviewArea">
                    <img id="imagePreview" src="" alt="Preview" style="display: none; max-width: 200px; margin-bottom: 10px;">
                    <div class="file-input-area" onclick="document.getElementById('riceProductImage').click()">
                        <i class="fas fa-upload"></i> Click to upload image
                    </div>
                </div>
                <input type="file" id="riceProductImage" accept="image/*" style="display: none;" onchange="previewImage(this)">
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Allowed formats: JPG, PNG, GIF, WEBP</p>
            </div>
            <div class="form-group">
                <label>Rice Variety Name</label>
                <input type="text" id="riceVarietyName" required placeholder="e.g., Hybrid Rice, RC160">
            </div>
            <div class="form-group">
                <label>Price per Sack (₱)</label>
                <input type="number" id="ricePrice" required min="0" step="0.01" placeholder="0.00">
            </div>
            <div class="modal-actions">
                <button type="submit" class="submit-btn">Save Product</button>
                <button type="button" class="cancel-btn" onclick="closeRiceProductModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
    let selectedFarmerId = null;
    let farmers = [];

    // Load farmers on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadFarmers();
    });

    function loadFarmers() {
        document.getElementById('farmersGrid').innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p>Loading farmers...</p>
            </div>
        `;

        fetch('get_farmers.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Failed');
                farmers = data.farmers;
                renderFarmers(farmers);
            })
            .catch(err => {
                console.error(err);
                document.getElementById('farmersGrid').innerHTML =
                    `<div class="no-data">Error loading farmers</div>`;
            });
    }

    function renderFarmers(farmersList) {
        const grid = document.getElementById('farmersGrid');

        if (!farmersList.length) {
            grid.innerHTML = '<div class="no-data">No farmers found</div>';
            return;
        }

        grid.innerHTML = farmersList.map(farmer => {
            const name = farmer.name || 'Unknown Farmer';
            const phone = farmer.phone || '';
            const registry = farmer.registry || '';

            const avatarHtml = farmer.photo
                ? `<img class="farmer-avatar-img" src="../uploads/${encodeURIComponent(farmer.photo)}" alt="${escapeHtml(name)}">`
                : `<div class="farmer-avatar-text">${escapeHtml(getInitials(name))}</div>`;

            return `
                <div class="farmer-card">
                    <div class="farmer-header">
                        <div class="farmer-avatar">${avatarHtml}</div>
                        <div class="farmer-info">
                            <h3>${escapeHtml(name)}</h3>
                            <p><i class="fas fa-phone"></i> ${escapeHtml(phone)}</p>
                            ${registry ? `<p><i class="fas fa-id-card"></i> ${escapeHtml(registry)}</p>` : ''}
                        </div>
                    </div>
                    <button class="view-products-btn" onclick="viewFarmerProducts(${farmer.id})">
                        <i class="fas fa-eye"></i> View Products
                    </button>
                </div>
            `;
        }).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function getInitials(name) {
        return (name || '')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(w => w[0]?.toUpperCase() || '')
            .join('') || 'F';
    }

    function searchFarmers() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();

        const filtered = farmers.filter(f => {
            const name = (f.name || '').toLowerCase();
            const registry = (f.registry || '').toLowerCase();
            const phone = (f.phone || '');
            return name.includes(searchTerm) || registry.includes(searchTerm) || phone.includes(searchTerm);
        });

        renderFarmers(filtered);
    }

    function viewFarmerProducts(farmerId) {
        selectedFarmerId = farmerId;
        const farmer = farmers.find(f => f.id === farmerId);
        
        document.getElementById('selectedFarmerName').innerHTML = `<i class="fas fa-tractor"></i> ${farmer.name}'s Active Products`;
        document.getElementById('productsSection').style.display = 'block';
        
        // Scroll to products section
        document.getElementById('productsSection').scrollIntoView({ behavior: 'smooth' });
        
        // Load active products for this farmer
        loadFarmerProducts(farmerId);
    }

    function loadFarmerProducts(farmerId) {
    document.getElementById('productsTableBody').innerHTML = `
        <tr>
            <td colspan="3" class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading products...
            </td>
        </tr>
    `;

    // Use your existing get_farmer_products.php
    fetch('get_farmer_products.php?farmer_id=' + encodeURIComponent(farmerId))
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.error || 'Failed');
            renderProductsTable(data.products);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('productsTableBody').innerHTML =
                `<tr><td colspan="3" class="no-data">Error loading products</td></tr>`;
        });
    }

    function renderProductsTable(productsList) {
        const tbody = document.getElementById('productsTableBody');

        if (!productsList || productsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="no-data">No active products found</td></tr>';
            return;
        }

        tbody.innerHTML = productsList.map(p => `
            <tr>
                <td data-label="Rice Variety">${escapeHtml(p.rice_variety)}</td>
                <td data-label="Price">₱${Number(p.price_per_sack).toFixed(2)}</td>
                <td data-label="Date Added">${formatDate(p.created_at)}</td>
            </tr>
        `).join('');
    }

    function formatDate(dt) {
        if (!dt) return '-';
        const d = new Date(dt.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dt;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }
    
    // Rice Products Management Functions
let currentRiceProducts = [];

function loadRiceProducts() {
    console.log("Loading rice products...");
    
    fetch('admin_api/get_rice_products.php')
        .then(response => {
            console.log("Response status:", response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Raw products data received:", data);
            
            if (data.success) {
                // Make sure we're storing the products array correctly
                currentRiceProducts = data.products;
                console.log("Stored products array:", currentRiceProducts);
                console.log("Number of products:", currentRiceProducts.length);
                
                // Log each product's ID for debugging
                currentRiceProducts.forEach(product => {
                    console.log(`Product ID: ${product.id}, Name: ${product.name}, Type: ${typeof product.id}`);
                });
                
                renderRiceProductsTable(data.products);
            } else {
                console.error("Error from server:", data.error);
                document.getElementById('riceProductsTableBody').innerHTML = 
                    '<tr><td colspan="4" class="no-data">Error: ' + (data.error || 'Unknown error') + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            document.getElementById('riceProductsTableBody').innerHTML = 
                '<tr><td colspan="4" class="no-data">Error loading products: ' + error.message + '</td></tr>';
        });
}

function renderRiceProductsTable(products) {
    const tbody = document.getElementById('riceProductsTableBody');
    
    console.log("Rendering products table with:", products);
    
    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="no-data">No rice varieties found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => {
        // Ensure product.id is a number
        const productId = parseInt(product.id);
        console.log(`Rendering product: ID=${productId}, Name=${product.name}`);
        
        let imageHtml = '';
        if (product.image && product.image !== '') {
            let imgPath = '../uploads/' + product.image;
            imageHtml = `<img src="${imgPath}" alt="${escapeHtml(product.name)}" class="product-thumbnail" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\' viewBox=\'0 0 50 50\'%3E%3Crect width=\'50\' height=\'50\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'25\' y=\'25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23666\'%3ENo Image%3C/text%3E%3C/svg%3E'">`;
        } else {
            imageHtml = `<div class="no-image"><i class="fas fa-image"></i></div>`;
        }
        
        return `
            <tr>
                <td>${imageHtml}</td>
                <td><strong>${escapeHtml(product.name)}</strong></td>
                <td>₱${Number(product.price).toFixed(2)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="edit-rice-btn" onclick="editRiceProduct(${productId})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-rice-btn" onclick="deleteRiceProduct(${productId})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openRiceProductModal() {
    document.getElementById('riceProductModalTitle').innerText = 'Add Rice Product';
    document.getElementById('editProductId').value = '';
    document.getElementById('riceVarietyName').value = '';
    document.getElementById('ricePrice').value = '';
    document.getElementById('riceProductForm').reset();
    
    // Reset image preview
    const preview = document.getElementById('imagePreview');
    preview.style.display = 'none';
    preview.src = '';
    
    document.getElementById('riceProductModal').style.display = 'flex';
}

function closeRiceProductModal() {
    document.getElementById('riceProductModal').style.display = 'none';
    document.getElementById('riceProductForm').reset();
    
    const preview = document.getElementById('imagePreview');
    preview.style.display = 'none';
    preview.src = '';
}

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
        preview.src = '';
    }
}

function saveRiceProduct(event) {
    event.preventDefault();
    
    const productId = document.getElementById('editProductId').value;
    const name = document.getElementById('riceVarietyName').value.trim();
    const price = document.getElementById('ricePrice').value;
    const imageFile = document.getElementById('riceProductImage').files[0];
    
    if (!name || !price) {
        alert('Please fill in all required fields');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('price', price);
    
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    let url = 'admin_api/add_rice_product.php';
    let method = 'POST';
    
    if (productId) {
        url = 'admin_api/edit_rice_product.php';
        formData.append('product_id', productId);
    }
    
    const submitBtn = document.querySelector('#riceProductModal .submit-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Saving...';
    submitBtn.disabled = true;
    
    console.log("Saving product with data:", {
        productId: productId || 'new',
        name: name,
        price: price,
        hasImage: !!imageFile
    });
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Response:", data);
        
        if (data.success) {
            alert(productId ? 'Product updated successfully!' : 'Product added successfully!');
            closeRiceProductModal();
            loadRiceProducts(); // Refresh the table
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving product: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function editRiceProduct(productId) {
    console.log("=== EDIT PRODUCT CALLED ===");
    console.log("Looking for product ID:", productId);
    console.log("Product ID type:", typeof productId);
    console.log("Current products array:", currentRiceProducts);
    
    // Convert productId to number for comparison
    const searchId = parseInt(productId);
    console.log("Searching for ID:", searchId);
    
    // Find the product, comparing as numbers
    const product = currentRiceProducts.find(p => parseInt(p.id) === searchId);
    
    console.log("Found product:", product);
    
    if (product) {
        console.log("Product found! Opening edit modal for:", product.name);
        
        document.getElementById('riceProductModalTitle').innerText = 'Edit Rice Product';
        document.getElementById('editProductId').value = product.id;
        document.getElementById('riceVarietyName').value = product.name;
        document.getElementById('ricePrice').value = product.price;
        
        const preview = document.getElementById('imagePreview');
        if (product.image && product.image !== '') {
            preview.src = '../uploads/' + product.image;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
            preview.src = '';
        }
        
        document.getElementById('riceProductModal').style.display = 'flex';
    } else {
        console.error("Product not found with ID:", productId);
        console.log("Available product IDs:", currentRiceProducts.map(p => ({id: p.id, type: typeof p.id})));
        alert("Error: Product not found. Please refresh the page and try again.");
        loadRiceProducts(); // Reload products
    }
}

function deleteRiceProduct(productId) {
    console.log("=== DELETE PRODUCT CALLED ===");
    console.log("Product ID to delete:", productId);
    
    // Double check with user
    if (!confirm('⚠️ WARNING: Are you sure you want to delete this rice variety?\n\nThis action cannot be undone!\n\nNote: Products already assigned to farmers cannot be deleted.')) {
        return;
    }
    
    // Show loading indicator
    const deleteBtn = event?.target;
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = 'Deleting...';
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    
    console.log("Sending delete request...");
    
    fetch('admin_api/delete_rice_product.php', {
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
            alert('✓ Product deleted successfully!');
            loadRiceProducts(); // Refresh the table
        } else {
            alert('✗ Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Error deleting product: ' + error.message + '\n\nPlease check the console for more details.');
    })
    .finally(() => {
        if (deleteBtn) {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = 'Delete';
        }
    });
}

// Call loadRiceProducts when the page loads
document.addEventListener('DOMContentLoaded', function() {
    loadFarmers();
    loadRiceProducts(); // Add this line
});
</script>

</body>
</html>