<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Prefect Profile', 'profile.php');
?>
  <div id="profileAdminNav" style="display:none; margin-bottom:14px;">
    <a href="prefects.php" class="btn btn-ghost btn-sm" style="text-decoration:none;">&larr; Back to Prefects Directory</a>
  </div>
  <div id="profileContainer">
    <div class="card" style="text-align:center; padding:30px;">
      <p class="eyebrow">Checking Authentication</p>
      <h2>Loading Profile&hellip;</h2>
    </div>
  </div>

<?php renderFooter(); ?>
<script>
function computeCycleScore(prefectId, cycleId){
  var mine = state.entries.filter(function(e){ var c=cycleForDate(e.date); return e.prefectId===prefectId && c && c.id===cycleId; });
  var dutyEntries = mine.filter(function(e){ return e.type==='duty'; });
  var eventEntries = mine.filter(function(e){ return e.type==='event'; });
  var dutyAvg = avg(dutyEntries.map(function(e){ return e.values.punctuality+e.values.uniform+e.values.execution+e.values.initiative; }));
  var conductAvg = avg(dutyEntries.map(function(e){ return e.values.buddhistValues+e.values.teamSynergy; }));
  var eventAvg = avg(eventEntries.map(function(e){ return e.values.eventAttendance+e.values.taskOwnership+e.values.problemSolving; }));
  var base = dutyAvg+conductAvg+eventAvg;
  var myDed = state.deductions.filter(function(d){ var c=cycleForDate(d.date); return d.prefectId===prefectId && c && c.id===cycleId; });
  var dedTotal = myDed.reduce(function(s,d){ return s+d.points; },0);
  return {dutyAvg:dutyAvg, conductAvg:conductAvg, eventAvg:eventAvg, base:base, dedTotal:dedTotal, final: Math.max(0, base+dedTotal), dedCount:myDed.length, dutyCount:dutyEntries.length, eventCount:eventEntries.length};
}

function computeLeaderboard(cycleId, tierFilter){
  var scored = state.prefects.filter(function(p){ return TIERS[p.tier] && TIERS[p.tier].scored; });
  var rows = scored.filter(function(p){ return tierFilter==='all'||p.tier===tierFilter; })
    .map(function(p){ return {p:p, s:computeCycleScore(p.id, cycleId)}; });
  rows.sort(function(a,b){ return b.s.final-a.s.final; });
  return rows;
}

function renderProfile(prefect, isAdminView){
  var c = currentCycle();
  var s = computeCycleScore(prefect.id, c ? c.id : null);
  var lb = computeLeaderboard(c ? c.id : null, prefect.tier);
  var rank = lb.findIndex(function(r){ return (r.p.id||'').toLowerCase() === (prefect.id||'').toLowerCase(); }) + 1;

  var mine = state.entries.filter(function(e){ var cx=cycleForDate(e.date); return (e.prefectId||'').toLowerCase() === (prefect.id||'').toLowerCase() && cx && cx.id===(c?c.id:null); });
  var myDed = state.deductions.filter(function(d){ var cx=cycleForDate(d.date); return (d.prefectId||'').toLowerCase() === (prefect.id||'').toLowerCase() && cx && cx.id===(c?c.id:null); });

  var allLogs = mine.map(function(e){
    var pts = e.type==='duty' ? (e.values.punctuality+e.values.uniform+e.values.execution+e.values.initiative+e.values.buddhistValues+e.values.teamSynergy) : (e.values.eventAttendance+e.values.taskOwnership+e.values.problemSolving);
    return {date:e.date, kind:e.type==='duty'?'Sunday Duty':'Event: '+(e.eventName||''), pts:pts, positive:true};
  }).concat(myDed.map(function(d){
    return {date:d.date, kind:'Deduction: ' + d.type, pts:d.points, positive:false};
  }));
  allLogs.sort(function(a,b){ return a.date<b.date?1:-1; });

  var logRows = allLogs.map(function(r){
    return '<div class="entry-row"><span>'+esc(r.kind)+'<br><span class="meta">'+fmtDateNice(r.date)+'</span></span><span class="pts" style="color:'+(r.positive?'var(--positive)':'#A23B2E')+'">'+(r.positive?'+':'')+round1(r.pts)+'</span></div>';
  });

  var container = document.getElementById('profileContainer');
  container.innerHTML =
    '<div class="card"><p class="eyebrow">'+esc(cycleLabel(c))+(isAdminView ? ' &bull; <b>Viewing as Administrator</b>' : '')+'</p>'+
    '<h2 style="margin-bottom:2px;">'+esc(prefect.name)+'<span class="tier-pill '+esc(prefect.tier)+'" style="margin-left:10px;">'+esc(tierInfo(prefect.tier).short)+'</span></h2>'+
    '<p class="sub">Rank '+(rank?rank:'—')+' of '+lb.length+' in tier this cycle. Registered Class: <b>'+esc(prefect.grade||'—')+'</b> &bull; Prefect ID: <b>'+esc(prefect.id)+'</b></p>'+
    '<p class="eyebrow" style="margin-top:14px;">Sunday Roll Call QR Code</p>'+
    '<div class="qr-box" id="profileQrBox" style="display:inline-block; margin-bottom:12px;"></div>'+
    '<div class="stat-row">'+
      '<div class="stat"><span class="n">'+round1(s.final)+'</span><span class="l">Points (of 100)</span></div>'+
      '<div class="stat"><span class="n">'+round1(s.dutyAvg)+'</span><span class="l">Sunday Duty (of 50)</span></div>'+
      '<div class="stat"><span class="n">'+round1(s.eventAvg)+'</span><span class="l">Extra Events (of 35)</span></div>'+
      '<div class="stat"><span class="n">'+round1(s.conductAvg)+'</span><span class="l">Conduct (of 15)</span></div>'+
    '</div>'+
    '<div class="divider"></div>'+
    '<h3>Activity Breakdown</h3>'+
    '<div class="entry-list">'+(logRows.join('') || '<div class="empty-state">No duty or event records yet this cycle.</div>')+'</div>'+
    '</div>';

  var qb = document.getElementById('profileQrBox');
  if(qb){
    qb.innerHTML = '';
    var qrData = prefect.qrToken || prefect.id;
    new QRCode(qb, {text: qrData, width:130, height:130, colorDark:'#2A1810', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});
  }
}

