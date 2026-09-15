<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Awards & Recognition', 'awards.php');
?>
  <div class="card">
    <p class="eyebrow">End of cycle</p>
    <h2 style="margin-bottom:2px;">Awards &amp; Recognition</h2>
    <p class="sub" id="awardsCycleLabel">&nbsp;</p>
  </div>
  <div class="grid" id="awardsGrid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));"></div>

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
  var hasUnexcused = myDed.some(function(d){ return d.type==='unexcused_duty'||d.type==='unexcused_event'; });
  return {dutyAvg:dutyAvg, conductAvg:conductAvg, eventAvg:eventAvg, base:base, dedTotal:dedTotal,
    final: Math.max(0, base+dedTotal), dutyCount:dutyEntries.length, eventCount:eventEntries.length,
    hasUnexcused:hasUnexcused};
}

function computeAwards(cycleId){
  var scored = state.prefects.filter(function(p){ return TIERS[p.tier] && TIERS[p.tier].scored; });
  var rows = scored.map(function(p){ return {p:p, s:computeCycleScore(p.id, cycleId)}; });
  var active = rows.filter(function(r){ return r.s.dutyCount>0 || r.s.eventCount>0; });
  function topOf(arr){
    if(!arr.length) return [];
    var max = Math.max.apply(null, arr.map(function(r){ return r.s.final; }));
    if(max<=0) return [];
    return arr.filter(function(r){ return r.s.final===max; }).map(function(r){ return r.p; });
  }
  var probation = active.filter(function(r){ return r.p.tier==='probation'; });
  var junior = active.filter(function(r){ return r.p.tier==='junior'; });
  var commitment = active.filter(function(r){ return !r.s.hasUnexcused && r.s.dutyAvg>=45; }).map(function(r){ return r.p; });
  return {bestProbation:topOf(probation), bestJunior:topOf(junior), overallBest:topOf(active), commitment:commitment};
}

function renderAwards(){
  var c = currentCycle();
  document.getElementById('awardsCycleLabel').textContent = cycleLabel(c);
  var a = computeAwards(c ? c.id : null);
  function block(title, winners, sub){
    var names = winners.length ? winners.map(function(p){ return '<div class="award-winner">'+esc(p.name)+'<span class="tier-pill '+p.tier+'">'+esc(tierInfo(p.tier).short)+'</span></div>'; }).join('') : '<p class="empty-state">Not yet decided — awaiting entries.</p>';
    return '<div class="award-card"><div class="seal">SKDS</div><p class="eyebrow">'+esc(title)+'</p><p class="sub" style="margin-bottom:10px;">'+esc(sub)+'</p>'+names+'</div>';
  }
  document.getElementById('awardsGrid').innerHTML =
    block('Best Probation Leader of the Term', a.bestProbation, 'Highest total points, Tier 1')+
    block('Best Junior Leader of the Term', a.bestJunior, 'Highest total points, Tier 2')+
    block('Overall Best Prefect', a.overallBest, 'Highest points across both active tiers')+
    block('100% Commitment Award', a.commitment, 'Zero unexcused absences and Sunday Duty ≥ 45/50 (Top Board threshold)');
}

initCommon().then(renderAwards);
</script>