<?php
include('config.php');

if (isset($_POST['change_password'])) {
  $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
  $oldp = isset($_POST['oldpassword']) && is_string($_POST['oldpassword']) ? $_POST['oldpassword'] : '';
  $newp = isset($_POST['newpassword']) && is_string($_POST['newpassword']) ? $_POST['newpassword'] : '';

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
    $select = $db->prepare("SELECT $pass_col FROM $table WHERE $id_col = ? LIMIT 1");
    if (!$select) return 'server_error';
    $select->bind_param('s', $username);
    if (!$select->execute()) {
      $select->close();
      return 'server_error';
    }
    $res = $select->get_result();
    if (!$res) {
      $select->close();
      return 'server_error';
    }
    if ($res->num_rows !== 1) {
      $select->close();
      return 'not_found';
    }

    $row = $res->fetch_assoc();
    $select->close();
    if (!check_password_match($row[$pass_col], $oldp)) return 'wrong_old';

    $new_md5 = md5($newp);
    $update = $db->prepare("UPDATE $table SET $pass_col = ? WHERE $id_col = ?");
    if (!$update) return 'update_failed';
    $update->bind_param('ss', $new_md5, $username);
    $updated = $update->execute();
    $update->close();
    return $updated ? 'OK' : 'update_failed';
  }

  // Finishing operator IDs can also exist in users; update the dedicated operator account first.
  $result = change_password_in_table($db, 'finishing_operator', 'FN_OP_ID', 'FN_OP_PASSWORD', $username, $oldp, $newp);
  if ($result === 'wrong_old' && strtolower($username) === 'f01') {
    $result = change_password_in_table($db, 'users', 'USER_ID', 'password', $username, $oldp, $newp);
    if ($result === 'not_found') $result = 'wrong_old';
  }
  if ($result !== 'not_found') {
    if ($result === 'OK') echo 'OK';
    elseif ($result === 'wrong_old') echo 'Old password not correct';
    else echo 'Failed to update password';
    exit;
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

  // 4) dyeing_operator table
  $result = change_password_in_table($db, 'dyeing_operator', 'DY_OP_ID', 'DY_OP_PASSWORD', $username, $oldp, $newp);
  if ($result !== 'not_found') {
    if ($result === 'OK') echo 'OK';
    elseif ($result === 'wrong_old') echo 'Old password not correct';
    else echo 'Failed to update password';
    exit;
  }

  echo 'User ID not found';
}
exit;
