<?php

function clean($key, $type = 'string', $src = INPUT_POST){
    
    $filter = FILTER_SANITIZE_SPECIAL_CHARS;
    $options = 0;
    if ($type === 'string'){
        $filter = FILTER_SANITIZE_STRING;
    }
    if ($type === 'email'){
        $filter = FILTER_SANITIZE_EMAIL;
    } elseif ($type === 'url'){
        $filter = FILTER_SANITIZE_URL;
    }elseif ($type === 'int'){
        $filter = FILTER_SANITIZE_NUMBER_INT;
    } elseif ($type === 'float'){
        $filter = FILTER_SANITIZE_NUMBER_FLOAT;
        $options = FILTER_FLAG_ALLOW_FRACTION;
    }

    $value = filter_input($src, $key, $filter, $options);

    return trim($value ?? '');
}

function esc($val){
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

$username = $password = "";
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = clean('username');
    $password = clean('password');

    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (empty($password)){
        $errors[] = "Password is required.";
    } elseif (!preg_match("/(?=.*\d).{6,}/", $password)){
        $errors[] = "Password must be at least 6 characters and include a number.";
    }

    if (empty($errors)){

        $success = "Registration Successful! Username: " . esc($username);

        $username = $password = "";
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account - Betsy</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <header>
    <img src="../assets/img/logo.png" alt="Betsy Logo" class="logo">
    <h1>Betsy</h1>
  </header>

  <div class="layout">
    <nav>
      <ul>
        <li><a href="../index.html">Home</a></li>
        <li><a href="../pages/upload.html">Upload</a></li>
        <li><a href="../pages/account.html">Account</a></li>
        <li><a href="../pages/contact.html">Contact</a></li>
      </ul>
    </nav>

    <main class="container">
      <div class="account-container">

        <div class="form-section">
          <h2>Sign Up</h2>
          <form action="" method="post">
        Username: <input type="text" name="username" value="<?php echo esc($username); ?>" required><br><br>
        Password: <input type="password" name="Password"
                pattern="(?=.*\d).{6,}"
                title="Minimum 6 characters and at least one number" required><br><br>
        <input type="submit" value="Sign Up">
    </form>
        </div>

        <div class="form-section">
          <h2>Log In</h2>
          <form action="" method="post">
        Username: <input type="text" name="Username" value="<?php echo esc($username); ?>" required><br><br>
        Password: <input type="password" name="Password"
                pattern="(?=.*\d).{6,}"
                title="Minimum 6 characters and at least one number" required><br><br>
        <input type="submit" value="Sign Up">
    </form>
        </div>
      </div>
    </main>
  </div>

  <footer>
    <p>&copy; 2025 Betsy</p>
  </footer>
</body>
</html>
