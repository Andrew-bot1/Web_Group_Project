<?php
//home.php -- static page for example
require_once '../config/config.php';  //get the BASE_PATH and BASE_URL

$page_title = "Home";
require_once BASE_PATH . '/src/includes/header.php';
?>
    <main>
        <h2>Welcome to Our Store</h2>
        <p>This is the home page.</p>
    </main>
<?php
require_once BASE_PATH . '/src/includes/footer.php';
?>