async function initProfile(){
  await initCommon();
  var session = await apiGet('session.php');
  var params = new URLSearchParams(window.location.search);
  var viewId = params.get('id');

  // 1. If viewing as Admin with a target prefect ID
  if(session && session.authenticated && session.user.type === 'admin'){
    var adminNav = document.getElementById('profileAdminNav');
    if(adminNav) adminNav.style.display = 'block';

    if(viewId){
      var target = state.prefects.find(function(p){ return (p.id||'').toLowerCase() === viewId.toLowerCase(); });
      if(target){
        renderProfile(target, true);
        return;
      }
    }
  }

  // 2. If logged in as Prefect via Server Session
  if(session && session.authenticated && session.user.type === 'prefect'){
    var cur = state.prefects.find(function(p){ return (p.id||'').toLowerCase() === (session.user.id||'').toLowerCase(); });
    var localData = null;
    try{ localData = JSON.parse(sessionStorage.getItem('skds_prefect') || localStorage.getItem('skds_prefect') || 'null'); }catch(e){}
    var prefectObj = cur ? Object.assign({}, cur) : {
      id: session.user.id,
      name: session.user.name,
      tier: session.user.role || 'probation',
      grade: (localData && localData.grade) || '',
      status: 'approved'
    };
    prefectObj.qrToken = session.user.qrToken || (localData && localData.qrToken) || prefectObj.qrToken || prefectObj.id;
    renderProfile(prefectObj, false);
    return;
  }

  // 3. Fallback: Prefect sign-in via sessionStorage or localStorage
  var localPrefect = sessionStorage.getItem('skds_prefect') || localStorage.getItem('skds_prefect');
  if(localPrefect){
    try{
      var p = JSON.parse(localPrefect);
      if(p && p.id){
        var curMatch = state.prefects.find(function(x){ return (x.id||'').toLowerCase() === p.id.toLowerCase(); });
        var targetMatch = curMatch ? Object.assign({}, curMatch) : p;
        targetMatch.qrToken = p.qrToken || targetMatch.qrToken || targetMatch.id;
        renderProfile(targetMatch, false);
        return;
      }
    }catch(e){}
  }

  // If unauthorized
  toast('Please sign in to view your profile.', true);
  document.getElementById('profileContainer').innerHTML =
    '<div class="card" style="text-align:center; padding:36px 20px;">' +
    '  <div class="emblem" style="margin:0 auto 12px; width:54px; height:54px; background:#fff; padding:3px;"><img src="Logo/logo.png" alt="SKDS Crest"></div>' +
    '  <h2>Authentication Required</h2>' +
    '  <p class="sub">Prefect profiles are private. Please sign in with your Name/ID and 4-digit PIN.</p>' +
    '  <a href="register.php" class="btn btn-primary">Go to Sign-In Portal &rarr;</a>' +
    '</div>';
  setTimeout(function(){ window.location.href = 'register.php'; }, 1500);
}

initProfile();
</script>