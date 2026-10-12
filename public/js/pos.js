/**
 * NusaPOS - POS Engine Client Script
 * Mengelola keranjang belanja, kalkulasi PPN & diskon, modal pembayaran, dan cetak struk termal.
 */

let cart = [];
const PPN_RATE = 0.11; // 11%
let discountAmount = 0;
let currentPaymentMethod = 'tunai';
let lastCompletedReceipt = null;

// Audio beep for scanning / adding items
function playBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = 880; // A5 note
    gain.gain.value = 0.1;
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.08);
  } catch (e) {}
}

// 1. Tambah Produk ke Keranjang
function addToCart(product) {
  const existing = cart.find(item => item.id === product.id);
  const currentQtyInCart = existing ? existing.qty : 0;

  if (currentQtyInCart + 1 > product.stok) {
    showToast(`Stok "${product.nama_produk}" tidak mencukupi (sisa ${product.stok})`, 'error');
    return;
  }

  playBeep();

  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({
      id: product.id,
      sku: product.sku,
      barcode: product.barcode,
      nama_produk: product.nama_produk,
      harga_jual: parseFloat(product.harga_jual),
      stok: parseInt(product.stok),
      qty: 1
    });
  }

  renderCart();
  showToast(`"${product.nama_produk}" ditambahkan ke keranjang`, 'success');
}

// 2. Ubah Kuantitas
function updateItemQty(productId, delta) {
  const item = cart.find(i => i.id === productId);
  if (!item) return;

  const newQty = item.qty + delta;
  if (newQty <= 0) {
    removeItem(productId);
    return;
  }

  if (newQty > item.stok) {
    showToast(`Maksimal stok tercapai (${item.stok} unit)`, 'error');
    return;
  }

  item.qty = newQty;
  renderCart();
}

// 3. Hapus Item dari Keranjang
function removeItem(productId) {
  cart = cart.filter(i => i.id !== productId);
  renderCart();
  showToast('Item dihapus dari keranjang', 'info');
}

// 4. Kosongkan Keranjang
function confirmClearCart() {
  if (cart.length === 0) return;
  if (confirm('Kosongkan seluruh barang di keranjang belanja?')) {
    cart = [];
    discountAmount = 0;
    renderCart();
    showToast('Keranjang telah dikosongkan', 'info');
  }
}

// 5. Render Keranjang & Hitung Total
function renderCart() {
  const listEl = document.getElementById('cart-item-list');
  const emptyEl = document.getElementById('cart-empty-state');
  const countEl = document.getElementById('cart-items-count');
  const btnCheckout = document.getElementById('btn-checkout');

  if (cart.length === 0) {
    listEl.innerHTML = '';
    listEl.classList.add('hidden');
    emptyEl.classList.remove('hidden');
    if (countEl) countEl.textContent = '0 Item';
    if (btnCheckout) btnCheckout.disabled = true;

    document.getElementById('cart-subtotal').textContent = 'Rp0';
    document.getElementById('cart-ppn').textContent = 'Rp0';
    document.getElementById('cart-discount').textContent = '-Rp0';
    document.getElementById('cart-total').textContent = 'Rp0';
    return;
  }

  listEl.classList.remove('hidden');
  emptyEl.classList.add('hidden');
  if (btnCheckout) btnCheckout.disabled = false;

  let totalItemsCount = 0;
  let subtotal = 0;

  let html = '';
  cart.forEach(item => {
    const itemSubtotal = item.harga_jual * item.qty;
    subtotal += itemSubtotal;
    totalItemsCount += item.qty;

    html += `
      <div class="cart-row p-space-sm bg-surface-container-low rounded-lg flex items-center justify-between gap-space-sm transition-all border border-outline-variant/30">
        <div class="flex-1 min-w-0">
          <h4 class="font-headline-sm text-sm text-on-surface truncate font-semibold">${item.nama_produk}</h4>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="font-body-sm text-xs text-on-surface-variant font-mono">@${formatRupiah(item.harga_jual)}</span>
            <span class="text-outline text-[12px]">•</span>
            <span class="item-subtotal font-currency-table text-primary font-bold">${formatRupiah(itemSubtotal)}</span>
          </div>
        </div>
        <div class="flex items-center gap-1.5 bg-surface-container-lowest p-1 rounded-md shadow-inner border border-outline-variant/40">
          <button type="button" onclick="updateItemQty(${item.id}, -1)" class="w-7 h-7 rounded flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-on-surface active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[16px]">remove</span>
          </button>
          <span class="item-qty w-6 text-center font-headline-sm text-sm text-on-surface font-bold">${item.qty}</span>
          <button type="button" onclick="updateItemQty(${item.id}, 1)" class="w-7 h-7 rounded flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-on-surface active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[16px]">add</span>
          </button>
        </div>
        <button type="button" onclick="removeItem(${item.id})" title="Hapus Item" class="text-outline hover:text-error p-1 rounded transition-colors">
          <span class="material-symbols-outlined text-[18px]">delete</span>
        </button>
      </div>
    `;
  });

  listEl.innerHTML = html;
  if (countEl) countEl.textContent = `${totalItemsCount} Item`;

  const ppn = Math.round(subtotal * PPN_RATE);
  const total = Math.max(0, subtotal + ppn - discountAmount);

  document.getElementById('cart-subtotal').textContent = formatRupiah(subtotal);
  document.getElementById('cart-ppn').textContent = formatRupiah(ppn);
  document.getElementById('cart-discount').textContent = '-' + formatRupiah(discountAmount);
  document.getElementById('cart-total').textContent = formatRupiah(total);
}

