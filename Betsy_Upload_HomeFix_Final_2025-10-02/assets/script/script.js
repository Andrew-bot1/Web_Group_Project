
/**
 * Betsy Store - Single source of truth for product data and page behavior.
 * This script:
 *  - Defines a global CATALOG (window.CATALOG) with consistent ids/images.
 *  - Syncs localStorage 'products' and 'cart'.
 *  - Renders the Product Detail page based on the URL ?id= param.
 *  - Renders Similar Products (same category).
 *  - Provides Add to Cart and Cart page rendering.
 */

(function () {
  // ====== PRODUCT CATALOG ======
  const CATALOG = [
    { id: 1, name: 'Action Figure', price: 120, quantity: 5, category: 'Collectibles', img: 'assets/img/bethammer.png', description: 'A detailed action figure of the hero Betsy with a mighty hammer.' },
    { id: 2, name: 'Makeup',        price: 150, quantity: 3, category: 'Beauty',       img: 'assets/img/makeup.png',    description: 'A premium set of Betsy-themed makeup for a stunning look.' },
    { id: 3, name: 'Necklace',      price: 45,  quantity: 10, category: 'Jewelry',     img: 'assets/img/necklace.png',  description: 'An elegant necklace featuring the Betsy logo charm.' },
    { id: 4, name: 'Video Game',    price: 80,  quantity: 7, category: 'Games',        img: 'assets/img/betsyZelda.png',description: 'Join Betsy in a new fantasy adventure game.' },
    { id: 5, name: 'Movie',         price: 60,  quantity: 12, category: 'Movies',      img: 'assets/img/betsyMovie.png',description: 'The critically acclaimed feature film starring Betsy.' },
    { id: 6, name: 'Shoes',         price: 300, quantity: 2, category: 'Apparel',      img: 'assets/img/betsyNike.png', description: 'Limited-edition sneakers, designed for comfort and style.' },
  ];
  window.CATALOG = CATALOG;

  // ====== STORAGE SCHEMA VERSION (one-time reset to avoid legacy junk in cart) ======
  const STORAGE_VERSION = '2025-10-02-2';
  (function ensureSchema() {
    const current = localStorage.getItem('betsy_schema_version');
    if (current !== STORAGE_VERSION) {
      // Reset cart to empty; re-seed products from catalog so quantities are predictable.
      localStorage.setItem('cart', JSON.stringify([]));
      // Do NOT overwrite products; preserve user uploads.
      localStorage.setItem('betsy_schema_version', STORAGE_VERSION);
    }
  })();

  // ====== STORAGE HELPERS ======
  function getProducts() {
    let stored = [];
    try { stored = JSON.parse(localStorage.getItem('products') || '[]'); } catch(e) {}
    if (!Array.isArray(stored)) stored = [];

    // If nothing stored, seed with catalog
    if (stored.length === 0) {
      localStorage.setItem('products', JSON.stringify(CATALOG));
      return CATALOG.slice();
    }

    // Normalize stored and merge with catalog defaults when ids match.
    const byIdCatalog = new Map(CATALOG.map(p => [p.id, p]));
    const normalized = stored.map((s, idx) => {
      const cat = byIdCatalog.get(Number(s.id));
      return {
        id: Number(s.id) || (1000 + idx), // ensure numeric id
        name: (s.name || (cat && cat.name) || 'Untitled').toString(),
        price: Number(s.price ?? (cat && cat.price) ?? 0),
        quantity: Number.isFinite(Number(s.quantity)) ? Number(s.quantity) : Number((cat && cat.quantity) ?? 0),
        category: (s.category || (cat && cat.category) || 'Misc').toString(),
        img: (s.img || (cat && cat.img) || 'assets/img/product1.jpg').toString(),
        description: (s.description || (cat && cat.description) || '').toString(),
      };
    });

    // Add any catalog items missing from storage (so originals still appear)
    const have = new Set(normalized.map(p => p.id));
    const missing = CATALOG.filter(c => !have.has(c.id));

    const finalList = normalized.concat(missing);
    localStorage.setItem('products', JSON.stringify(finalList));
    return finalList;
  }

  function setProducts(arr) {
    localStorage.setItem('products', JSON.stringify(arr));
  }

  function getCart() {
    let arr;
    try { arr = JSON.parse(localStorage.getItem('cart') || '[]'); } catch(e) { arr = []; }
    if (!Array.isArray(arr)) arr = [];
    // sanitize entries
    arr = arr.filter(x => x && typeof x === 'object')
             .map(x => ({
               id: Number(x.id)||0,
               name: String(x.name||''),
               price: Number(x.price)||0,
               img: String(x.img||''),
               qty: Math.max(1, Number(x.qty)||1)
             }))
             .filter(x => x.id>0 && x.name);
    return arr;
  }
  function setCart(arr) {
    localStorage.setItem('cart', JSON.stringify(arr));
  }

  // Compute correct path prefix for images when inside /pages/
  function pathPrefix() {
    return (location.pathname.replace(/\\/g, '/').includes('/pages/')) ? '../' : '';
  }

  function isAbsoluteOrData(url) {
    if (!url) return false;
    return /^data:|^https?:\/\//i.test(url) || url.startsWith('/');
  }

  function resolveImg(src) {
    const s = String(src || '');
    if (isAbsoluteOrData(s)) return s;
    return pathPrefix() + s;
  }


  // ====== PRODUCT DETAIL PAGE ======
  function renderProductPage() {
    const detailEl = document.getElementById('product-detail');
    if (!detailEl) return; // Not on product page

    const products = getProducts();
    const params = new URLSearchParams(location.search);
    const id = parseInt(params.get('id'), 10) || products[0]?.id || 1;
    const p = products.find(x => x.id === id) || products[0];

    const imgSrc = resolveImg(p.img);
    detailEl.innerHTML = `
      <div class="product-detail-card">
        <img src="${imgSrc}" alt="${p.name}" onerror="this.style.opacity=0.25">
        <div class="info">
          <h2>${p.name}</h2>
          <p class="category">Category: ${p.category}</p>
          <p>${p.description}</p>
          <p class="price">Price: $${p.price}</p>
          <p class="qty">In Stock: ${p.quantity}</p>
          <button class="add-to-cart" data-id="${p.id}">Add to Cart</button>
        </div>
      </div>
    `;

    // Hook up Add to Cart
    const btn = detailEl.querySelector('.add-to-cart');
    btn?.addEventListener('click', function() {
      let products = getProducts();
      const product = products.find(x => x.id === p.id);
      if (!product || product.quantity <= 0) {
        alert('Out of stock.');
        return;
      }
      // decrement inventory
      product.quantity -= 1;
      setProducts(products);

      // add to cart (group by id)
      const cart = getCart();
      const line = cart.find(x => x.id === p.id);
      if (line) { line.qty += 1; }
      else {
        cart.push({ id: p.id, name: p.name, price: p.price, img: p.img, qty: 1 });
      }
      setCart(cart);

      // Update UI
      const qtyEl = detailEl.querySelector('.qty');
      if (qtyEl) qtyEl.textContent = 'In Stock: ' + product.quantity;

      const msg = document.getElementById('message');
      if (msg) { msg.textContent = 'Added to cart!'; }

      // If cart table is present (when viewing in split panes), re-render it
      renderCartPage(); 
    });

    // Render similar products (same category, excluding self)
    renderSimilarProducts(p);
  }

  function renderSimilarProducts(current) {
    const wrap = document.getElementById('similar-products');
    if (!wrap) return;

    const products = getProducts();
    const sim = products.filter(x => x.category === current.category && x.id !== current.id);
    wrap.innerHTML = '';
    if (sim.length === 0) {
      wrap.innerHTML = '<p>No similar products.</p>';
      return;
    }
    sim.forEach(s => {
      const a = document.createElement('a');
      a.className = 'product-card';
      a.href = 'product.html?id=' + s.id; // relative to /pages/
      a.innerHTML = `
        <img src="${resolveImg(s.img)}" alt="${s.name}" onerror="this.style.opacity=0.25">
        <div class="product-name">${s.name}</div>
      `;
      wrap.appendChild(a);
    });
  }

  
  // ====== HOME PAGE RENDERER ======
  function renderHomePage() {
    const grid = document.getElementById('home-grid');
    if (!grid) return;
    const products = getProducts();

    grid.innerHTML = '';
    products.forEach(p => {
      const a = document.createElement('a');
      a.className = 'product-card';
      // Index is at root, product page lives in /pages/
      a.href = 'pages/product.html?id=' + p.id;
      a.innerHTML = `
        <img src="${resolveImg(p.img)}" alt="${p.name}" onerror="this.style.opacity=0.25">
        <div class="product-name">${p.name}</div>
      `;
      grid.appendChild(a);
    });
  }

  // ====== CART PAGE ======
  function renderCartPage() {
    const cartTable = document.querySelector('#cart, table#cart, .cart table, table');
    const tbody = cartTable ? cartTable.querySelector('tbody') : null;
    const totalEl = document.getElementById('total');
    const checkoutBtn = document.getElementById('checkout');

    // Only proceed if the cart layout exists on this page
    if (!tbody || !totalEl || !checkoutBtn) return;

    const products = getProducts();
    const cart = getCart();

    // Clean up out-of-sync items (e.g., price/name updates)
    cart.forEach(line => {
      const p = products.find(x => x.id === line.id);
      if (p) {
        line.name = p.name;
        line.price = p.price;
        line.img = p.img;
      }
    });
    setCart(cart);

    // Render rows
    tbody.innerHTML = '';
    let total = 0;
    cart.forEach(line => {
      const row = document.createElement('tr');
      const lineTotal = line.price * line.qty;
      total += lineTotal;

      row.innerHTML = `
        <td>${line.name}</td>
        <td>$${line.price}</td>
        <td>${line.qty}</td>
        <td>$${lineTotal}</td>
      `;
      tbody.appendChild(row);
    });
    totalEl.textContent = 'Total: $' + total;

    // Checkout handler
    checkoutBtn.onclick = function () {
      if (cart.length === 0) return;

      // For demo: simply clear the cart.
      setCart([]);
      tbody.innerHTML = '';
      totalEl.textContent = 'Total: $0';
      const msg = document.getElementById('message');
      if (msg) msg.textContent = 'Thank you for your purchase!';
    };
  }

  
  // ====== UPLOAD PAGE HANDLER ======
  function handleUploadPage() {
    const form = document.getElementById('upload-form');
    if (!form) return;

    const nameEl = document.getElementById('u-name');
    const priceEl = document.getElementById('u-price');
    const qtyEl = document.getElementById('u-qty');
    const catEl = document.getElementById('u-cat');
    const urlEl = document.getElementById('u-img-url');
    const fileEl = document.getElementById('u-img-file');
    const descEl = document.getElementById('u-desc');
    const msgEl = document.getElementById('u-msg');

    function nextId(list) {
      return list.reduce((m, x) => Math.max(m, Number(x.id)||0), 0) + 1;
    }

    function finalizeSave(imageSrc) {
      let products = getProducts();
      const id = nextId(products);
      const p = {
        id,
        name: (nameEl.value || '').trim(),
        price: Number(priceEl.value || 0),
        quantity: Math.max(0, parseInt(qtyEl.value || '0', 10)),
        category: (catEl.value || 'Misc'),
        img: imageSrc || 'assets/img/product1.jpg',
        description: (descEl.value || '').trim() || 'No description provided.'
      };
      // Put newest first so it appears immediately on home
      products.unshift(p);
      setProducts(products);

      if (msgEl) msgEl.textContent = 'Uploaded! Redirecting...';
      // Redirect to home to show the new product
      setTimeout(() => { window.location.href = '../index.html'; }, 600);
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!nameEl.value.trim()) {
        if (msgEl) msgEl.textContent = 'Name is required.';
        return;
      }

      const file = (fileEl && fileEl.files && fileEl.files[0]) ? fileEl.files[0] : null;
      const url = (urlEl.value || '').trim();

      if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
          finalizeSave(ev.target.result); // data URL
        };
        reader.onerror = function() {
          finalizeSave(url || 'assets/img/product1.jpg');
        };
        reader.readAsDataURL(file);
      } else {
        finalizeSave(url || 'assets/img/product1.jpg');
      }
    });
  }

  // ====== INIT ======
  document.addEventListener('DOMContentLoaded', function () {
    renderHomePage();
    handleUploadPage();
    // Ensure products exist in storage
    getProducts();

    // Render feature pages if present
    renderProductPage();
    renderCartPage();
  });

  // Expose for debugging
  window.BetsyStore = {
    getProducts, setProducts, getCart, setCart,
    renderProductPage, renderCartPage
  };
})();
