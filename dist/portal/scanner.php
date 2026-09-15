<?php
declare(strict_types=1);

// Dedicated Attendance Scanner Kiosk for Sri Kalyani Dhamma School
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    ini_set('session.gc_maxlifetime', 90 * 86400); // 90 days persistent kiosk
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(90 * 86400, '/', '', $secureCookie, true);
    session_name('PREFECT_KIOSK_SESSION');
    @session_start();
}

// Kiosk idle check (4 hours timeout) & IP fingerprint validation
if (!empty($_SESSION['kiosk_unlocked'])) {
    $now = time();
    $lastActivity = (int)($_SESSION['kiosk_last_scan'] ?? ($_SESSION['kiosk_unlocked_at'] ?? $now));
    if ($now - $lastActivity > 14400) { // 4 hours
        $_SESSION = [];
        @session_destroy();
        setcookie('PREFECT_KIOSK_SESSION', '', time() - 3600, '/');
        header('Location: scanner.php?timeout=1');
        exit;
    }
    $_SESSION['kiosk_last_scan'] = $now;

    $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ipSubnet = $rawIp;
    if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $rawIp);
        $ipSubnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $fingerprint = hash('sha256', $ipSubnet . '|' . $ua);
    if (isset($_SESSION['kiosk_fingerprint']) && !hash_equals((string)$_SESSION['kiosk_fingerprint'], $fingerprint)) {
        $_SESSION = [];
        @session_destroy();
        setcookie('PREFECT_KIOSK_SESSION', '', time() - 3600, '/');
        header('Location: scanner.php?error=hijack');
        exit;
    }
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/csrf.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = db();
$error = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    @session_destroy();
    setcookie('PREFECT_KIOSK_SESSION', '', time() - 3600, '/');
    header('Location: scanner.php');
    exit;
}

// Handle Unlock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passcode'])) {
    check_rate_limit('kiosk_unlock', 5, 600);
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $pass = (string)$_POST['passcode'];
        $username = clean_string($_POST['username'] ?? '', 60);

        $user = null;
        $role = null;
        $name = null;
        $userId = null;

        // 1. Check admin_users table
        if ($username !== null && $username !== '') {
            $stmt = $pdo->prepare('SELECT id, username, name, role, passcode_hash FROM admin_users WHERE LOWER(username) = LOWER(:u) LIMIT 1');
            $stmt->execute(['u' => $username]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($pass, (string)$admin['passcode_hash'])) {
                $user = $admin;
                $userId = $admin['id'];
                $role = $admin['role'];
                $name = $admin['name'];
            }
        } else {
            // Passcode check against admin_users
            $admins = $pdo->query('SELECT id, username, name, role, passcode_hash FROM admin_users LIMIT 50')->fetchAll();
            foreach ($admins as $adm) {
                if (password_verify($pass, (string)$adm['passcode_hash'])) {
                    $user = $adm;
                    $userId = $adm['id'];
                    $role = $adm['role'];
                    $name = $adm['name'];
                    break;
                }
            }
        }

        // 2. Fallback to settings master passcode
        if (!$user) {
            $row = $pdo->query('SELECT passcode_hash FROM settings WHERE id = 1')->fetch();
            if ($row && password_verify($pass, (string)$row['passcode_hash'])) {
                $userId = 'ADM-TOP01';
                $role = 'top_board';
                $name = 'Top Board Prefect';
                $user = ['id' => $userId, 'role' => $role, 'name' => $name];
            }
        }

        if ($user !== null) {
            @session_regenerate_id(true);
            $_SESSION['kiosk_unlocked'] = true;
            $_SESSION['kiosk_unlocked_at'] = time();
            $_SESSION['kiosk_last_scan'] = time();
            $_SESSION['kiosk_user_id'] = $userId;
            $_SESSION['kiosk_user_name'] = $name;
            $rawIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ipSubnet = $rawIp;
            if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $parts = explode('.', $rawIp);
                $ipSubnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
            $_SESSION['kiosk_fingerprint'] = hash('sha256', $ipSubnet . '|' . $ua);
            header('Location: scanner.php');
            exit;
        } else {
            $error = 'Incorrect Username or Passcode.';
        }
    }
}

