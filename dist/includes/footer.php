<?php
declare(strict_types=1);

/**
 * Sri Kalyani Dhamma School — Prefect Guild Merit System
 * Shared Footer Template
 * Compatible with PHP 7.4.33
 */

function renderFooter(): void {
?>
  <footer>Sri Kalyani Dhamma School &middot; Prefect Guild Merit System &middot; Built By Prefects In The Dhamma School</footer>
</div>
<div class="toast" id="toast"></div>

<script>
// State & constants
var csrfToken = null;
var state = {config:{}, prefects:[], attendance:[], entries:[], deductions:[], cycles:[]};

var TIERS = {
  probation: {name:'Probation Leader (Tier 1)', short:'Probation', scored:true},
  junior:    {name:'Junior Leader (Tier 2)', short:'Junior', scored:true},
  senior:    {name:'Senior Prefect (Top Board)', short:'Senior', scored:false}
};

var DUTY_FIELDS = [
  {key:'punctuality', label:'Punctuality & Morning Setup', max:15},
  {key:'uniform', label:'Uniform & Demeanour', max:10},
  {key:'execution', label:'Duty Execution & Vigilance', max:15},
  {key:'initiative', label:'Initiative & Problem-Solving', max:10},
  {key:'buddhistValues', label:'Buddhist Values & Respectful Speech', max:10},
  {key:'teamSynergy', label:'Team Synergy & Helping Juniors', max:5}
];

var EVENT_FIELDS = [
  {key:'eventAttendance', label:'Attendance & Promptness', max:15},
  {key:'taskOwnership', label:'Task Ownership & Reliability', max:15},
  {key:'problemSolving', label:'Calmness & Problem-Solving', max:5}
];

var DEDUCTION_TYPES = [
  {key:'unexcused_duty', label:'Unexcused absence — Sunday duty (-5 pts)', points:-5},
  {key:'unexcused_event', label:'Unexcused absence — scheduled extra event (-5 pts)', points:-5},
  {key:'late_arrival', label:'Late arrival to Sunday duty without notice (-2 pts)', points:-2},
  {key:'uniform_breach', label:'Uniform or grooming breach after warning (-2 pts)', points:-2},
  {key:'misconduct', label:'Disrespectful speech or conduct breach (-5 to -10 pts)', points:null}
];

function toast(msg, isErr){
  var t = document.getElementById('toast');
  if(!t) return;
  t.textContent = msg;
  t.className = 'toast show' + (isErr ? ' err' : '');
  setTimeout(function(){ t.className = 'toast'; }, 3200);
}

function esc(s){
  if(s === null || s === undefined) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function todayStr(){
  var d = new Date();
  var off = d.getTimezoneOffset();
  var sl = new Date(d.getTime() + (330 + off)*60000);
  return sl.toISOString().slice(0,10);
}

function round1(n){ return Math.round((n||0)*10)/10; }
function avg(arr){ return arr.length ? arr.reduce(function(a,b){return a+b;},0)/arr.length : 0; }

function cycleForDate(dateStr){
  if(!state.cycles || !state.cycles.length) return null;
  return state.cycles.find(function(c){
    var s = c.start || c.startDate || '';
    var e = c.end || c.endDate || '';
    return dateStr >= s && dateStr <= e;
  }) || null;
}

function currentCycle(){
  var t = todayStr();
  return state.cycles.find(function(c){
    var s = c.start || c.startDate || '';
    var e = c.end || c.endDate || '';
    return t >= s && t <= e;
  }) || (state.cycles.length ? state.cycles[state.cycles.length-1] : null);
}

function cycleLabel(c){
  if(!c) return 'No Active Cycle';
  var s = c.start || c.startDate || '';
  var e = c.end || c.endDate || '';
  if(!s || !e) return c.label || 'Active Cycle';
  return (c.label ? c.label + ' (' : '') + fmtDateNice(s) + ' \u2013 ' + fmtDateNice(e) + (c.label ? ')' : '');
}

function tierInfo(k){ return TIERS[k] || {name:k, short:k, scored:false}; }

function fmtDateNice(dStr){
  if(!dStr) return '—';
  var d = new Date(dStr+'T00:00:00');
  return d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric', year:'numeric'});
}

function fmtTimeNice(tStr){
  if(!tStr) return '—';
  var p = tStr.split(':');
  var h = parseInt(p[0], 10), m = p[1] || '00';
  var am = h < 12 ? 'AM' : 'PM';
  var h12 = h % 12 || 12;
  return h12 + ':' + m + ' ' + am;
}

async function apiCall(endpoint, payload, method){
  try{
    var headers = {'Content-Type':'application/json'};
    if(csrfToken) headers['X-CSRF-Token'] = csrfToken;
    var res = await fetch('api/' + endpoint, {
      method: method || 'POST',
      headers: headers,
      body: payload ? JSON.stringify(payload) : undefined
    });
    return await res.json();
  }catch(e){
    return {ok:false, error:e.message};
  }
}

async function apiGet(endpoint){
  try{
    var res = await fetch('api/' + endpoint);
    return await res.json();
  }catch(e){
    return null;
  }
}

async function performSignOut(ev){
  if(ev && ev.preventDefault) ev.preventDefault();

  // 1. Destroy server-side session and cookies via API
  try {
    await apiCall('auth.php', {action:'logout'});
    await apiCall('session.php', {action:'logout'});
  } catch(e) {}

  // 2. Clear browser client storage
  try {
    localStorage.removeItem('skds_prefect');
    localStorage.clear();
    sessionStorage.clear();
  } catch(e) {}

  // 3. Clear all browser cookies
  try {
    var cookies = document.cookie.split(';');
    for(var i = 0; i < cookies.length; i++){
      var cookie = cookies[i];
      var eqPos = cookie.indexOf('=');
      var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
      document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
      document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/prefect/';
    }
  } catch(e) {}

  // 4. Wipe in-memory client state
  state.prefects = [];
  state.attendance = [];
  state.entries = [];
  state.deductions = [];
  csrfToken = null;

  // 5. Reset UI indicators immediately
  var userBadge = document.getElementById('topbarUser');
  if(userBadge){ userBadge.style.display = 'none'; userBadge.textContent = ''; }
  var navProfileTab = document.getElementById('navProfileTab');
  if(navProfileTab) navProfileTab.style.display = 'none';
  var navTopBoardTab = document.getElementById('navTopBoardTab');
  if(navTopBoardTab) navTopBoardTab.style.display = 'none';
  var navPrefectsTab = document.getElementById('navPrefectsTab');
  if(navPrefectsTab) navPrefectsTab.style.display = 'none';
  var navAuthBtn = document.getElementById('navAuthBtn');
  if(navAuthBtn){ navAuthBtn.textContent = 'Register / Login'; navAuthBtn.href = 'register.php'; navAuthBtn.onclick = null; }

  toast('Signed out. All session data destroyed.');
  setTimeout(function(){ window.location.href = 'index.php'; }, 300);
}

async function initCommon(){
  var data = await apiGet('bootstrap.php');
  if(data){
    csrfToken = data.csrfToken || (data.config && data.config.csrfToken) || null;
    state.config = data.config || {};
    state.prefects = data.prefects || [];
    state.attendance = data.attendance || [];
    state.entries = data.entries || [];
    state.deductions = data.deductions || [];
    state.cycles = data.cycles || [];
  }
  var c = currentCycle();
  var el = document.getElementById('topbarCycle');
  if(el && c){
    var s = c.start || c.startDate || '';
    var e = c.end || c.endDate || '';
    el.innerHTML = '<b>' + esc(c.label || 'Active Cycle') + '</b><br>' + fmtDateNice(s) + ' &ndash; ' + fmtDateNice(e);
  } else if(el){
    el.innerHTML = '<b>No Active Cycle</b>';
  }

  // Session & Dynamic Navigation
  var session = await apiGet('session.php');
  var userBadge = document.getElementById('topbarUser');
  var navAuthBtn = document.getElementById('navAuthBtn');
  var navProfileTab = document.getElementById('navProfileTab');
  var navTopBoardTab = document.getElementById('navTopBoardTab');
  var navPrefectsTab = document.getElementById('navPrefectsTab');

  if(session && session.authenticated){
    var u = session.user;
    if(userBadge){
      userBadge.style.display = 'block';
      userBadge.textContent = (u.type === 'admin') ? ('Admin: ' + (u.name || u.role)) : ('Prefect: ' + u.name);
    }
    if(navAuthBtn){
      navAuthBtn.textContent = 'Sign Out';
      navAuthBtn.href = '#';
      navAuthBtn.onclick = performSignOut;
    }
    if(u.type === 'prefect'){
      if(navProfileTab) navProfileTab.style.display = 'inline-block';
    } else if(u.type === 'admin'){
      if(navProfileTab) navProfileTab.style.display = 'inline-block';
      if(navTopBoardTab) navTopBoardTab.style.display = 'inline-block';
      if(navPrefectsTab) navPrefectsTab.style.display = 'inline-block';
    }
  } else {
    // Check local student session
    var localStudent = sessionStorage.getItem('skds_prefect') || localStorage.getItem('skds_prefect');
    if(localStudent){
      try{
        var sObj = JSON.parse(localStudent);
        if(sObj && sObj.id){
          if(navProfileTab) navProfileTab.style.display = 'inline-block';
          if(userBadge){
            userBadge.style.display = 'block';
            userBadge.textContent = 'Prefect: ' + sObj.name;
          }
          if(navAuthBtn){
            navAuthBtn.textContent = 'Sign Out';
            navAuthBtn.href = '#';
            navAuthBtn.onclick = performSignOut;
          }
        }
      }catch(err){}
    }
  }
}

// 30-Minute Client Inactivity Lock
(function(){
  var idleLimit = 30 * 60 * 1000;
  var idleTimer = null;
  function resetIdleTimer(){
    if(idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(function(){
      var userBadge = document.getElementById('topbarUser');
      if(userBadge && userBadge.style.display !== 'none'){
        toast('Session expired due to 30 minutes of inactivity.', true);
        setTimeout(function(){ performSignOut(); }, 1200);
      }
    }, idleLimit);
  }
  ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function(evt){
    window.addEventListener(evt, resetIdleTimer, {passive: true});
  });
  resetIdleTimer();
})();
</script>
</body>
</html>
<?php
}