<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Top Board Admin', 'topboard.php');
?>
  <div class="gate card" id="tbGate">
    <div class="emblem" style="border-color:var(--maroon); width:64px; height:64px; margin:0 auto 12px; background:#fff; padding:4px;">
      <img src="Logo/logo.png" alt="Sri Kalyani Dhamma School Crest">
    </div>
    <h2>Top Board &amp; Admin Panel</h2>
    <p class="sub">This area logs official points, deductions, and gate attendance. Enter your authorized passcode to unlock.</p>
    <form id="tbGateForm" onsubmit="return false;" autocomplete="off">
      <input type="text" name="username" value="topboard" autocomplete="username" style="display:none;" aria-hidden="true" tabindex="-1">
      <input type="password" id="tbPasscode" placeholder="Enter Passcode" style="text-align:center; margin-bottom:10px; width:100%; padding:10px; border-radius:7px; border:1.5px solid var(--parchment-line);" autocomplete="current-password">
      <button type="submit" class="btn btn-primary btn-block" id="tbUnlockBtn">Unlock Panel</button>
    </form>
    <p class="small" style="color:#A23B2E; margin-top:8px; display:none;" id="tbGateError">Incorrect passcode.</p>
    <div class="divider" style="margin:16px 0 12px;"></div>
    <a href="register.php" class="btn btn-ghost btn-sm" style="width:100%; text-decoration:none;">Go to Admin Sign-In Portal &rarr;</a>
  </div>

  <div id="tbPanel" style="display:none;">
    <!-- Gate Scanner Card -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:6px;">
        <div>
          <p class="eyebrow">Sunday Roll Call &amp; Special Events</p>
          <h2 style="margin:0;">Attendance Scanner</h2>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn btn-primary btn-sm" id="tbCameraToggleBtn">&#128247; Start Camera</button>
          <a href="portal/scanner.php" target="_blank" class="btn btn-ghost btn-sm" style="text-decoration:none;">Dedicated Kiosk &nearr;</a>
        </div>
      </div>
      <p class="sub">Point your camera at a prefect's QR code. Check-in is instantaneous; checkout enforces a 5-minute cooldown.</p>
      
      <div style="display:flex; gap:8px; margin-bottom:12px;">
        <button type="button" class="btn btn-sm" id="tbModeSunday" style="flex:1;">Sunday Roll Call</button>
        <button type="button" class="btn btn-ghost btn-sm" id="tbModeEvent" style="flex:1;">Special Event</button>
      </div>
      <div id="tbEventTitleWrap" style="display:none; margin-bottom:12px;">
        <input type="text" id="tbEventTitle" placeholder="Event Name (e.g. Katina Pinkama, Annual Pirith)" style="width:100%; padding:9px 12px; border-radius:7px; border:1px solid var(--parchment-line); font-size:13.5px;">
      </div>

      <div id="tbReaderWrap" style="display:none; margin-bottom:16px;">
        <div id="tbReader" style="width:100%; max-width:380px; margin:0 auto 12px; border-radius:8px; overflow:hidden; background:#000; min-height:220px;"></div>
        <div id="tbScanFeedback" style="text-align:center; font-family:var(--font-mono); font-size:12.5px; color:var(--ink-soft);">Camera active &mdash; point camera at prefect QR code.</div>
      </div>

      <div class="divider"></div>
      <h3>Or mark manually</h3>
      <div class="grid">
        <div class="field">
          <label for="attManualPrefect">Prefect</label>
          <select id="attManualPrefect"></select>
        </div>
      </div>
      <button class="btn btn-ghost btn-sm" id="attManualBtn">Record check-in / check-out</button>
      <div class="divider"></div>
      <h3>Today's Roll Call</h3>
      <div id="attendanceTodayList"></div>
    </div>

    <!-- Sunday Duty & Conduct Log -->
    <div class="card">
      <p class="eyebrow">Weekly Log</p>
      <h2>Log Sunday Duty &amp; Conduct</h2>
      <p class="sub">One entry per prefect per Sunday. Duty scores out of 50, conduct out of 15.</p>
      <div class="grid">
        <div class="field">
          <label for="dutyPrefect">Prefect</label>
          <select id="dutyPrefect"></select>
        </div>
        <div class="field">
          <label for="dutyDate">Date</label>
          <input type="date" id="dutyDate">
        </div>
      </div>
      <div class="grid" id="dutyFieldsGrid"></div>
      <div class="subtotal-live" id="dutySubtotal">Subtotal: 0 / 65</div>
      <div class="field" style="margin-top:12px;">
        <label for="dutyNote">Note (optional)</label>
        <input type="text" id="dutyNote" placeholder="e.g. led hall setup unprompted">
      </div>
      <button class="btn btn-primary" id="dutySubmitBtn">Save Sunday entry</button>
    </div>

    <!-- Extra Events Log -->
    <div class="card">
      <p class="eyebrow">As They Happen</p>
      <h2>Log Extra Event Dedication</h2>
      <p class="sub">Katina Perahera, Vesak Zone, Sil programs, cleaning drives, and similar. Scored out of 35.</p>
      <div class="grid">
        <div class="field">
          <label for="eventPrefect">Prefect</label>
          <select id="eventPrefect"></select>
        </div>
        <div class="field">
          <label for="eventDate">Date</label>
          <input type="date" id="eventDate">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label for="eventName">Event Name</label>
          <input type="text" id="eventName" placeholder="e.g. Vesak Zone setup">
        </div>
      </div>
      <div class="grid" id="eventFieldsGrid"></div>
      <div class="subtotal-live" id="eventSubtotal">Subtotal: 0 / 35</div>
      <button class="btn btn-primary" id="eventSubmitBtn">Save event entry</button>
    </div>

    <!-- Deductions Log -->
    <div class="card">
      <p class="eyebrow">Accountability</p>
      <h2>Log a Deduction</h2>
      <div class="grid">
        <div class="field">
          <label for="dedPrefect">Prefect</label>
          <select id="dedPrefect"></select>
        </div>
        <div class="field">
          <label for="dedType">Deduction Type</label>
          <select id="dedType"></select>
        </div>
        <div class="field">
          <label for="dedDate">Date</label>
          <input type="date" id="dedDate">
        </div>
        <div class="field" id="dedPointsField" style="display:none;">
          <label for="dedPoints">Points to deduct (negative, e.g. -7)</label>
          <input type="number" id="dedPoints" min="-10" max="-5" value="-5">
        </div>
      </div>
      <div class="field">
        <label for="dedNote">Note</label>
        <input type="text" id="dedNote" placeholder="Brief context for the register">
      </div>
      <button class="btn btn-danger" id="dedSubmitBtn">Record deduction</button>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <p class="eyebrow">Corrections</p>
      <h2>Recent Activity</h2>
      <p class="sub">Most recent 20 entries across the Guild. Remove anything logged in error.</p>
      <div id="recentActivityList"></div>
    </div>

    <!-- Executive Cycle Evaluation & Official Report Center (Principal Thero, Head Prefect, Top Board) -->
    <div class="card" id="tbReportCard" style="display:none; border:1.5px solid var(--saffron); background:linear-gradient(180deg, #fffdf8 0%, var(--parchment) 100%);">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
        <div>
          <p class="eyebrow" style="color:var(--saffron-deep);">&#128220; Guild Governance &amp; Official Records</p>
          <h2 style="margin:0;">Executive Cycle Evaluation Report</h2>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;" class="no-print">
          <button type="button" class="btn btn-saffron btn-sm" id="btnExportCsv">&#128229; Download Official CSV</button>
          <button type="button" class="btn btn-primary btn-sm" id="btnPrintReport">&#128438; Print / Save PDF</button>
        </div>
      </div>
      <p class="sub">Official evaluation ledger for <b>Principal Thero</b>, <b>Head Prefect</b>, and <b>Top Board</b>. Generates verified standings across Sunday duty, conduct, and extra events.</p>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; background:var(--parchment-deep); padding:10px 14px; border-radius:8px; border:1px solid var(--parchment-line);" class="no-print">
        <div style="flex:1; min-width:180px;">
          <label for="reportCycleSelect" style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Evaluation Cycle</label>
          <select id="reportCycleSelect" style="width:100%;"></select>
        </div>
        <div style="flex:1; min-width:160px;">
          <label for="reportTierSelect" style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Filter Tier</label>
          <select id="reportTierSelect" style="width:100%;">
            <option value="all">All Tiers</option>
            <option value="probation">Probation Leaders (Tier 1)</option>
            <option value="junior">Junior Leaders (Tier 2)</option>
            <option value="senior">Senior Prefects</option>
          </select>
        </div>
      </div>

      <!-- Executive Overview Stats -->
      <div class="stat-row" id="reportSummaryStats" style="margin-bottom:14px;">
        <div class="stat"><span class="n" id="statTotalPrefects">—</span><span class="l">Registered Prefects</span></div>
        <div class="stat"><span class="n" id="statAvgScore">—</span><span class="l">Average Score (/100)</span></div>
        <div class="stat"><span class="n" id="statTotalDuties">—</span><span class="l">Sunday Shifts</span></div>
        <div class="stat"><span class="n" id="statTotalEvents">—</span><span class="l">Events Logged</span></div>
        <div class="stat"><span class="n" id="statTotalDeductions">—</span><span class="l">Total Deductions</span></div>
      </div>

      <!-- Printable Report Container -->
      <div id="printReportSection">
        <div id="printHeader" style="display:none; border-bottom:2px solid #7A2333; padding-bottom:14px; margin-bottom:16px; text-align:center;">
          <div style="width:56px; height:56px; margin:0 auto 8px;"><img src="Logo/logo.png" alt="SKDS Emblem" style="width:100%; height:100%; object-fit:contain;"></div>
          <h2 style="margin:0 0 4px; font-size:20px; color:#5C1A26;">Sri Kalyani Dhamma School &middot; Kelaniya</h2>
          <h3 style="margin:0 0 4px; font-size:15px; font-weight:600; letter-spacing:0.05em;">PREFECT GUILD &mdash; OFFICIAL CYCLE MERIT REGISTER</h3>
          <p style="margin:0; font-size:12px; color:#555;" id="printCycleInfo">Evaluation Period: &hellip;</p>
        </div>

        <div id="reportTableWrap" style="overflow-x:auto;">Loading executive report&hellip;</div>

        <div id="printSignatures" style="display:none; margin-top:40px; padding-top:20px; border-top:1px dashed #bbb;">
          <div style="display:flex; justify-content:space-between; text-align:center; font-size:12px;">
            <div style="width:30%;">
              <div style="border-bottom:1px solid #000; margin-bottom:6px; height:40px;"></div>
              <b>Ven. Principal Thero</b><br>
              <span style="color:#555;">Chief Incumbent / Principal</span>
            </div>
            <div style="width:30%;">
              <div style="border-bottom:1px solid #000; margin-bottom:6px; height:40px;"></div>
              <b>Teacher-in-Charge</b><br>
              <span style="color:#555;">Prefect Guild Advisory Board</span>
            </div>
            <div style="width:30%;">
              <div style="border-bottom:1px solid #000; margin-bottom:6px; height:40px;"></div>
              <b>Head Prefect</b><br>
              <span style="color:#555;">Sri Kalyani Dhamma School</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Evaluation Cycles & Settings -->
    <div class="card">
      <p class="eyebrow">Settings</p>
      <h2>Evaluation Cycles &amp; Gate Rules</h2>
      <p class="sub" id="currentCycleInfo">Active: &hellip;</p>
      <div class="grid">
        <div class="field">
          <label for="lateCutoffInput">Late arrival cutoff (Sunday Roll Call)</label>
          <input type="time" id="lateCutoffInput" value="06:15">
        </div>
      </div>
      <button class="btn btn-ghost btn-sm" id="saveLateCutoffBtn">Save late cutoff</button>
    </div>
  </div>

