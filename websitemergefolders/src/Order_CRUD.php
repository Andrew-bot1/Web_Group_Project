<?php
//create errorlog
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/logs/app_errors.log');

require_once "helper.php";
// ----------------------------
// Database connection settings
// ----------------------------
$config = require_once 'DBConfig.php';
require_once 'Database.php';
require_once 'OrdersDB.php';
require_once 'ItemsDB.php';

// Start the session to store messages between requests
session_start();



// ----------------------------
// CONNECT TO DATABASE
// ----------------------------
try {
    $database = new Database($config);
    $pdo = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

$ordersDB = new OrdersDB($pdo);
$itemDB = new ItemsDB($pdo);

// ----------------------------
// Create an Order
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $customer_name = clean('customer_name');
    $item_id = clean('item_id');
    $quantity = clean('quantity','float');

    $errors = [];

    // --- Validation ---
    if (empty($customer_name)) {
        $errors[] = "Customer name is required.";
    }
    if (empty($item_id) || !is_numeric($item_id) || $item_id <= 0) {
        $errors[] = "Please select a valid item.";
    }
    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        $errors[] = "Quantity must be a positive number.";
    }

    if (empty($errors)) {
            try {
                $ordersDB->createOrder($customer_name, $item_id, $quantity);
                $_SESSION['customer_success'] = "Order created successfully";
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
					error_log("Integrity constraint violation (23000) in createOrder: " . $e->getMessage());
                     $_SESSION['customer_error'] = "Error: Unable to create order - Invalid item selection.";
                } else {
					error_log("Database error in createOrder: ". $e->getMessage());
                    $_SESSION['customer_error'] = "Error: Unable to create order due to a database error.";
                }
            } catch (Exception $e) {
				error_log("General error in createOrder: ". $e->getMessage());
                $_SESSION['customer_error'] = "Error: Unable to create order - " . $e->getMessage();
            }		
		} else {
			$_SESSION['customer_error'] = implode("<br>", $errors); //implode joins array elements into a string. Used for display
		}

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------
// Update an Order
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $order_id = clean('order_id');
    $quantity = clean('quantity', 'float');

    $errors = [];

    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        $errors[] = "Quantity must be a positive number.";
    }

	if (empty($errors)) {
		try {
			$ordersDB->updateOrder($order_id, $quantity);
			$_SESSION['order_success'] = "Order updated successfully";
	} catch (PDOException $e) {
			error_log("Database error in updateOrder: ". $e->getMessage());
			$_SESSION['order_error'] = "Error: Unable to update order due to a database error.";
	} catch (Exception $e) {
			error_log("General error in updateOrder: ". $e->getMessage());		
			$_SESSION['order_error'] = "Error: Unable to update order - " . $e->getMessage();
		}
	} else {
			$_SESSION['order_error'] = implode("<br>", $errors);
	}


    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------
// Delete an Order
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $order_id = clean('order_id');
    $errors = [];
    try{
       $ordersDB->deleteOrder($order_id);
       $_SESSION['order_success'] = "Order deleted successfully";
    } catch (PDOException $e) {
	   error_log("Database error in deleteOrder: " . $e->getMessage());
       $_SESSION['order_error'] = "Error: Unable to delete order due to a database error.";
    } catch (Exception $e) {
	   error_log("General error in deleteOrder: " . $e->getMessage());		
       $_SESSION['order_error'] = "Error: Unable to delete order - " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------
// Search Handling
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $_SESSION['search_name'] = clean('search_name' ?? '');
    $_SESSION['search_item'] = clean('search_item' ?? '');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear'])) {
    unset($_SESSION['search_name']);
    unset($_SESSION['search_item']);
}

// ----------------------------
// Build Query (Search or All)
// ----------------------------
if (!empty($_SESSION['search_name'])) {
    $orders =  $ordersDB->getCustomers($_SESSION['search_name']);
} else {
  if (!empty($_SESSION['search_item'])) {
       $orders =  $ordersDB->getItems($_SESSION['search_item']);
  } else {
       $orders = $ordersDB->getAllOrders();
  }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Management</title>
</head>
<body>
<!-- Create Order Form -->
<h2>Create Order</h2>
<?php 
if (isset($_SESSION['customer_error'])) { 
    echo "<p style='color:red'>" . $_SESSION['customer_error'] . "</p>";
    unset($_SESSION['customer_error']);
} 
if (isset($_SESSION['customer_success'])) { 
    echo "<p style='color:green'>" . $_SESSION['customer_success'] . "</p>";
    unset($_SESSION['customer_success']); 
} 
?>

<form method="post">
    <input type="text" name="customer_name" placeholder="Customer Name" required><br>

    <select name="item_id" required>
        <option value="">Select Item</option>
        <?php
        $items = $itemDB->getAllItems();
        foreach ($items as $item) {
            $item_id = esc($item['item_id']);
            $name = esc($item['name']);
            $price = esc($item['price']);
            echo "<option value='$item_id'>$name - $price</option>";
        }
        ?>
    </select><br>

    <input type="number" name="quantity" placeholder="Quantity" required><br>
    <button type="submit" name="create">Create Order</button>
</form>

<hr>

<!-- List Orders -->
<h2>Orders</h2>
<?php 
if (isset($_SESSION['order_error'])) { 
    echo "<p style='color:red'>" . $_SESSION['order_error'] . "</p>";
    unset($_SESSION['order_error']);
} 
if (isset($_SESSION['order_success'])) { 
    echo "<p style='color:green'>" . $_SESSION['order_success'] . "</p>";
    unset($_SESSION['order_success']); 
} 
?>

<h3>Search</h3>
<form method="post">
    <label for="search_name">Name:</label>
    <input type="text" id="search_name" name="search_name">
    <br><br>
    <label for="search_item">Item:</label>
    <select name="search_item">
        <option value="">Select Item</option>
        <?php
        $items = $itemDB->getAllItems();
        foreach ($items as $item) {
            $item_id = esc($item['item_id']);
            $name = esc($item['name']);
            $selected = (isset($_SESSION['search_item']) && $item_id == $_SESSION['search_item']) ? 'selected' : '';
            echo "<option value='$item_id' $selected>$name</option>";
        }
        ?>
    </select>
    <p>
        <button type="submit" name="search">Search</button>
        <button type="submit" name="clear">Clear Search</button>
    </p>
</form>

<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= esc($order['order_id']) ?></td>
            <td><?= esc($order['customer_name']) ?></td>
            <td><?= esc($order['item_name']) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="order_id" value="<?= esc($order['order_id']) ?>">
                    <input type="number" name="quantity" value="<?= esc($order['quantity']) ?>" required>
                    <button type="submit" name="update">Update</button>
                </form>
            </td>
            <td><?= esc(number_format($order['quantity'] * $order['price'], 2)) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="order_id" value="<?= esc($order['order_id']) ?>">
                    <button type="submit" name="delete">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
