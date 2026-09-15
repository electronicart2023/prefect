<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Register / Login', 'register.php');
?>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:18px;">
    
    <!-- Registration Form -->
    <div class="card" id="registerFormCard">
      <p class="eyebrow">New Member Registration</p>
      <h2>Join the Prefect Guild</h2>
      <p class="sub">Fill out your details below. Once submitted, your registration will be reviewed and approved by the <b>Head Prefect</b>.</p>
      
      <div class="field">
        <label for="regName">Full Name *</label>
        <input type="text" id="regName" placeholder="e.g. Kalana Jayasinghe">
      </div>
      <div class="field">
        <label for="regTier">Guild Tier *</label>
        <select id="regTier">
          <option value="probation">Probation Leader (Tier 1)</option>
          <option value="junior">Junior Leader (Tier 2)</option>
          <option value="senior">Senior Prefect</option>
        </select>
      </div>
      <div class="field">
        <label for="regGrade">Grade / Class</label>
        <input type="text" id="regGrade" placeholder="e.g. Grade 10 or Dhamma Final">
      </div>
      <div class="field">
        <label for="regEmail">Email Address (Optional)</label>
        <input type="email" id="regEmail" placeholder="student@example.com">
      </div>
      
      <p class="eyebrow" style="margin-top:14px;">Contact Numbers (3 Total)</p>
      <div class="field">
        <label for="regContact1">Primary Contact Number * (Student Mobile / WhatsApp)</label>
        <input type="tel" id="regContact1" placeholder="e.g. 07X XXX XXXX">
      </div>
      <div class="field">
        <label for="regContact2">Secondary Contact Number (Home / Guardian)</label>
        <input type="tel" id="regContact2" placeholder="e.g. 011 XXX XXXX">
      </div>
      <div class="field">
        <label for="regContact3">Emergency / Guardian Contact</label>
        <input type="tel" id="regContact3" placeholder="e.g. 0771234567">
      </div>

      <div class="field" style="margin-top:12px;">
        <label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; font-size:12.5px; line-height:1.4;">
          <input type="checkbox" id="regConsentCheckbox" style="margin-top:2px;" required>
          <span>I confirm that I am a student of Sri Kalyani Dhamma School and that my parent/guardian consents to collecting these contact details for official school prefect communications.</span>
        </label>
      </div>

      <button class="btn btn-primary btn-block" id="submitRegBtn" style="margin-top:10px;">Complete Registration</button>
    </div>

    <!-- Registration Success Card -->
    <div class="card" id="registerSuccessCard" style="display:none;">
      <p class="eyebrow">Registration Submitted</p>
      <h2 id="regSuccessName">Welcome, Prefect</h2>
      <p class="sub">Your membership registration has been recorded and submitted for Head Prefect review.</p>
      
      <div class="stat-row">
        <div class="stat"><span class="n" id="regSuccessId" style="font-size:15px;">&mdash;</span><span class="l">Prefect ID</span></div>
        <div class="stat"><span class="n" id="regSuccessPin">&mdash;</span><span class="l">4-Digit PIN</span></div>
      </div>
      
      <div class="divider"></div>
      <p class="eyebrow">Personal Attendance QR Code</p>
      <p class="sub">Save a screenshot of this QR code. Present it to the gate marshal each Sunday or event to record attendance.</p>
      <div class="qr-box" id="regQrBox" style="display:inline-block; margin-bottom:12px;"></div>
      
      <div style="background:#FFF9C4; border:1px solid #FFE082; color:#F57F17; padding:12px; border-radius:8px; font-size:13px;">
        <b>Status: Pending Head Prefect Approval</b><br>
        Save your <b>Prefect ID</b> and <b>4-digit PIN</b>. Once approved by the Head Prefect, you can sign in to access your individual Profile.
      </div>

      <div style="margin-top:16px;">
        <button class="btn btn-ghost btn-sm" id="regAgainBtn">Register Another Prefect</button>
      </div>
    </div>

    <!-- Dual Sign-In Box -->
    <div class="card">
      <p class="eyebrow">Sign In</p>
      <h2>Member &amp; Admin Sign In</h2>
      <p class="sub">Prefects sign in to view their private profile; Administrators sign in to access Top Board management.</p>

      <!-- Prefect Sign In -->
      <div style="background:var(--parchment-deep); padding:16px; border-radius:8px; border:1px solid var(--parchment-line); margin-bottom:16px;">
        <h3 style="margin-top:0; font-size:15px;">Prefect Sign In (Own Profile)</h3>
        <p class="small muted" style="margin-bottom:10px;">Approved prefects sign in with their registered Name (or Prefect ID) and 4-digit PIN.</p>
        <form id="prefectSignInForm" onsubmit="return false;" autocomplete="off">
          <div class="field">
            <label for="loginPrefectName">Full Name or Prefect ID *</label>
            <input type="text" id="loginPrefectName" placeholder="e.g. Kalana or SKP-..." autocomplete="username">
          </div>
          <div class="field">
            <label for="loginPrefectPin">4-Digit PIN *</label>
            <input type="password" maxlength="4" inputmode="numeric" id="loginPrefectPin" placeholder="4-Digit PIN" autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-saffron btn-block" id="loginPrefectBtn">Sign In to My Profile</button>
        </form>
      </div>

      <!-- Admin Sign In -->
      <div style="background:var(--parchment-deep); padding:16px; border-radius:8px; border:1px solid var(--parchment-line);">
        <h3 style="margin-top:0; font-size:15px;">Top Board &amp; Admin Sign In</h3>
        <p class="small muted" style="margin-bottom:10px;">Principal Thero, Head Prefect, and Top Board members sign in with assigned passcode.</p>
        <form id="adminSignInForm" onsubmit="return false;" autocomplete="off">
          <div class="field">
            <label for="loginAdminUser">Username (Optional for Master Passcode)</label>
            <input type="text" id="loginAdminUser" placeholder="e.g. headprefect, principal, topboard" autocomplete="username">
          </div>
          <div class="field">
            <label for="loginAdminPass">Admin Passcode *</label>
            <input type="password" id="loginAdminPass" placeholder="Passcode" autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary btn-block" id="loginAdminBtn">Sign In to Admin Panel</button>
        </form>
      </div>
    </div>

  </div>