// 6. Filter Kategori Produk di Katalog
function filterCategory(catSlug, btnEl) {
  document.querySelectorAll('.cat-tab').forEach(b => {
    b.className = 'cat-tab px-3.5 py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-colors bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 cursor-pointer';
  });
  if (btnEl) {
    btnEl.className = 'cat-tab px-3.5 py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-colors bg-primary-container text-on-primary font-semibold shadow-sm cursor-pointer';
  }

  const cards = document.querySelectorAll('.product-card');
  cards.forEach(card => {
    const cardCat = card.getAttribute('data-category');
    if (catSlug === 'semua' || cardCat === catSlug) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

// 7. Live Search Produk & Barcode Enter
function initSearch() {
  const searchInput = document.getElementById('search-input');
  if (!searchInput) return;

  function doSearch() {
    const query = searchInput.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    let visibleCount = 0;

    cards.forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const sku = (card.getAttribute('data-sku') || '').toLowerCase();
      const barcode = (card.getAttribute('data-barcode') || '').toLowerCase();

      if (!query || name.includes(query) || sku.includes(query) || barcode.includes(query)) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    const notFoundEl = document.getElementById('catalog-not-found');
    if (notFoundEl) {
      if (visibleCount === 0) {
        notFoundEl.classList.remove('hidden');
      } else {
        notFoundEl.classList.add('hidden');
      }
    }
  }

  searchInput.addEventListener('input', doSearch);

  // Jika scanner barcode menekan tombol Enter
  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const query = searchInput.value.trim();
      if (!query) return;

      // Cari exact barcode match
      const matchedCard = Array.from(document.querySelectorAll('.product-card')).find(card => {
        const barcode = card.getAttribute('data-barcode') || '';
        const sku = card.getAttribute('data-sku') || '';
        return barcode.toLowerCase() === query.toLowerCase() || sku.toLowerCase() === query.toLowerCase();
      });

      if (matchedCard) {
        const prodData = JSON.parse(matchedCard.getAttribute('data-json'));
        addToCart(prodData);
        searchInput.value = '';
        doSearch();
      }
    }
  });
}

// 8. Modal Pembayaran
function openPaymentModal() {
  if (cart.length === 0) {
    showToast('Keranjang masih kosong!', 'error');
    return;
  }

  const subtotal = cart.reduce((acc, item) => acc + (item.harga_jual * item.qty), 0);
  const ppn = Math.round(subtotal * PPN_RATE);
  const total = Math.max(0, subtotal + ppn - discountAmount);

  document.getElementById('modal-pay-subtotal').textContent = formatRupiah(subtotal);
  document.getElementById('modal-pay-ppn').textContent = formatRupiah(ppn);
  document.getElementById('modal-pay-discount').textContent = '-' + formatRupiah(discountAmount);
  document.getElementById('modal-pay-total').textContent = formatRupiah(total);

  // Set nilai awal uang diterima = total (Uang Pas)
  const cashInput = document.getElementById('cash-tendered-input');
  if (cashInput) {
    cashInput.value = total;
  }

  calculateChange();
  selectPaymentMethod('tunai');

  openModal('payment-modal');
}

function closePaymentModal() {
  closeModal('payment-modal');
}

function selectPaymentMethod(method) {
  currentPaymentMethod = method;

  document.querySelectorAll('.pay-method-card').forEach(card => {
    const m = card.getAttribute('data-method');
    if (m === method) {
      card.classList.add('border-primary', 'bg-primary-container/10', 'ring-2', 'ring-primary');
      card.classList.remove('border-outline-variant/40');
    } else {
      card.classList.remove('border-primary', 'bg-primary-container/10', 'ring-2', 'ring-primary');
      card.classList.add('border-outline-variant/40');
    }
  });

  const cashSection = document.getElementById('cash-payment-section');
  const qrisSection = document.getElementById('qris-payment-section');
  const cardSection = document.getElementById('card-payment-section');

  if (cashSection) cashSection.classList.add('hidden');
  if (qrisSection) qrisSection.classList.add('hidden');
  if (cardSection) cardSection.classList.add('hidden');

  if (method === 'tunai' && cashSection) {
    cashSection.classList.remove('hidden');
    calculateChange();
  } else if (method === 'qris' && qrisSection) {
    qrisSection.classList.remove('hidden');
  } else if ((method === 'debit' || method === 'transfer') && cardSection) {
    cardSection.classList.remove('hidden');
  }

  validatePaymentCanProceed();
}

