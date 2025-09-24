document.addEventListener('DOMContentLoaded', () => {
  let products = JSON.parse(localStorage.getItem('products')) || [
    { name: 'Sample Product A', quantity: 5, price: 120 },
    { name: 'Sample Product B', quantity: 3, price: 150 }
  ];
  let cart = JSON.parse(localStorage.getItem('cart')) || [];

  // Render products on home page
  const productTable = document.querySelector('table tbody');
  if (productTable && document.title.includes('Home')) {
    productTable.innerHTML = '';
    products.forEach((p, index) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><img src="product${index+1}.jpg" alt="${p.name}"></td>
        <td>${p.name}</td>
        <td>${p.quantity}</td>
        <td>$${p.price}</td>
        <td><button class="add-to-cart" data-name="${p.name}" data-price="${p.price}">Add</button></td>`;
      productTable.appendChild(row);
    });

    document.querySelectorAll('.add-to-cart').forEach(button => {
      button.addEventListener('click', () => {
        const name = button.getAttribute('data-name');
        const price = parseFloat(button.getAttribute('data-price'));
        cart.push({ name, price });
        localStorage.setItem('cart', JSON.stringify(cart));
        alert(name + ' added to cart!');
      });
    });
  }

  // Render cart page
  const cartTable = document.querySelector('#cart-table tbody');
  if (cartTable) {
    const totalEl = document.getElementById('total');
    let total = 0;
    cartTable.innerHTML = '';
    cart.forEach((item, index) => {
      const row = document.createElement('tr');
      row.innerHTML = `<td>${item.name}</td><td>$${item.price}</td><td><button data-index="${index}" class="remove">Remove</button></td>`;
      cartTable.appendChild(row);
      total += item.price;
    });
    totalEl.textContent = 'Total: $' + total;

    document.querySelectorAll('.remove').forEach(btn => {
      btn.addEventListener('click', () => {
        const i = btn.getAttribute('data-index');
        cart.splice(i, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        location.reload();
      });
    });

    document.getElementById('checkout').addEventListener('click', () => {
      document.getElementById('message').textContent = 'Thank you! Your items are on the way.';
      localStorage.removeItem('cart');
    });
  }

  // Admin login
  const loginBtn = document.getElementById('login-btn');
  if (loginBtn) {
    loginBtn.addEventListener('click', () => {
      const pw = document.getElementById('admin-password').value;
      if (pw === 'password123') {
        document.getElementById('login-section').style.display = 'none';
        document.getElementById('admin-tools').style.display = 'block';
        renderAdminTable();
      } else {
        document.getElementById('login-message').textContent = 'Incorrect password';
      }
    });
  }

  // Render admin table
  function renderAdminTable() {
    const adminTable = document.querySelector('#admin-table tbody');
    if (!adminTable) return;
    adminTable.innerHTML = '';
    products.forEach((p, index) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td contenteditable="true" class="editable" data-field="name">${p.name}</td>
        <td contenteditable="true" class="editable" data-field="quantity">${p.quantity}</td>
        <td contenteditable="true" class="editable" data-field="price">${p.price}</td>
        <td><button class="delete" data-index="${index}">Delete</button></td>`;
      adminTable.appendChild(row);
    });

    document.querySelectorAll('.delete').forEach(btn => {
      btn.addEventListener('click', () => {
        const i = btn.getAttribute('data-index');
        products.splice(i, 1);
        localStorage.setItem('products', JSON.stringify(products));
        renderAdminTable();
      });
    });

    document.querySelectorAll('.editable').forEach(cell => {
      cell.addEventListener('blur', () => {
        const index = Array.from(cell.parentNode.parentNode.children).indexOf(cell.parentNode);
        const field = cell.getAttribute('data-field');
        products[index][field] = field === 'price' || field === 'quantity' ? parseFloat(cell.textContent) : cell.textContent;
        localStorage.setItem('products', JSON.stringify(products));
      });
    });
  }
});