<?php
include('config.php');

if (isset($_POST['change_password'])) {
  $username = isset($_POST['username']) ? trim(mysqli_real_escape_string($db, $_POST['username'])) : '';
  $oldp = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : '';
  $newp = isset($_POST['newpassword']) ? $_POST['newpassword'] : '';

  if (empty($username)) {
    echo 'Username is required';
    exit;
  }
  if (empty($oldp) || empty($newp)) {
    echo 'Old and new password are required';
    exit;
  }

  function check_password_match($stored, $plain)
  {
    $plain_trim = trim($plain);
    if ($stored === md5($plain)) return true;
    if ($stored === md5($plain_trim)) return true;
    if ($stored === $plain) return true;
    if ($stored === $plain_trim) return true;
    return false;
  }

  $query = "SELECT password FROM users WHERE username = '$username' LIMIT 1";
  $res = mysqli_query($db, $query);
  if (!$res) {
    echo 'Server error';
    exit;
  }
  if (mysqli_num_rows($res) == 1) {
    $row = mysqli_fetch_assoc($res);
    $stored_password = $row['password'];

    if (check_password_match($stored_password, $oldp)) {
      $new_md5 = md5($newp);
      $update = "UPDATE users SET password = '$new_md5' WHERE username = '$username'";
      if (mysqli_query($db, $update)) {
        echo 'OK';
      } else {
        echo 'Failed to update password';
      }
    } else {
      echo 'Old password not correct';
    }
  } else {
    echo 'Username not found';
  }
}
exit;
