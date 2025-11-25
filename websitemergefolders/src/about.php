<?php
//about.php -- static page for example


require_once '../config/config.php';  //get the BASE_PATH and BASE_URL

$page_title = "About Us";
require_once BASE_PATH . '/src/includes/header.php';
?>
    <main>
        <h2>About Us</h2>
        <p>We’re a small store built by students!</p>
    </main>
<?php
require_once BASE_PATH . '/src/includes/footer.php';
?>