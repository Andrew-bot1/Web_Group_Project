<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Store'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Store</h1>
        
    </header>
    <nav>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/../src/home.php">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>/../src/about.php">About</a></li>
            <li><a href="<?php echo BASE_URL; ?>/../src/Item_CRUD.php">Manage Items</a></li>
            <li><a href="<?php echo BASE_URL; ?>/../src/Order_CRUD.php">Manage Orders</a></li>
        </ul>
    </nav>