<?php renderFooter(); ?>
<script>
var tbUnlocked = false;
var tbPasscode = '';
var activeAttMode = 'sunday';
var html5QrCode = null;
var scannerActive = false;

async function initTopBoard(){
  await initCommon();

  // Populate dynamic scoring forms
  var dfGrid = document.getElementById('dutyFieldsGrid');
  dfGrid.innerHTML = DUTY_FIELDS.map(function(f){
    return '<div class="field"><label for="df_'+f.key+'">'+esc(f.label)+' <span class="max">/ '+f.max+'</span></label>'+
      '<input type="number" id="df_'+f.key+'" min="0" max="'+f.max+'" value="'+f.max+'" class="duty-input"></div>';
  }).join('');

  var efGrid = document.getElementById('eventFieldsGrid');
  efGrid.innerHTML = EVENT_FIELDS.map(function(f){
    return '<div class="field"><label for="ef_'+f.key+'">'+esc(f.label)+' <span class="max">/ '+f.max+'</span></label>'+
      '<input type="number" id="ef_'+f.key+'" min="0" max="'+f.max+'" value="'+f.max+'" class="event-input"></div>';
  }).join('');

  var dedSel = document.getElementById('dedType');
  dedSel.innerHTML = DEDUCTION_TYPES.map(function(d){ return '<option value="'+d.key+'">'+esc(d.label)+'</option>'; }).join('');

  dedSel.addEventListener('change', function(){
    var dt = DEDUCTION_TYPES.find(function(x){ return x.key===dedSel.value; });
    document.getElementById('dedPointsField').style.display = (dt && dt.points === null) ? 'block' : 'none';
  });

  function updateDutySubtotal(){
    var sum = 0;
    document.querySelectorAll('.duty-input').forEach(function(i){ sum += parseInt(i.value||'0', 10); });
    document.getElementById('dutySubtotal').textContent = 'Subtotal: ' + sum + ' / 65';
  }
  document.querySelectorAll('.duty-input').forEach(function(i){ i.addEventListener('input', updateDutySubtotal); });
  updateDutySubtotal();

  function updateEventSubtotal(){
    var sum = 0;
    document.querySelectorAll('.event-input').forEach(function(i){ sum += parseInt(i.value||'0', 10); });
    document.getElementById('eventSubtotal').textContent = 'Subtotal: ' + sum + ' / 35';
  }
  document.querySelectorAll('.event-input').forEach(function(i){ i.addEventListener('input', updateEventSubtotal); });
  updateEventSubtotal();

  // Populate prefect dropdowns
  var activePrefects = state.prefects.filter(function(p){ return p.status === 'approved'; });
  var opts = activePrefects.map(function(p){ return '<option value="'+esc(p.id)+'">'+esc(p.name)+' ('+esc(tierInfo(p.tier).short)+')</option>'; }).join('');
  document.getElementById('dutyPrefect').innerHTML = opts;
  document.getElementById('eventPrefect').innerHTML = opts;
  document.getElementById('dedPrefect').innerHTML = opts;
  document.getElementById('attManualPrefect').innerHTML = opts;

  document.getElementById('dutyDate').value = todayStr();
  document.getElementById('eventDate').value = todayStr();
  document.getElementById('dedDate').value = todayStr();

  // Check existing session
  var session = await apiGet('session.php');
  if(session && session.authenticated && session.user.type === 'admin'){
    unlockPanel('');
  }
}