function setQuickCash(amount) {
  const cashInput = document.getElementById('cash-tendered-input');
  if (amount === 'pas') {
    const subtotal = cart.reduce((acc, item) => acc + (item.harga_jual * item.qty), 0);
    const ppn = Math.round(subtotal * PPN_RATE);
    const total = Math.max(0, subtotal + ppn - discountAmount);
    cashInput.value = total;
  } else {
    cashInput.value = amount;
  }
  calculateChange();
}

function calculateChange() {
  const subtotal = cart.reduce((acc, item) => acc + (item.harga_jual * item.qty), 0);
  const ppn = Math.round(subtotal * PPN_RATE);
  const total = Math.max(0, subtotal + ppn - discountAmount);

  const cashInput = document.getElementById('cash-tendered-input');
  const tendered = parseFloat(cashInput.value) || 0;
  const change = tendered - total;

  const changeDisplay = document.getElementById('cash-change-display');
  const changeAlert = document.getElementById('cash-underpaid-alert');

  if (change < 0) {
    if (changeDisplay) changeDisplay.textContent = 'Kurang ' + formatRupiah(Math.abs(change));
    if (changeDisplay) changeDisplay.className = 'font-currency-display text-error font-bold';
    if (changeAlert) changeAlert.classList.remove('hidden');
  } else {
    if (changeDisplay) changeDisplay.textContent = formatRupiah(change);
    if (changeDisplay) changeDisplay.className = 'font-currency-display text-tertiary font-bold';
    if (changeAlert) changeAlert.classList.add('hidden');
  }

  validatePaymentCanProceed();
}

function validatePaymentCanProceed() {
  const btnConfirm = document.getElementById('btn-confirm-payment');
  if (!btnConfirm) return;

  const subtotal = cart.reduce((acc, item) => acc + (item.harga_jual * item.qty), 0);
  const ppn = Math.round(subtotal * PPN_RATE);
  const total = Math.max(0, subtotal + ppn - discountAmount);

  if (currentPaymentMethod === 'tunai') {
    const cashInput = document.getElementById('cash-tendered-input');
    const tendered = parseFloat(cashInput.value) || 0;
    btnConfirm.disabled = tendered < total;
    if (tendered < total) {
      btnConfirm.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
      btnConfirm.classList.remove('opacity-50', 'cursor-not-allowed');
    }
  } else {
    btnConfirm.disabled = false;
    btnConfirm.classList.remove('opacity-50', 'cursor-not-allowed');
  }
}

// 9. Konfirmasi Pembayaran & Simpan ke Backend (AJAX)
async function confirmPayment() {
  if (cart.length === 0) return;

  const subtotal = cart.reduce((acc, item) => acc + (item.harga_jual * item.qty), 0);
  const ppn = Math.round(subtotal * PPN_RATE);
  const total = Math.max(0, subtotal + ppn - discountAmount);

  let nominalBayar = total;
  if (currentPaymentMethod === 'tunai') {
    const cashInput = document.getElementById('cash-tendered-input');
    nominalBayar = parseFloat(cashInput.value) || total;
    if (nominalBayar < total) {
      showToast('Nominal uang tunai kurang dari total tagihan!', 'error');
      return;
    }
  }

  const customerName = document.getElementById('customer-name-input')?.value || 'Pelanggan Umum (Walk-In)';
  const btnConfirm = document.getElementById('btn-confirm-payment');
  const originalBtnText = btnConfirm.innerHTML;
  btnConfirm.disabled = true;
  btnConfirm.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span> Memproses...';

  try {
    const response = await fetch('api/checkout.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        pelanggan: customerName,
        metode_pembayaran: currentPaymentMethod,
        nominal_bayar: nominalBayar,
        diskon: discountAmount,
        items: cart.map(i => ({
          produk_id: i.id,
          jumlah: i.qty,
          harga_satuan: i.harga_jual
        }))
      })
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Gagal memproses transaksi.');
    }

    // Sukses: Tutup modal pembayaran, buka modal sukses
    closePaymentModal();
    lastCompletedReceipt = result.receipt;
    showSuccessModal(result.receipt);

    // Reset keranjang
    cart = [];
    discountAmount = 0;
    renderCart();

    // Perbarui stok produk yang ada di antarmuka katalog secara lokal
    if (result.updated_stocks) {
      result.updated_stocks.forEach(s => {
        const card = document.querySelector(`.product-card[data-id="${s.id}"]`);
        if (card) {
          card.setAttribute('data-stok', s.stok);
          const json = JSON.parse(card.getAttribute('data-json'));
          json.stok = s.stok;
          card.setAttribute('data-json', JSON.stringify(json));
          const stockBadge = card.querySelector('.stock-badge');
          if (stockBadge) {
            stockBadge.textContent = `Stok: ${s.stok} pcs`;
            if (s.stok <= 0) {
              stockBadge.className = 'stock-badge bg-error-container text-error px-2 py-0.5 rounded-full font-label-sm text-label-sm font-semibold';
              const addBtn = card.querySelector('.btn-add-cart');
              if (addBtn) addBtn.disabled = true;
            }
          }
        }
      });
    }

    showToast('Transaksi berhasil disimpan ke database!', 'success');

  } catch (error) {
    showToast(error.message, 'error');
  } finally {
    btnConfirm.disabled = false;
    btnConfirm.innerHTML = originalBtnText;
  }
}

