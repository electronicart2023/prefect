<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Leaderboard', 'leaderboard.php');
?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
      <div>
        <p class="eyebrow">Two-month evaluation cycle</p>
        <h2 style="margin:0;">Leaderboard</h2>
      </div>
      <div>
        <select id="lbTierFilter" style="font-size:13px; padding:6px 10px; border-radius:6px; border:1px solid var(--parchment-line); background:#fff;">
          <option value="all">All active tiers</option>
          <option value="probation">Probation Leaders only</option>
          <option value="junior">Junior Leaders only</option>
        </select>
      </div>
    </div>
    <p class="sub" id="lbCycleLabel">&nbsp;</p>
    <div id="lbTableWrap">Loading&hellip;</div>
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
  return {dutyAvg:dutyAvg, conductAvg:conductAvg, eventAvg:eventAvg, base:base, dedTotal:dedTotal, final: Math.max(0, base+dedTotal)};
}

function computeLeaderboard(cycleId, tierFilter){
  var scored = state.prefects.filter(function(p){ return TIERS[p.tier] && TIERS[p.tier].scored; });
  var rows = scored.filter(function(p){ return tierFilter==='all'||p.tier===tierFilter; })
    .map(function(p){ return {p:p, s:computeCycleScore(p.id, cycleId)}; });
  rows.sort(function(a,b){ return b.s.final-a.s.final; });
  return rows;
}

function renderLeaderboard(){
  var c = currentCycle();
  document.getElementById('lbCycleLabel').textContent = cycleLabel(c);
  var rows = computeLeaderboard(c ? c.id : null, document.getElementById('lbTierFilter').value);
  var wrap = document.getElementById('lbTableWrap');
  if(!rows.length){ wrap.innerHTML = '<div class="empty-state">No registered prefects in this view yet.</div>'; return; }
  var html = '<table class="ledger"><thead><tr><th>#</th><th>Prefect</th><th>Tier</th><th style="text-align:right">Duty</th><th style="text-align:right">Events</th><th style="text-align:right">Conduct</th><th style="text-align:right">Total</th></tr></thead><tbody>';
  rows.forEach(function(r,i){
    html += '<tr><td><span class="rank'+(i===0&&r.s.final>0?' gold':'')+'">'+(i+1)+'</span></td>'+
      '<td class="name-cell"><b>'+esc(r.p.name)+'</b><br><span class="muted small">'+esc(r.p.grade||'—')+'</span></td>'+
      '<td><span class="tier-pill '+esc(r.p.tier)+'">'+esc(tierInfo(r.p.tier).short)+'</span></td>'+
      '<td style="text-align:right" class="pts">'+round1(r.s.dutyAvg)+'</td>'+
      '<td style="text-align:right" class="pts">'+round1(r.s.eventAvg)+'</td>'+
      '<td style="text-align:right" class="pts">'+round1(r.s.conductAvg)+'</td>'+
      '<td style="text-align:right" class="pts">'+round1(r.s.final)+'</td></tr>';
  });
  wrap.innerHTML = '<div class="table-wrap">'+html+'</tbody></table></div>';
}

document.getElementById('lbTierFilter').addEventListener('change', renderLeaderboard);
initCommon().then(renderLeaderboard);
</script>