async function submitUnlockGate(){
  var pass = document.getElementById('tbPasscode').value.trim();
  if(!pass){ return; }
  var r = await apiCall('auth.php', {passcode: pass});
  if(r.ok){
    tbPasscode = pass;
    document.getElementById('tbGateError').style.display = 'none';
    unlockPanel(pass);
  } else {
    document.getElementById('tbGateError').style.display = 'block';
  }
}

var tbForm = document.getElementById('tbGateForm');
if(tbForm){
  tbForm.addEventListener('submit', function(e){
    e.preventDefault();
    submitUnlockGate();
  });
}
document.getElementById('tbUnlockBtn').addEventListener('click', function(e){
  e.preventDefault();
  submitUnlockGate();
});

function unlockPanel(pass){
  tbUnlocked = true;
  document.getElementById('tbGate').style.display = 'none';
  document.getElementById('tbPanel').style.display = 'block';
  renderAttendanceToday();
  renderRecentActivity();
  renderCycleSettings();
  initReportSection();
}

// Scanner Modes
document.getElementById('tbModeSunday').addEventListener('click', function(){
  activeAttMode = 'sunday';
  this.className = 'btn btn-sm';
  document.getElementById('tbModeEvent').className = 'btn btn-ghost btn-sm';
  document.getElementById('tbEventTitleWrap').style.display = 'none';
});

