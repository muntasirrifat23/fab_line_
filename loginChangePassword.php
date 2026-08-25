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

  function change_password_in_table($db, $table, $id_col, $pass_col, $username, $oldp, $newp)
  {
    $query = "SELECT $pass_col FROM $table WHERE $id_col = '$username' LIMIT 1";
    $res = mysqli_query($db, $query);
    if (!$res) return 'server_error';
    if (mysqli_num_rows($res) != 1) return 'not_found';

    $row = mysqli_fetch_assoc($res);
    if (!check_password_match($row[$pass_col], $oldp)) return 'wrong_old';

    $new_md5 = md5($newp);
    $update = "UPDATE $table SET $pass_col = '$new_md5' WHERE $id_col = '$username'";
    return mysqli_query($db, $update) ? 'OK' : 'update_failed';
  }

  // 1) users table
  $result = change_password_in_table($db, 'users', 'USER_ID', 'password', $username, $oldp, $newp);
  if ($result !== 'not_found') {
    if ($result === 'OK') echo 'OK';
    elseif ($result === 'wrong_old') echo 'Old password not correct';
    else echo 'Failed to update password';
    exit;
  }

  // 2) knitting_operator table
  $result = change_password_in_table($db, 'knitting_operator', 'OPERATOR_ID', 'OPERATOR_PASSWORD', $username, $oldp, $newp);
  if ($result !== 'not_found') {
    if ($result === 'OK') echo 'OK';
    elseif ($result === 'wrong_old') echo 'Old password not correct';
    else echo 'Failed to update password';
    exit;
  }

  // 3) knitting_operator_qc table
  $result = change_password_in_table($db, 'knitting_operator_qc', 'KNITTING_QC_ID', 'KNITTING_QC_PASSWORD', $username, $oldp, $newp);
  if ($result !== 'not_found') {
    if ($result === 'OK') echo 'OK';
    elseif ($result === 'wrong_old') echo 'Old password not correct';
    else echo 'Failed to update password';
    exit;
  }

  echo 'User ID not found';
}
exit;
