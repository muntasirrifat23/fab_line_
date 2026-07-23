<!DOCTYPE html>
<html>

<head>
  <title>Purbani:Login</title>
  <meta name="viewport" content="width=device-width,height=device-height, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="css/w3.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" type="text/css" href="css/loginPRO.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script type="text/javascript" src="jquery.min.js"></script>
  <style>
    /* ----- LIGHT GRADIENT BACKGROUND (soft) ----- */
    body {
      background: linear-gradient(145deg, #f5f0ff 0%, #ffffff 100%);
      min-height: 100vh;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }

    /* main container card */
    .container {
      background: rgba(255, 255, 255, 0.96);
      border-radius: 40px;
      box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
      max-width: 1100px;
      margin: 2rem auto;
      padding: 2rem 1.8rem;
      transition: all 0.3s ease;
      border: 1px solid #ede7f6;
    }

    /* logo image styling */
    .col img {
      max-width: 85%;
      height: auto;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.05));
    }

    /* input fields */
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px 18px;
      margin: 8px 0;
      border: 1px solid #ddd0f0;
      border-radius: 48px;
      font-size: 15px;
      transition: all 0.25s ease;
      background-color: #ffffff;
      box-shadow: inset 0 1px 2px #f9f5ff;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: #b89ae8;
      outline: none;
      box-shadow: 0 0 0 3px rgba(160, 120, 210, 0.15);
    }

    /* ----- BUTTONS – SLIGHTLY LESS DARK (medium-dark) ----- */
    input[type="button"] {
      border: none;
      border-radius: 48px;
      padding: 12px 20px;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.5px;
      transition: all 0.25s ease;
      cursor: pointer;
      width: 100%;
      margin-top: 10px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    }

    /* Login button – medium dark slate (less dark than before) */
    input[value="Login For Production Apps"] {
      background: linear-gradient(95deg, #295e31, #4b9b4a);
      color: white;
    }

    input[value="Login For Production Apps"]:hover {
      background: linear-gradient(95deg, #2f3b57, #3d4b6b);
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(59, 74, 107, 0.25);
    }

    /* Change Password button – medium grayish blue */
    input[value="Change Password"] {
      background: linear-gradient(95deg, #4d2031, #743355);
      color: white;
    }

    input[value="Change Password"]:hover {
      background: #3b4658;
      transform: translateY(-1px);
    }

    /* Submit Change button – medium teal (less dark) */
    input[value="Submit Change"] {
      background: linear-gradient(95deg, #403388, #406091);
      color: white;
    }

    input[value="Submit Change"]:hover {
      background: linear-gradient(95deg, #235a41, #2e6b50);
      transform: translateY(-1px);
    }

    /* ----- CHECKBOX: ALWAYS WHITE, BIGGER SIZE ----- */
    .remember-wrap {
      margin: 12px 0 15px;
    }

    .remember-label {
      display: flex;
      align-items: center;
      cursor: pointer;
      font-size: 15px;
      font-weight: 500;
      color: #2c2c3a;
      user-select: none;
    }

    .remember-label input {
      display: none;
    }

    /* bigger checkbox (24x24) with white background always */
    .custom-check {
      width: 24px;
      height: 24px;
      border: 2px solid #8b7aa8;
      border-radius: 6px;
      margin-right: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      background-color: #ffffff !important; /* always white */
    }

    .custom-check i {
      color: #4a3a6e;
      font-size: 14px;
      display: none;
    }

    .remember-label input:checked + .custom-check i {
      display: block;
    }

    /* no background change on checked – stays white */
    .remember-label input:checked + .custom-check {
      background-color: #ffffff !important;
      border-color: #5e4a8a;
    }

    .remember-label:hover .custom-check {
      border-color: #5e4a8a;
      box-shadow: 0 0 0 3px rgba(94, 74, 138, 0.15);
    }

    .remember-text {
      letter-spacing: 0.3px;
    }

    /* change password box */
    #changePwdBox {
      background: #fefaff;
      padding: 12px 18px;
      border-radius: 36px;
      margin-top: 18px;
      box-shadow: inset 0 0 0 1px #e9e0fc, 0 2px 6px rgba(0, 0, 0, 0.02);
    }

    /* eye icon */
    span[onclick*="togglePasswordField"] i {
      color: #7a6a9a;
      transition: color 0.2s;
    }
    span[onclick*="togglePasswordField"]:hover i {
      color: #4a3a6e;
    }

    /* responsive */
    @media (max-width: 768px) {
      .container {
        margin: 1rem;
        padding: 1.5rem;
      }
      .col img {
        max-width: 70%;
        margin-bottom: 1rem;
      }
    }

    .row {
      align-items: center;
    }
    .hide-md-lg { display: none; }
    form { width: 100%; }
    #changePwdBox input[type="password"] {
      background: white;
      border-radius: 40px;
      border: 1px solid #e0d4f0;
    }
  </style>
</head>

<body>
  <div class="bottom-containerHEAD">
    <div class="row"><div class="col"></div><div class="col"></div></div>
  </div>
  <div class="container">
    <form method="post">
      <div class="row">
        <div class="col"><img src="image/fabline.jpg" alt="Proline Logo"></div>
        <div class="col">
          <div class="hide-md-lg"></div>
          <input type="text" name="username" id="uname" placeholder="Username" required>
          <div style="position:relative;">
            <input type="password" name="password" id="upass" placeholder="Password" required>
          </div>
          <div class="remember-wrap">
            <label class="remember-label">
              <input type="checkbox" id="rememberMe">
              <span class="custom-check"><i class="fa-solid fa-check"></i></span>
              <span class="remember-text">Remember ID & Password</span>
            </label>
          </div>
          <input type="button" value="Login For Production Apps" onclick="login(1)">
          <input type="button" style="display: none;" value="Login For Graphically Apps" onclick="login(2)" class="w3-blue-grey">
          <br><br>
          <input type="button" value="Change Password" onclick="toggleChangePwd()">
          <div id="changePwdBox" style="display:none;margin-top:12px;">
            <div style="position:relative;margin-top:8px;">
              <input type="password" id="oldpass_cp" placeholder="Old Password" style="width:100%;padding-right:34px;">
              <span onclick="togglePasswordField('oldpass_cp','icon_old_cp')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#7a6a9a;">
                <i id="icon_old_cp" class="fa-solid fa-eye-slash"></i>
              </span>
            </div>
            <div style="position:relative;margin-top:8px;">
              <input type="password" id="newpass_cp" placeholder="New Password" style="width:100%;padding-right:34px;">
              <span onclick="togglePasswordField('newpass_cp','icon_new_cp')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#7a6a9a;">
                <i id="icon_new_cp" class="fa-solid fa-eye-slash"></i>
              </span>
            </div>
            <div style="position:relative;margin-top:8px;">
              <input type="password" id="confpass_cp" placeholder="Confirm New Password" style="width:100%;padding-right:34px;">
              <span onclick="togglePasswordField('confpass_cp','icon_conf_cp')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;color:#7a6a9a;">
                <i id="icon_conf_cp" class="fa-solid fa-eye-slash"></i>
              </span>
            </div>
            <input type="button" value="Submit Change" onclick="loginChangePassword()">
          </div>
        </div>
      </div>
    </form>
  </div>
  <div class="bottom-containerFOOT">
    <div class="row"><div class="col"></div><div class="col"></div></div>
  </div>

  <script>
    function setCookie(name, value, days) {
      var expires = "";
      if (typeof days === 'number') {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = ";expires=" + d.toUTCString();
      }
      document.cookie = name + "=" + encodeURIComponent(value) + expires + ";path=/";
    }

    function getCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
      }
      return null;
    }

    function saveCookie() {
      var remember = $('#rememberMe').is(':checked');
      var txt1 = $('#uname').val();
      var txt2 = $('#upass').val();
      if (remember) {
        setCookie('Name', txt1, 365);
        setCookie('Pass', txt2, 365);
      } else {
        setCookie('Name', '', -1);
        setCookie('Pass', '', -1);
      }
    }

    function loadCookie() {
      var txtt1 = getCookie('Name') || '';
      var txtt2 = getCookie('Pass') || '';
      document.getElementById('uname').value = txtt1;
      document.getElementById('upass').value = txtt2;
      if (txtt1 || txtt2) $('#rememberMe').prop('checked', true);
    }

    function login(param) {
      saveCookie();
      var txt1 = $('#uname').val();
      var txt2 = $('#upass').val();
      $.ajax({
        type: 'POST',
        url: 'loginPOST.php',
        data: { username: txt1, password: txt2, login_user: '1' },
        success: function(msg) {
          var msgNew1 = msg.replace(/\s+/g, ' ').trim();
          if (msgNew1 === 'OK') {
            if (param == 1) window.location.href = 'initialPage.php';
            else if (param == 2) window.location.href = 'http://proline.purbani.com/zPGReportAllWeb/webapp/index.html';
          } else alert('username/password not correct');
        }
      });
    }

    function toggleChangePwd() {
      $('#changePwdBox').toggle();
    }

    function togglePasswordField(fieldId, iconId) {
      var f = document.getElementById(fieldId);
      var ic = document.getElementById(iconId);
      if (!f || !ic) return;
      if (f.type === 'password') {
        f.type = 'text';
        ic.classList.remove('fa-eye-slash');
        ic.classList.add('fa-eye');
      } else {
        f.type = 'password';
        ic.classList.remove('fa-eye');
        ic.classList.add('fa-eye-slash');
      }
    }

    function loginChangePassword() {
      var username = $('#uname').val().trim();
      var oldp = $('#oldpass_cp').val();
      var newp = $('#newpass_cp').val();
      var conf = $('#confpass_cp').val();

      if (!username) {
        alert('Please enter username in the login field');
        return;
      }
      if (!oldp || !newp || !conf) {
        alert('All password fields are required');
        return;
      }
      if (newp !== conf) {
        alert('New password and confirmation do not match');
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'loginChangePassword.php',
        data: { username: username, oldpassword: oldp, newpassword: newp, change_password: '1' },
        success: function(msg) {
          var response = msg.replace(/\s+/g, ' ').trim();
          if (response === 'OK') {
            alert('Password changed successfully. Please login with new password.');
            $('#changePwdBox').hide();
            $('#oldpass_cp, #newpass_cp, #confpass_cp').val('');
          } else {
            alert(response);
          }
        },
        error: function() { alert('Server error.'); }
      });
    }

    $(document).ready(function() { loadCookie(); });
  </script>
</body>

</html>