document.getElementById('tbModeEvent').addEventListener('click', function(){
  activeAttMode = 'event';
  this.className = 'btn btn-sm';
  document.getElementById('tbModeSunday').className = 'btn btn-ghost btn-sm';
  document.getElementById('tbEventTitleWrap').style.display = 'block';
});

// Camera Scanner
document.getElementById('tbCameraToggleBtn').addEventListener('click', function(){
  if(scannerActive){
    stopScanner();
    this.textContent = '📷 Start Camera';
    document.getElementById('tbReaderWrap').style.display = 'none';
  } else {
    startScanner();
    this.textContent = '⏹ Stop Camera';
    document.getElementById('tbReaderWrap').style.display = 'block';
  }
});

function startScanner(){
  html5QrCode = new Html5Qrcode('tbReader');
  scannerActive = true;
  html5QrCode.start(
    {facingMode: 'environment'},
    {fps: 6, qrbox: {width: 250, height: 250}},
    async function(decodedText){
      document.getElementById('tbScanFeedback').textContent = 'Scanned: ' + decodedText;
      var eventTitle = activeAttMode === 'event' ? document.getElementById('tbEventTitle').value.trim() : null;
      var res = await apiCall('attendance.php', {token: decodedText, mode: activeAttMode, eventName: eventTitle});
      if((res.ok || res.status === 'success' || res.status === 'warning' || res.status === 'info' || res.action) && !res.error){
        toast(res.message || 'Attendance recorded.');
        await initCommon();
        renderAttendanceToday();
      } else {
        toast(res.error || 'Attendance rejected.', true);
      }
    },
    function(err){}
  ).catch(function(err){
    toast('Camera error: ' + err, true);
  });
}

