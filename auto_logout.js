(function(){
  var POLL_INTERVAL_MS = 60000; // 60 seconds

  function checkSession() {
    return fetch('session_status.php', {
      credentials: 'same-origin',
      headers: {'X-Requested-With':'XMLHttpRequest'}
    }).then(function(res){
      if (res.status === 401) {
        // Session already expired on server
        window.location.href = 'login.php';
        return null;
      }
      return res.json().catch(function(){ return null; });
    }).then(function(data){
      if (!data) return;
      if (data.status === 'expired' || data.status === 'no_session') {
        window.location.href = 'login.php';
        return;
      }
      // if remaining is very small, redirect slightly after expiry
      var remaining = parseInt(data.remaining, 10);
      if (!isNaN(remaining) && remaining <= 2) {
        setTimeout(function(){ window.location.href = 'login.php'; }, (remaining + 1) * 1000);
      }
    }).catch(function(){ /* network errors ignored */ });
  }

  function init(){
    checkSession();
    // periodic polling to detect session expiry on long-lived pages
    setInterval(checkSession, POLL_INTERVAL_MS);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
