<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Guild Overview', 'index.php');
?>
  <div class="card">
    <p class="eyebrow">Proposal on record</p>
    <h2>Student Leadership Merit &amp; Reward System</h2>
    <p class="sub">A transparent, point-based register that tracks Sunday duty and guild dedication over two-month cycles &mdash; prepared for Principal Thero's review.</p>
    <div class="stat-row" id="overviewStats">
      <div class="stat"><span class="n" id="statProbation">&hellip;</span><span class="l">Probation Leaders</span></div>
      <div class="stat"><span class="n" id="statJunior">&hellip;</span><span class="l">Junior Leaders</span></div>
      <div class="stat"><span class="n" id="statAttendanceToday">&hellip;</span><span class="l">Checked in today</span></div>
      <div class="stat"><span class="n" id="statEntriesLogged">&hellip;</span><span class="l">Entries Logged</span></div>
    </div>
  </div>

  <div class="card">
    <p class="eyebrow">Points framework</p>
    <h2>100 points / month, three pillars</h2>
    <div class="table-wrap">
    <table class="ledger">
      <thead><tr><th>Pillar</th><th>Focus</th><th style="text-align:right">Max</th></tr></thead>
      <tbody>
        <tr><td class="name-cell">Sunday Duty Performance</td><td class="muted small">Punctuality, uniform, duty execution, initiative</td><td style="text-align:right" class="pts">50</td></tr>
        <tr><td class="name-cell">Extra Events &amp; Guild Dedication</td><td class="muted small">Event attendance, task ownership, problem-solving</td><td style="text-align:right" class="pts">35</td></tr>
        <tr><td class="name-cell">Conduct &amp; Spiritual Leadership</td><td class="muted small">Buddhist values, team synergy</td><td style="text-align:right" class="pts">15</td></tr>
      </tbody>
    </table>
    </div>
    <p class="field-note" style="margin-top:10px;">Senior Prefects aren't a scored tier &mdash; they form the Top Board, evaluating Probation and Junior Leaders via the passcode-gated panel, while they focus on national examinations.</p>
  </div>

  <div class="card">
    <p class="eyebrow">Registration</p>
    <h2>Scan to join the Guild</h2>
    <p class="sub">Print this page or the QR code below and display it at Dhamma school &mdash; new Probation, Junior, and Senior Prefects can register themselves in under a minute.</p>
    <div class="qr-wrap">
      <div class="qr-box" id="qrBox"></div>
      <div style="flex:1; min-width:220px;">
        <label class="field-note" for="publicUrlInput">Link this QR code opens</label>
        <div class="link-row">
          <input type="text" id="publicUrlInput" placeholder="https://…">
          <button class="btn btn-ghost btn-sm" id="saveUrlBtn">Save</button>
        </div>
        <p class="field-note" style="margin-top:8px;">This is auto-filled from the page you're viewing. Only change this if your site is reachable at a different public address (for example, behind a proxy or CDN).</p>
        <button class="btn btn-saffron btn-sm" id="copyLinkBtn" style="margin-top:10px;">Copy registration link</button>
      </div>
    </div>
  </div>

<?php renderFooter(); ?>
<script>
async function renderOverview(){
  await initCommon();
  var prob = state.prefects.filter(function(p){ return p.tier==='probation'; }).length;
  var jun = state.prefects.filter(function(p){ return p.tier==='junior'; }).length;
  var today = todayStr();
  var checkedIn = state.attendance.filter(function(a){ return a.date===today; }).length;
  var totalEntries = state.entries.length;

  document.getElementById('statProbation').textContent = prob;
  document.getElementById('statJunior').textContent = jun;
  document.getElementById('statAttendanceToday').textContent = checkedIn;
  document.getElementById('statEntriesLogged').textContent = totalEntries;

  var currentUrl = state.config.publicUrl || (location.protocol+'//'+location.host+location.pathname.replace('index.php','register.php').replace('index.html','register.php'));
  var input = document.getElementById('publicUrlInput');
  input.value = currentUrl;

  var box = document.getElementById('qrBox');
  box.innerHTML = '';
  try{
    new QRCode(box, {text:currentUrl, width:150, height:150, colorDark:'#2A1810', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});
  }catch(e){}
}

document.getElementById('saveUrlBtn').addEventListener('click', async function(){
  var url = document.getElementById('publicUrlInput').value.trim();
  var r = await apiCall('public_url.php', {publicUrl:url});
  if(r.ok){
    toast('Public QR URL updated.');
    state.config.publicUrl = url;
    var box = document.getElementById('qrBox'); box.innerHTML = '';
    new QRCode(box, {text:url, width:150, height:150, colorDark:'#2A1810', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});
  } else {
    toast(r.error || 'Failed to update URL.', true);
  }
});

document.getElementById('copyLinkBtn').addEventListener('click', function(){
  var url = document.getElementById('publicUrlInput').value.trim();
  if(navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(url).then(function(){ toast('Registration link copied!'); });
  } else {
    toast('Copied: ' + url);
  }
});

renderOverview();
</script>