function stopScanner(){
  if(html5QrCode && scannerActive){
    html5QrCode.stop().then(function(){ html5QrCode.clear(); }).catch(function(){});
    scannerActive = false;
  }
}

// Manual Attendance
document.getElementById('attManualBtn').addEventListener('click', async function(){
  var pid = document.getElementById('attManualPrefect').value;
  var eventTitle = activeAttMode === 'event' ? document.getElementById('tbEventTitle').value.trim() : null;
  var r = await apiCall('attendance.php', {token: pid, mode: activeAttMode, eventName: eventTitle});
  if((r.ok || r.status === 'success' || r.status === 'warning' || r.status === 'info' || r.action) && !r.error){
    toast(r.message || 'Attendance saved.');
    await initCommon();
    renderAttendanceToday();
  } else {
    toast(r.error || 'Could not save attendance.', true);
  }
});

function renderAttendanceToday(){
  var list = document.getElementById('attendanceTodayList');
  var today = todayStr();
  var todays = state.attendance.filter(function(a){ return a.date===today; });
  if(!todays.length){ list.innerHTML = '<div class="empty-state">No check-ins yet today.</div>'; return; }
  list.innerHTML = todays.map(function(a){
    var p = state.prefects.find(function(x){ return x.id===a.prefectId; });
    return '<div class="entry-row"><span>'+esc(p?p.name:'Unknown')+
      (a.late?' <span class="tier-pill" style="background:var(--maroon-tint);color:#A23B2E;">LATE</span>':'')+
      '<br><span class="meta">In '+fmtTimeNice(a.checkIn)+(a.checkOut?(' &middot; Out '+fmtTimeNice(a.checkOut)):' &middot; not checked out')+'</span></span></div>';
  }).join('');
}