$isUnlocked = !empty($_SESSION['kiosk_unlocked']);
$csrf = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
<title>Gate Attendance Scanner &mdash; Sri Kalyani Dhamma School</title>
<link rel="icon" type="image/x-icon" href="../Logo/favicon.ico">
<link rel="shortcut icon" href="../Logo/favicon.ico">
<link rel="apple-touch-icon" href="../Logo/logo.png">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  :root{
    --ink:#2A1810; --ink-soft:#6B5642;
    --parchment:#F3EAD6; --parchment-deep:#E6D5AE; --parchment-line:#D8C193;
    --maroon:#7A2333; --maroon-deep:#5C1A26; --maroon-tint:#F7E9EB;
    --saffron:#C98A22; --saffron-soft:#F0D9A0; --saffron-deep:#9C6A15;
    --positive:#3F6B4A; --positive-tint:#E7F0E7;
    --shadow: 0 1px 2px rgba(42,24,16,0.06), 0 6px 18px rgba(42,24,16,0.07);
    --font-display: Georgia, 'Iowan Old Style', 'Palatino Linotype', serif;
    --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-mono: ui-monospace, 'SF Mono', Consolas, monospace;
    --radius: 10px;
  }
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;background:var(--parchment);color:var(--ink);font-family:var(--font-body);font-size:15px;line-height:1.5;}
  body::before{
    content:""; position:fixed; inset:0; pointer-events:none; z-index:0;
    background-image: repeating-linear-gradient(180deg, rgba(122,35,51,0.025) 0px, rgba(122,35,51,0.025) 1px, transparent 1px, transparent 34px);
  }
  .kiosk-wrap{position:relative; z-index:1; max-width:540px; margin:0 auto; padding:18px 16px 40px;}
  .topbar{background:linear-gradient(180deg, var(--maroon) 0%, var(--maroon-deep) 100%); color:#F7ECC9; padding:16px 18px; border-radius:12px; margin-bottom:16px; box-shadow:var(--shadow); display:flex; align-items:center; justify-content:space-between;}
  .topbar h1{font-family:var(--font-display); font-size:17px; margin:0 0 2px; color:#FBF3DC;}
  .topbar p{margin:0; font-size:11px; letter-spacing:0.12em; text-transform:uppercase; color:var(--saffron-soft); font-weight:700;}
  .card{background:#FBF6EA; border:1px solid var(--parchment-line); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow); margin-bottom:16px;}
  .card h2{font-family:var(--font-display); font-size:18px; color:var(--maroon-deep); margin:0 0 4px;}
  .card .sub{color:var(--ink-soft); font-size:12.5px; margin:0 0 16px;}
  .btn{font-family:var(--font-body); font-weight:600; font-size:13.5px; border-radius:8px; border:1.5px solid var(--maroon); padding:10px 16px; cursor:pointer; background:var(--maroon); color:#FBF3DC;}
  .btn:hover{background:var(--maroon-deep);}
  .btn-ghost{background:transparent; color:var(--maroon); border-color:var(--maroon);}
  .btn-sm{padding:6px 10px; font-size:12px; border-radius:6px;}
  .btn-block{width:100%;}
  .scanner-box{border:2px solid var(--saffron-deep); border-radius:12px; overflow:hidden; background:#000; position:relative; min-height:280px;}
  #reader{width:100%;}
  .mode-switch{display:flex; gap:8px; margin-bottom:14px;}
  .mode-btn{flex:1; padding:10px; text-align:center; border:1.5px solid var(--parchment-line); border-radius:8px; background:#fff; font-weight:600; font-size:13px; cursor:pointer;}
  .mode-btn.active{background:var(--maroon-tint); border-color:var(--maroon); color:var(--maroon);}
  .input-text{width:100%; padding:10px; border-radius:7px; border:1.5px solid var(--parchment-line); font-family:inherit; font-size:14px;}
  .input-text:focus{outline:none; border-color:var(--saffron); box-shadow:0 0 0 3px var(--saffron-soft);}
  .status-badge{text-align:center; padding:10px; font-weight:600; font-size:13px; color:var(--ink-soft); font-family:var(--font-mono);}
  .cooldown-pill{display:inline-block; padding:3px 8px; border-radius:6px; background:#FFF3CD; color:#856404; font-size:11px; font-weight:700;}
</style>
</head>
<body>

<div class="kiosk-wrap">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="width:42px; height:42px; border-radius:50%; background:#fff; border:1.5px solid var(--saffron-soft); overflow:hidden; flex:none; display:flex; align-items:center; justify-content:center; padding:2px;">
        <img src="../Logo/logo.png" alt="SKDS Logo" style="width:100%; height:100%; object-fit:contain; display:block;">
      </div>
      <div>
        <h1>Sri Kalyani Dhamma School</h1>
        <p>Gate Attendance Kiosk</p>
      </div>
    </div>
    <?php if ($isUnlocked): ?>
      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
        <span style="font-size:12px; color:#F7ECC9; background:rgba(255,255,255,0.12); padding:4px 10px; border-radius:6px; font-family:var(--font-sans);">
          Top Board Prefect: <b><?= htmlspecialchars($_SESSION['kiosk_user_name'] ?? 'Top Board Prefect') ?></b>
        </span>
        <a href="?action=logout" class="btn btn-ghost btn-sm" style="color:#F7ECC9; border-color:#F7ECC9;" onclick="return confirm('Lock Kiosk?');">Lock</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$isUnlocked): ?>
    <!-- PASSCODE UNLOCK SCREEN -->
    <div class="card" style="text-align:center; margin-top:24px;">
      <div style="width:64px; height:64px; border-radius:50%; background:#fff; border:2px solid var(--maroon); overflow:hidden; margin:0 auto 12px; display:flex; align-items:center; justify-content:center; padding:4px;">
        <img src="../Logo/logo.png" alt="SKDS Logo" style="width:100%; height:100%; object-fit:contain; display:block;">
      </div>
      <h2>Kiosk Authorization</h2>
      <p class="sub">Enter your administrator credentials or Master Passcode to unlock the gate scanner display.</p>
      <?php if ($error): ?>
        <p style="color:#A23B2E; font-size:13px; font-weight:600; margin-bottom:12px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="text" name="username" class="input-text" placeholder="Username (Optional for Master Passcode)" style="text-align:center; margin-bottom:10px;" autocomplete="username">
        <input type="password" name="passcode" class="input-text" placeholder="Passcode" style="text-align:center; margin-bottom:14px;" autofocus required autocomplete="current-password">
        <button type="submit" class="btn btn-block">Unlock Scanner</button>
      </form>
      <div style="margin-top:16px;">
        <a href="../#overview" style="color:var(--maroon); font-size:12px; text-decoration:none;">&larr; Return to Overview</a>
      </div>
    </div>

  <?php else: ?>
    <!-- SCANNER SCREEN -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div>
          <h2>Roll Call Scanner</h2>
          <p class="sub" style="margin-bottom:0;">Live camera &amp; handheld QR scanner</p>
        </div>
        <span class="cooldown-pill">5m Cooldown Protection</span>
      </div>

      <!-- Mode Selector -->
      <div class="mode-switch">
        <button type="button" class="mode-btn active" id="modeSundayBtn" onclick="setMode('sunday')">Sunday Duty</button>
        <button type="button" class="mode-btn" id="modeEventBtn" onclick="setMode('event')">Special Event</button>
      </div>
      <div id="eventFieldWrap" style="display:none; margin-bottom:14px;">
        <input type="text" id="eventTitleInput" class="input-text" placeholder="Event Name (e.g. Katina Perahera, Vesak Zone)">
      </div>

      <div class="scanner-box">
        <div id="reader"></div>
      </div>

      <!-- USB/Barcode input -->
      <div style="margin-top:14px; display:flex; gap:8px;">
        <input type="text" id="manualScanInput" class="input-text" placeholder="Scan Barcode / USB or Paste UUID" autocomplete="off">
        <button class="btn btn-sm" type="button" id="manualScanBtn">Enter</button>
      </div>

      <div class="status-badge" id="scanStatus">Camera active &mdash; point at personal code</div>
    </div>
  <?php endif; ?>
</div>

<?php if ($isUnlocked): ?>
<script>
let activeMode = 'sunday';
let isScanningPaused = false;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function setMode(mode){
  activeMode = mode;
  document.getElementById('modeSundayBtn').classList.toggle('active', mode==='sunday');
  document.getElementById('modeEventBtn').classList.toggle('active', mode==='event');
  document.getElementById('eventFieldWrap').style.display = (mode==='event') ? 'block' : 'none';
}

function processScan(scannedValue){
  if(isScanningPaused) return;
  const token = String(scannedValue||'').trim();
  if(!token) return;

  isScanningPaused = true;
  document.getElementById('scanStatus').textContent = 'Verifying scan...';

  const bodyData = {
    token: token,
    mode: activeMode,
    eventName: activeMode==='event' ? (document.getElementById('eventTitleInput').value.trim() || 'Special Event') : null
  };

  fetch('../api/attendance.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(bodyData)
  })
  .then(r => r.json())
  .then(res => {
    if(res.status === 'success'){
      Swal.fire({
        icon: 'success',
        title: res.action === 'checkin' ? 'CHECKED IN' : 'CHECKED OUT',
        html: `<b>${res.name}</b> (${res.tier})<br><span style="font-size:16px;">${res.time}</span><br><p class="small">${res.message}</p>`,
        timer: 3000,
        showConfirmButton: false
      });
    } else if(res.status === 'warning'){
      Swal.fire({
        icon: 'warning',
        title: 'Scan Too Soon',
        html: `<b>${res.name}</b><br><p>${res.message}</p>`,
        timer: 3000,
        showConfirmButton: false
      });
    } else if(res.status === 'info'){
      Swal.fire({
        icon: 'info',
        title: 'Already Completed',
        text: res.message,
        timer: 3500,
        showConfirmButton: false
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Scan Error',
        text: res.error || 'Prefect QR code not recognised.'
      });
    }
  })
  .catch(err => {
    Swal.fire({ icon:'error', title:'Network Error', text:'Could not reach attendance server.' });
  })
  .finally(() => {
    setTimeout(() => {
      isScanningPaused = false;
      document.getElementById('scanStatus').textContent = 'Camera active — ready for next scan';
    }, 1800);
  });
}

// Start HTML5-QRCode Scanner
try {
  const html5QrCode = new Html5Qrcode("reader");
  html5QrCode.start(
    { facingMode: "environment" },
    { fps: 6, qrbox: { width: 220, height: 220 } },
    (decodedText) => { processScan(decodedText); },
    (error) => { /* ignore frame errors */ }
  ).catch(err => {
    document.getElementById('scanStatus').textContent = 'Camera access blocked — enable permissions or use USB scanner.';
  });
} catch(e) {
  document.getElementById('scanStatus').textContent = 'Camera scanner initialized.';
}

// Manual / USB Handheld Scanner Support
document.getElementById('manualScanBtn').addEventListener('click', function(){
  const val = document.getElementById('manualScanInput').value;
  if(val){ processScan(val); document.getElementById('manualScanInput').value = ''; }
});
document.getElementById('manualScanInput').addEventListener('keydown', function(e){
  if(e.key === 'Enter'){
    e.preventDefault();
    const val = this.value;
    if(val){ processScan(val); this.value = ''; }
  }
});
</script>
<?php endif; ?>

</body>
</html>
