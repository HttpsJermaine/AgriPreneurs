<style>
.modal-backdrop{
  display:none;
  position:fixed; inset:0;
  background:rgba(0,0,0,.45);
  justify-content:center; align-items:center;
  z-index:9999;
}
.modal-box{
  width:420px; max-width:92vw;
  background:#fff;
  border-radius:14px;
  padding:18px;
  box-shadow:0 20px 60px rgba(0,0,0,.25);
}
.modal-box h3{ margin:0 0 10px; }
.modal-box label{ display:block; margin-top:10px; font-weight:600; }
.modal-box input{
  width:100%;
  padding:10px;
  margin-top:6px;
  border:1px solid #ccc;
  border-radius:10px;
}
.modal-actions{
  margin-top:14px;
  display:flex;
  gap:10px;
  justify-content:flex-end;
}
.btn-close{
  background:#777;color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer;
}
.btn-green{
  background:#2eaf5d;color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer;
}
.btn-blue{
  background:#1d4ed8;color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer;
}
.small-note{ margin-top:6px;color:#666;font-size:13px; }
</style>

<!-- ADD TO CART MODAL -->
<div id="cartModal" class="modal-backdrop" onclick="closeModal(event,'cartModal')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3>Add to Cart</h3>

    <form method="POST" action="cart_add.php">
      <input type="hidden" name="product_id" id="cart_product_id">

      <label>Variety</label>
      <input type="text" id="cart_variety" disabled>

      <label>How many sacks?</label>
      <input type="number" name="qty" id="cart_qty" min="1" required>

      <div id="cart_stock" class="small-note"></div>

      <div class="modal-actions">
        <button type="button" class="btn-close" onclick="hide('cartModal')">Close</button>
        <button type="submit" class="btn-green">Add to Cart</button>
      </div>
    </form>
  </div>
</div>

<!-- BUY NOW MODAL -->
<div id="buyModal" class="modal-backdrop" onclick="closeModal(event,'buyModal')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3>Buy Now</h3>

    <form method="POST" action="buy_now_start.php">
      <input type="hidden" name="product_id" id="buy_product_id">

      <label>Variety</label>
      <input type="text" id="buy_variety" disabled>

      <label>How many sacks?</label>
      <input type="number" name="qty" id="buy_qty" min="1" required>

      <div id="buy_stock" class="small-note"></div>

      <div class="modal-actions">
        <button type="button" class="btn-close" onclick="hide('buyModal')">Close</button>
        <button type="submit" class="btn-blue">Proceed</button>
      </div>
    </form>
  </div>
</div>

<script>
function show(id){ document.getElementById(id).style.display='flex'; }
function hide(id){ document.getElementById(id).style.display='none'; }
function closeModal(e,id){ if(e.target.id===id) hide(id); }

function openCartModal(productId, variety, unit, maxStock){
  document.getElementById('cart_product_id').value = productId;
  document.getElementById('cart_variety').value = variety;

  const qty = document.getElementById('cart_qty');
  qty.value = 1;
  qty.max = maxStock;

  document.getElementById('cart_stock').innerText = `Available: ${maxStock} ${unit}(s)`;
  show('cartModal');
}

function openBuyModal(productId, variety, unit, maxStock){
  document.getElementById('buy_product_id').value = productId;
  document.getElementById('buy_variety').value = variety;

  const qty = document.getElementById('buy_qty');
  qty.value = 1;
  qty.max = maxStock;

  document.getElementById('buy_stock').innerText = `Available: ${maxStock} ${unit}(s)`;
  show('buyModal');
}
</script>