// Submit Duty Entry
document.getElementById('dutySubmitBtn').addEventListener('click', async function(){
  var pid = document.getElementById('dutyPrefect').value;
  var date = document.getElementById('dutyDate').value || todayStr();
  var note = document.getElementById('dutyNote').value.trim();
  var vals = {};
  DUTY_FIELDS.forEach(function(f){ vals[f.key] = parseInt(document.getElementById('df_'+f.key).value||'0', 10); });
  var r = await apiCall('entries.php', {type:'duty', prefectId:pid, date:date, note:note, values:vals, passcode:tbPasscode});
  if(r.ok){
    toast('Sunday duty entry saved.');
    document.getElementById('dutyNote').value = '';
    await initCommon();
    renderRecentActivity();
  } else {
    toast(r.error || 'Could not save entry.', true);
  }
});

// Submit Event Entry
document.getElementById('eventSubmitBtn').addEventListener('click', async function(){
  var pid = document.getElementById('eventPrefect').value;
  var date = document.getElementById('eventDate').value || todayStr();
  var evName = document.getElementById('eventName').value.trim();
  if(!evName){ toast('Please provide an event name.', true); return; }
  var vals = {};
  EVENT_FIELDS.forEach(function(f){ vals[f.key] = parseInt(document.getElementById('ef_'+f.key).value||'0', 10); });
  var r = await apiCall('entries.php', {type:'event', prefectId:pid, date:date, eventName:evName, values:vals, passcode:tbPasscode});
  if(r.ok){
    toast('Event entry saved.');
    document.getElementById('eventName').value = '';
    await initCommon();
    renderRecentActivity();
  } else {
    toast(r.error || 'Could not save event entry.', true);
  }
});

// Submit Deduction
document.getElementById('dedSubmitBtn').addEventListener('click', async function(){
  var pid = document.getElementById('dedPrefect').value;
  var type = document.getElementById('dedType').value;
  var date = document.getElementById('dedDate').value || todayStr();
  var note = document.getElementById('dedNote').value.trim();
  var dt = DEDUCTION_TYPES.find(function(x){ return x.key===type; });
  var pts = (dt && dt.points !== null) ? dt.points : parseInt(document.getElementById('dedPoints').value||'-5', 10);
  var r = await apiCall('deductions.php', {prefectId:pid, type:type, date:date, points:pts, note:note, passcode:tbPasscode});
  if(r.ok){
    toast('Deduction recorded.');
    document.getElementById('dedNote').value = '';
    await initCommon();
    renderRecentActivity();
  } else {
    toast(r.error || 'Could not record deduction.', true);
  }
});