<?php renderFooter(); ?>
<script>
initCommon();

// Registration Submission
document.getElementById('submitRegBtn').addEventListener('click', async function(){
  var name = document.getElementById('regName').value.trim();
  var tier = document.getElementById('regTier').value;
  var grade = document.getElementById('regGrade').value.trim();
  var email = document.getElementById('regEmail').value.trim();
  var contact1 = document.getElementById('regContact1').value.trim();
  var contact2 = document.getElementById('regContact2').value.trim();
  var contact3 = document.getElementById('regContact3').value.trim();

  if(!name){ toast('Please enter your full name.', true); return; }
  if(!contact1){ toast('Primary contact number is required.', true); return; }
  var consent = document.getElementById('regConsentCheckbox');
  if(consent && !consent.checked){ toast('Parental / guardian consent is required to submit registration.', true); return; }

  var btn = this;
  btn.disabled = true;
  btn.textContent = 'Submitting...';

  var r = await apiCall('register.php', {
    name: name,
    tier: tier,
    grade: grade,
    email: email,
    contact_primary: contact1,
    contact_secondary: contact2,
    contact_emergency: contact3
  });

  btn.disabled = false;
  btn.textContent = 'Complete Registration';

  if(!r.ok && !r.id){
    toast(r.error || 'Registration failed.', true);
    return;
  }

  // Show Success Screen
  document.getElementById('registerFormCard').style.display = 'none';
  var sc = document.getElementById('registerSuccessCard');
  sc.style.display = 'block';

  document.getElementById('regSuccessName').textContent = 'Welcome, ' + r.name;
  document.getElementById('regSuccessId').textContent = r.id;
  document.getElementById('regSuccessPin').textContent = r.pin;

  var qb = document.getElementById('regQrBox');
  qb.innerHTML = '';
  new QRCode(qb, {text: r.qrToken, width:130, height:130, colorDark:'#2A1810', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});

  toast('Registration submitted! Awaiting Head Prefect approval.');
});

document.getElementById('regAgainBtn').addEventListener('click', function(){
  document.getElementById('registerSuccessCard').style.display = 'none';
  document.getElementById('registerFormCard').style.display = 'block';
  document.getElementById('regName').value = '';
  document.getElementById('regGrade').value = '';
  document.getElementById('regEmail').value = '';
  document.getElementById('regContact1').value = '';
  document.getElementById('regContact2').value = '';
  document.getElementById('regContact3').value = '';
  var consent = document.getElementById('regConsentCheckbox');
  if(consent) consent.checked = false;
});

// Prefect Sign In
async function doPrefectLogin(){
  var name = document.getElementById('loginPrefectName').value.trim();
  var pin = document.getElementById('loginPrefectPin').value.trim();
  if(!name || !pin){ toast('Please enter your Name/ID and 4-digit PIN.', true); return; }

  var btn = document.getElementById('loginPrefectBtn');
  btn.disabled = true;
  var r = await apiCall('lookup.php', {name: name, pin: pin});
  btn.disabled = false;

  if(r && (r.ok || r.id) && !r.error){
    sessionStorage.setItem('skds_prefect', JSON.stringify(r));
    localStorage.removeItem('skds_prefect');
    toast('Welcome, ' + (r.name || 'Prefect') + '! Loading your profile...');
    setTimeout(function(){ window.location.href = 'profile.php'; }, 400);
  } else {
    toast((r && r.error) ? r.error : 'Incorrect name or PIN.', true);
  }
}
document.getElementById('loginPrefectBtn').addEventListener('click', function(e){ e.preventDefault(); doPrefectLogin(); });
var pForm = document.getElementById('prefectSignInForm');
if(pForm){ pForm.addEventListener('submit', function(e){ e.preventDefault(); doPrefectLogin(); }); }

// Admin Sign In
async function doAdminLogin(){
  var user = document.getElementById('loginAdminUser').value.trim();
  var pass = document.getElementById('loginAdminPass').value.trim();
  if(!pass){ toast('Please enter your admin passcode.', true); return; }

  var btn = document.getElementById('loginAdminBtn');
  btn.disabled = true;
  var r = await apiCall('auth.php', {username: user, passcode: pass});
  btn.disabled = false;

  if(r.ok){
    toast('Admin authenticated! Loading dashboard...');
    setTimeout(function(){ window.location.href = 'topboard.php'; }, 400);
  } else {
    toast(r.error || 'Invalid passcode or credentials.', true);
  }
}
document.getElementById('loginAdminBtn').addEventListener('click', function(e){ e.preventDefault(); doAdminLogin(); });
var aForm = document.getElementById('adminSignInForm');
if(aForm){ aForm.addEventListener('submit', function(e){ e.preventDefault(); doAdminLogin(); }); }
</script>