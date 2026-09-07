</main>
<script>
document.addEventListener('click', function(e) {
  var sidebar = document.getElementById('sidebar');
  var hb = document.querySelector('.hamburger');
  if (sidebar && !sidebar.contains(e.target) && hb && !hb.contains(e.target)) {
    sidebar.classList.remove('open');
  }
});

const themeToggle = document.querySelector('.theme-toggle');
const connectionStatus = document.getElementById('connection-status');
function updateConnectionStatus() {
  if (!connectionStatus) return;
  const online = navigator.onLine;
  connectionStatus.textContent = online ? '● Online' : '● Offline';
  connectionStatus.classList.toggle('offline', !online);
}
window.addEventListener('online', updateConnectionStatus);
window.addEventListener('offline', updateConnectionStatus);
updateConnectionStatus();
const savedTheme = localStorage.getItem('dukasmart-theme');
if (savedTheme === 'dark') document.body.classList.add('dark-theme');
if (themeToggle) {
  themeToggle.addEventListener('click', function() {
    document.body.classList.toggle('dark-theme');
    const dark = document.body.classList.contains('dark-theme');
    localStorage.setItem('dukasmart-theme', dark ? 'dark' : 'light');
    this.textContent = dark ? '☀️' : '🌙';
  });
  themeToggle.textContent = document.body.classList.contains('dark-theme') ? '☀️' : '🌙';
}

let deferredPrompt;
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault(); deferredPrompt = e;
  var b = document.getElementById('install-banner');
  if (b) b.style.display = 'flex';
});
function installApp() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then(function() {
    deferredPrompt = null;
    var b = document.getElementById('install-banner');
    if (b) b.style.display = 'none';
  });
}
if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(function(){});

document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity 0.6s';
    el.style.opacity = '0';
    setTimeout(function() { if(el.parentNode) el.parentNode.removeChild(el); }, 700);
  }, 5000);
});
</script>
</body>
</html>