// Recent Activity Ledger
function renderRecentActivity(){
  var list = document.getElementById('recentActivityList');
  var items = state.entries.map(function(e){
    var p = state.prefects.find(function(x){return x.id===e.prefectId;});
    var pts = e.type==='duty' ? (e.values.punctuality+e.values.uniform+e.values.execution+e.values.initiative+e.values.buddhistValues+e.values.teamSynergy) : (e.values.eventAttendance+e.values.taskOwnership+e.values.problemSolving);
    return {loggedAt:e.loggedAt, label:(p?p.name:'Unknown')+' — '+(e.type==='duty'?'Sunday Duty':('Event: '+(e.eventName||''))), date:e.date, pts:pts, positive:true, id:e.id, type:'entry'};
  }).concat(state.deductions.map(function(d){
    var p = state.prefects.find(function(x){return x.id===d.prefectId;});
    return {loggedAt:d.loggedAt, label:(p?p.name:'Unknown')+' — Deduction: '+d.type, date:d.date, pts:d.points, positive:false, id:d.id, type:'deduction'};
  }));
  items.sort(function(a,b){ return (b.loggedAt||'').localeCompare(a.loggedAt||''); });
  items = items.slice(0, 20);
  if(!items.length){ list.innerHTML = '<div class="empty-state">Nothing logged yet.</div>'; return; }
  list.innerHTML = items.map(function(it){
    return '<div class="entry-row"><span>'+esc(it.label)+'<br><span class="meta">'+fmtDateNice(it.date)+'</span></span>'+
      '<span style="display:flex; align-items:center; gap:10px;"><span class="pts" style="color:'+(it.positive?'var(--positive)':'#A23B2E')+'">'+(it.positive?'+':'')+round1(it.pts)+'</span>'+
      '<button class="btn btn-ghost btn-sm" data-type="'+it.type+'" data-id="'+it.id+'">Remove</button></span></div>';
  }).join('');
  list.querySelectorAll('button[data-type]').forEach(function(btn){
    btn.addEventListener('click', async function(){
      var ep = btn.dataset.type === 'entry' ? 'entries.php' : 'deductions.php';
      btn.disabled = true;
      var r = await apiCall(ep, {id: btn.dataset.id, passcode: tbPasscode}, 'DELETE');
      if(r.ok){
        toast('Record removed.');
        await initCommon();
        renderRecentActivity();
      } else {
        toast(r.error || 'Failed to remove.', true);
        btn.disabled = false;
      }
    });
  });
}

function renderCycleSettings(){
  var c = currentCycle();
  if(c) document.getElementById('currentCycleInfo').textContent = 'Active: ' + cycleLabel(c);
  document.getElementById('lateCutoffInput').value = state.config.lateCutoff || '06:15';
}

document.getElementById('saveLateCutoffBtn').addEventListener('click', async function(){
  var v = document.getElementById('lateCutoffInput').value;
  var r = await apiCall('settings.php', {lateCutoff: v, passcode: tbPasscode});
  if(r.ok){
    toast('Cutoff updated to ' + fmtTimeNice(v));
    state.config.lateCutoff = v;
  } else {
    toast(r.error || 'Failed to update cutoff.', true);
  }
});

// Executive Report Engine
async function initReportSection(){
  var session = await apiGet('session.php');
  if(!session || !session.authenticated || session.user.type !== 'admin') return;

  var allowedRoles = ['principal', 'head_prefect', 'top_board', 'developer'];
  var role = session.user.role || '';
  var card = document.getElementById('tbReportCard');

  if(!allowedRoles.includes(role)){
    if(card) card.style.display = 'none';
    return;
  }

  if(card) card.style.display = 'block';

  var cycleSel = document.getElementById('reportCycleSelect');
  var cur = currentCycle();
  if(state.cycles && state.cycles.length){
    cycleSel.innerHTML = state.cycles.map(function(c){
      var isSel = (cur && cur.id === c.id) ? ' selected' : '';
      return '<option value="' + c.id + '"' + isSel + '>' + esc(c.label) + ' (' + fmtDateNice(c.start || c.startDate) + ' – ' + fmtDateNice(c.end || c.endDate) + ')</option>';
    }).join('');
  } else {
    cycleSel.innerHTML = '<option value="">All Cycles</option>';
  }

  cycleSel.onchange = loadExecutiveReport;
  document.getElementById('reportTierSelect').onchange = loadExecutiveReport;

  document.getElementById('btnExportCsv').onclick = function(){
    var cId = document.getElementById('reportCycleSelect').value;
    var tF = document.getElementById('reportTierSelect').value;
    window.location.href = 'api/report.php?format=csv&cycle_id=' + encodeURIComponent(cId) + '&tier=' + encodeURIComponent(tF);
  };

  document.getElementById('btnPrintReport').onclick = function(){
    var pHeader = document.getElementById('printHeader');
    var pSigs = document.getElementById('printSignatures');
    if(pHeader) pHeader.style.display = 'block';
    if(pSigs) pSigs.style.display = 'block';
    window.print();
    setTimeout(function(){
      if(pHeader) pHeader.style.display = 'none';
      if(pSigs) pSigs.style.display = 'none';
    }, 1000);
  };

  loadExecutiveReport();
}

