
<?php
//put sha1() encrypted password here - example is 'hello'
// $page_password = 'aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d';
$page_password = '4f170d6a09b95cfecb465f07bde762fa12289c66';

// session_start();
if (!isset($_SESSION['loggedIn'])) {
    $_SESSION['loggedIn'] = false;
}

if (isset($_POST['page_password'])) {
    if (sha1($_POST['page_password']) == $page_password) {
        $_SESSION['loggedIn'] = true;
    } else {
        die ('Incorrect password');
    }
} 

if (!$_SESSION['loggedIn']): ?>

<html><head><title>Login</title></head>
  <body>
    <p>You need to login</p>
    <form method="post">
      Password: <input type="password" name="page_password"> <br />
      <input type="submit" name="submit" value="Login">
    </form>
  </body>
</html>

<?php
exit();
endif;
?>