// 10. Tampilkan Modal Sukses & Pratinjau Struk
function showSuccessModal(receipt) {
  document.getElementById('receipt-invoice-no').textContent = receipt.no_invoice;
  document.getElementById('receipt-time').textContent = receipt.tanggal;
  document.getElementById('receipt-method').textContent = receipt.metode_pembayaran.toUpperCase();
  document.getElementById('receipt-total').textContent = formatRupiah(receipt.total_akhir);
  document.getElementById('receipt-paid').textContent = formatRupiah(receipt.nominal_bayar);
  document.getElementById('receipt-change').textContent = formatRupiah(receipt.kembalian);

  // Thermal Slip Preview
  document.getElementById('slip-toko').textContent = receipt.nama_toko;
  document.getElementById('slip-cabang').textContent = receipt.cabang;
  document.getElementById('slip-alamat').textContent = receipt.alamat || '';
  document.getElementById('slip-invoice').textContent = receipt.no_invoice;
  document.getElementById('slip-date').textContent = receipt.tanggal;
  document.getElementById('slip-kasir').textContent = receipt.kasir;

  let itemsHtml = '';
  receipt.items.forEach(item => {
    itemsHtml += `
      <div class="mb-1 text-[11px]">
        <div class="font-bold text-neutral-900">${item.nama_produk}</div>
        <div class="flex justify-between text-neutral-600">
          <span>${item.jumlah} x ${new Intl.NumberFormat('id-ID').format(item.harga_satuan)}</span>
          <span>${new Intl.NumberFormat('id-ID').format(item.subtotal)}</span>
        </div>
      </div>
    `;
  });
  document.getElementById('slip-items').innerHTML = itemsHtml;

  document.getElementById('slip-subtotal').textContent = new Intl.NumberFormat('id-ID').format(receipt.subtotal);
  document.getElementById('slip-ppn').textContent = new Intl.NumberFormat('id-ID').format(receipt.pajak_ppn);
  document.getElementById('slip-diskon').textContent = '-' + new Intl.NumberFormat('id-ID').format(receipt.diskon);
  document.getElementById('slip-total').textContent = formatRupiah(receipt.total_akhir);
  document.getElementById('slip-paid').textContent = new Intl.NumberFormat('id-ID').format(receipt.nominal_bayar);
  document.getElementById('slip-change').textContent = new Intl.NumberFormat('id-ID').format(receipt.kembalian);

  openModal('success-modal');
}

// 11. Cetak Struk (Thermal 80mm Print)
function printReceipt() {
  window.print();
}

function resetOrder() {
  closeModal('success-modal');
  lastCompletedReceipt = null;
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.focus();
  }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
  if (e.key === 'F1') {
    e.preventDefault();
    document.getElementById('search-input')?.focus();
  } else if (e.key === 'F2') {
    e.preventDefault();
    openPaymentModal();
  } else if (e.key === 'F9') {
    e.preventDefault();
    const successModal = document.getElementById('success-modal');
    if (successModal && !successModal.classList.contains('hidden')) {
      printReceipt();
    }
  } else if (e.key === 'Escape') {
    const paymentModal = document.getElementById('payment-modal');
    const successModal = document.getElementById('success-modal');
    if (paymentModal && !paymentModal.classList.contains('hidden')) {
      closePaymentModal();
    } else if (successModal && !successModal.classList.contains('hidden')) {
      resetOrder();
    }
  }
});

// Inisialisasi awal saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
  initSearch();
  renderCart();
});