async function loadExecutiveReport(){
  var cId = document.getElementById('reportCycleSelect').value;
  var tF = document.getElementById('reportTierSelect').value;
  var wrap = document.getElementById('reportTableWrap');
  if(!wrap) return;
  wrap.innerHTML = '<div class="muted small" style="padding:12px 0;">Loading evaluation data&hellip;</div>';

  var res = await apiGet('report.php?cycle_id=' + encodeURIComponent(cId) + '&tier=' + encodeURIComponent(tF));
  if(!res || !res.ok){
    wrap.innerHTML = '<div class="empty-state">Could not generate executive report or unauthorized.</div>';
    return;
  }

  var sum = res.summary || {};
  document.getElementById('statTotalPrefects').textContent = sum.totalPrefects || 0;
  document.getElementById('statAvgScore').textContent = sum.averageScore || '0.0';
  document.getElementById('statTotalDuties').textContent = sum.totalDuties || 0;
  document.getElementById('statTotalEvents').textContent = sum.totalEvents || 0;
  document.getElementById('statTotalDeductions').textContent = sum.totalDeductions || 0;

  var pCycle = document.getElementById('printCycleInfo');
  if(pCycle && res.cycle){
    pCycle.textContent = 'Evaluation Period: ' + (res.cycle.label || '') + ' (' + fmtDateNice(res.cycle.start) + ' to ' + fmtDateNice(res.cycle.end) + ')';
  }

  if(!res.records || !res.records.length){
    wrap.innerHTML = '<div class="empty-state">No prefect records found for this cycle and filter.</div>';
    return;
  }

  var html = '<table class="ledger" style="width:100%;"><thead><tr>' +
    '<th>Rank</th><th>Prefect ID</th><th>Full Name</th><th>Tier &amp; Class</th>' +
    '<th style="text-align:right;">Sunday Duty (/50)</th>' +
    '<th style="text-align:right;">Events (/35)</th>' +
    '<th style="text-align:right;">Conduct (/15)</th>' +
    '<th style="text-align:right;">Deductions</th>' +
    '<th style="text-align:right;">Net Score (/100)</th>' +
    '<th style="text-align:center;">Status</th>' +
    '</tr></thead><tbody>';

  res.records.forEach(function(r){
    var dedStyle = r.dedTotal < 0 ? 'color:#A23B2E; font-weight:600;' : 'color:var(--ink-soft);';
    html += '<tr>' +
      '<td style="font-weight:700; font-family:var(--font-mono);">' + (r.rank || '—') + '</td>' +
      '<td style="font-family:var(--font-mono); font-size:11px;">' + esc(r.id) + '</td>' +
      '<td><b>' + esc(r.name) + '</b></td>' +
      '<td><span class="tier-pill ' + r.tier + '">' + esc(tierInfo(r.tier).short) + '</span><br><span class="small muted">' + esc(r.grade || '—') + '</span></td>' +
      '<td style="text-align:right; font-family:var(--font-mono);">' + round1(r.dutyAvg) + ' <span class="small muted">(' + r.dutyCount + ')</span></td>' +
      '<td style="text-align:right; font-family:var(--font-mono);">' + round1(r.eventAvg) + ' <span class="small muted">(' + r.eventCount + ')</span></td>' +
      '<td style="text-align:right; font-family:var(--font-mono);">' + round1(r.conductAvg) + '</td>' +
      '<td style="text-align:right; font-family:var(--font-mono); ' + dedStyle + '">' + (r.dedTotal < 0 ? r.dedTotal : '0') + '</td>' +
      '<td style="text-align:right; font-family:var(--font-mono); font-weight:700; color:var(--maroon-deep);">' + round1(r.finalScore) + '</td>' +
      '<td style="text-align:center;"><span class="status-pill ' + r.status + '">' + esc(r.status.toUpperCase()) + '</span></td>' +
      '</tr>';
  });

  html += '</tbody></table>';
  wrap.innerHTML = html;
}

initTopBoard();
</script>