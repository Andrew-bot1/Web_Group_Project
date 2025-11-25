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

$itemsDB = new ItemsDB($pdo);

// ----------------------------
// ADD (CREATE) AN ITEM
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert'])) {

    // Get user input and clean it
    $name = clean('name');
    $price = clean('price','float');

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a positive number.";
    }

    // If no errors, insert the record
    if (empty($errors)) {
		try {
			$itemsDB->createItem($name, $price);
			$_SESSION['success_message'] = "Item created successfully";
		} catch (PDOException $e) {
			//log details internaly, but show generic message to user
			error_log("Database error in createItem: ". $e->getMessage());
			$_SESSION['error_message'] = "Error: Unable to create item due to a database error.";
		} catch (Exception $e) {
			error_log("General error in createItem: ". $e->getMessage());
			$_SESSION['error_message'] = "Error: Unable to create item - " . $e->getMessage();
		}
	} else {
		// Store validation errors in the session
			$_SESSION['error_message'] = implode("<br>", $errors); //implode joins array elements into a string. Used for display
	}
   

    // Redirect to avoid duplicate form submissions
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------
// UPDATE AN ITEM
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = clean('item_id','int');
    $name = clean('name');
    $price = clean('price', 'float');

    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a positive number.";
    }

    if (empty($errors)) {

		try {
			$itemsDB->updateItem($id, $name, $price);
			$_SESSION['success_message'] = "Item updated successfully";
		} catch (PDOException $e) {
			error_log("Database error in updateItem: ". $e->getMessage());
			$_SESSION['error_message'] = "Error: Unable to update item due to a database error.";
		} catch (Exception $e) {
			error_log("General error in updateItem: ". $e->getMessage());
			$_SESSION['error_message'] = "Error: Unable to update item - " . $e->getMessage();
		}
	} else {
		// Store validation errors in the session
		$_SESSION['error_message'] = implode("<br>", $errors); //implode joins array elements into a string. Used for display
	}

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------
// DELETE AN ITEM
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = clean('item_id','int');
    $errors = [];
    try{
       $itemsDB->deleteItem($id);
       $_SESSION['success_message'] = "Item deleted successfully";
    } catch (PDOException $e) {
       if ($e->getCode() == '23000') {
		   error_log("Integrity constraint violation (23000) in deleteItem: " . $e->getMessage());
          $_SESSION['error_message'] = "Error: Cannot delete item because it is part of an existing order.";
       } else {
		   error_log("Database error in deleteItem: " . $e->getMessage());
          $_SESSION['error_message'] = "Error: Unable to delete item due to a database error.";
      }
    } catch (Exception $e) {
		  error_log("General error in deleteItem: " . $e->getMessage());
          $_SESSION['error_message'] = "Error: Unable to delete item - " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------
// DISPLAY ALL ITEMS
// ----------------------------
 $items = $itemsDB->getAllItems();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Item Management</title>
</head>
<body>
    <h2>Item Management</h2>

    <!-- Display error or success messages -->
    <?php 
    if (isset($_SESSION['error_message'])) { 
        echo "<p style='color:red'>" . $_SESSION['error_message'] . "</p>";
        unset($_SESSION['error_message']);
    } 

    if (isset($_SESSION['success_message'])) { 
        echo "<p style='color:green'>" . $_SESSION['success_message'] . "</p>";
        unset($_SESSION['success_message']); 
    } 
    ?>

    <!-- ADD NEW ITEM FORM -->
    <form method="post">
        <label>Name:</label>
        <input type="text" name="name" >

        <label>Price:</label>
        <input type="number" step="0.01" name="price">

        <button type="submit" name="insert">Add Item</button>
    </form>

    <h3>Items List</h3>
    <table border="1" cellpadding="5">
        <tr><th>Name</th><th>Price</th><th>Action</th></tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= esc($item['name']) ?></td>
                <td><?= esc($item['price']) ?></td>
                <td>
                    <!-- UPDATE/DELETE FORM for each row -->
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?= esc($item['item_id']) ?>">
                        <input type="text" name="name" value="<?= esc($item['name']) ?>">
                        <input type="number" step="0.01" name="price" value="<?= esc($item['price']) ?>">
                        <button type="submit" name="update">Update</button>
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
