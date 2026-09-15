<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

renderHeader('Prefects Directory', 'prefects.php');
?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
      <div>
        <p class="eyebrow">Guild Administration</p>
        <h2 style="margin:0;">Prefects Directory &amp; RBAC Control</h2>
      </div>
      <div id="createAdminBtnWrap" style="display:none;">
        <button class="btn btn-saffron btn-sm" id="showCreateAdminBtn">+ Add Admin Role (Head Prefect)</button>
      </div>
    </div>
    <p class="sub">View and manage all registered prefects. <b>Head Prefect</b> approves registrations; <b>Principal Thero</b> holds executive suspension authority.</p>
    
    <!-- Create Admin Form Box -->
    <div id="createAdminBox" style="display:none; background:#FAF0E6; border:1px solid var(--parchment-line); padding:16px; border-radius:8px; margin-bottom:16px;">
      <h3 style="margin-top:0;">Register Administrative Account</h3>
      <form onsubmit="return false;" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="admRole">Role *</label>
            <select id="admRole">
              <option value="principal">Principal Thero</option>
              <option value="head_prefect">Head Prefect</option>
              <option value="top_board">Top Board Member</option>
            </select>
          </div>
          <div class="field">
            <label for="admName">Full Name *</label>
            <input type="text" id="admName" placeholder="e.g. Ven. Principal Thero">
          </div>
          <div class="field">
            <label for="admUsername">Username *</label>
            <input type="text" id="admUsername" placeholder="e.g. principal2026" autocomplete="off">
          </div>
          <div class="field">
            <label for="admPasscode">Passcode * (Min 4 chars)</label>
            <input type="password" id="admPasscode" placeholder="Passcode" autocomplete="new-password">
          </div>
        </div>
        <div style="display:flex; gap:8px;">
          <button class="btn btn-primary btn-sm" id="submitCreateAdminBtn">Save Admin User</button>
          <button class="btn btn-ghost btn-sm" id="cancelCreateAdminBtn">Cancel</button>
        </div>
      </form>
    </div>

    <!-- Filters -->
    <div style="display:flex; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
      <select id="filterStatus" style="max-width:180px;">
        <option value="all">All Statuses</option>
        <option value="pending">Pending Approval</option>
        <option value="approved">Approved / Active</option>
        <option value="suspended">Suspended</option>
      </select>
      <input type="text" id="searchPrefect" placeholder="Search by name, ID, or phone..." style="flex:1; min-width:200px; padding:8px 12px; border:1px solid var(--parchment-line); border-radius:7px;">
    </div>

    <div id="prefectsTableWrap">Loading prefects directory&hellip;</div>
  </div>

<?php renderFooter(); ?>
<script>
var allPrefects = [];
var currentAdminRole = '';

async function loadDirectory(){
  await initCommon();
  var chk = await apiGet('session.php');
  if(!chk || !chk.authenticated || chk.user.type !== 'admin'){
    toast('Admin login required.', true);
    setTimeout(function(){ window.location.href = 'register.php'; }, 800);
    return;
  }
  currentAdminRole = chk.user.role;
  if(currentAdminRole === 'head_prefect' || currentAdminRole === 'developer'){
    document.getElementById('createAdminBtnWrap').style.display = 'block';
  }

  var res = await apiGet('prefects_admin.php');
  if(res && res.prefects){
    allPrefects = res.prefects;
    renderPrefectsTable();
  } else {
    document.getElementById('prefectsTableWrap').innerHTML = '<div class="empty-state">Could not load directory or unauthorized.</div>';
  }
}

function renderPrefectsTable(){
  var stFilter = document.getElementById('filterStatus').value;
  var q = document.getElementById('searchPrefect').value.toLowerCase().trim();

  var filtered = allPrefects.filter(function(p){
    var mStatus = (stFilter === 'all' || p.status === stFilter);
    var mQ = !q || (p.name && p.name.toLowerCase().includes(q)) || (p.id && p.id.toLowerCase().includes(q)) || (p.contact_primary && p.contact_primary.includes(q));
    return mStatus && mQ;
  });

  var wrap = document.getElementById('prefectsTableWrap');
  if(!filtered.length){
    wrap.innerHTML = '<div class="empty-state">No prefects match the selected filter.</div>';
    return;
  }

  var html = '<table class="ledger"><thead><tr>'+
    '<th>Prefect</th><th>Tier &amp; Class</th><th>Contact Numbers</th><th>Status</th><th style="text-align:right;">Actions</th>'+
    '</tr></thead><tbody>';

  filtered.forEach(function(p){
    var contacts = '<b>Pri:</b> ' + esc(p.contact_primary||'—');
    if(p.contact_secondary) contacts += '<br><b>Sec:</b> ' + esc(p.contact_secondary);
    if(p.contact_emergency) contacts += '<br><b>Emg:</b> ' + esc(p.contact_emergency);
    if(p.email) contacts += '<br><b>Email:</b> ' + esc(p.email);

    var actions = [];

    // Profile action button for admin
    actions.push('<a href="profile.php?id='+p.id+'" class="btn btn-sm btn-ghost" style="text-decoration:none;">Profile &rarr;</a>');

    if(p.status === 'pending'){
      if(currentAdminRole === 'head_prefect' || currentAdminRole === 'principal' || currentAdminRole === 'developer'){
        actions.push('<button class="btn btn-sm" style="background:#2E7D32; border-color:#2E7D32; color:#fff;" data-action="approve" data-id="'+p.id+'">Approve</button>');
        actions.push('<button class="btn btn-danger btn-sm" data-action="reject" data-id="'+p.id+'">Decline</button>');
      } else {
        actions.push('<span class="small muted">Awaiting Head Prefect</span>');
      }
    }

    if(p.status === 'approved'){
      if(currentAdminRole === 'principal' || currentAdminRole === 'developer'){
        actions.push('<button class="btn btn-danger btn-sm" data-action="suspend" data-id="'+p.id+'">Suspend</button>');
      }
    }

    if(p.status === 'suspended'){
      if(currentAdminRole === 'principal' || currentAdminRole === 'developer'){
        actions.push('<button class="btn btn-sm" style="background:#1565C0; border-color:#1565C0; color:#fff;" data-action="reactivate" data-id="'+p.id+'">Reactivate</button>');
      } else {
        actions.push('<span class="small" style="color:#C62828;">Suspended by Principal</span>');
      }
    }

    if((currentAdminRole === 'head_prefect' || currentAdminRole === 'principal' || currentAdminRole === 'developer') && (p.contact_primary || p.email)){
      actions.push('<button class="btn btn-ghost btn-sm" style="font-size:11px;" data-action="purge_contacts" data-id="'+p.id+'" title="Permanently wipe contact numbers and email for minor privacy">Purge Contacts</button>');
    }

    html += '<tr>'+
      '<td class="name-cell"><a href="profile.php?id='+p.id+'" style="color:var(--maroon-deep); font-weight:600; text-decoration:none;">'+esc(p.name)+'</a><br><span class="small muted" style="font-family:var(--font-mono);">'+esc(p.id)+'</span></td>'+
      '<td><span class="tier-pill '+esc(p.tier)+'">'+esc(tierInfo(p.tier).short)+'</span><br><span class="small muted">'+esc(p.grade||'—')+'</span></td>'+
      '<td class="small">'+contacts+'</td>'+
      '<td><span class="status-pill '+esc(p.status)+'">'+esc(p.status.toUpperCase())+'</span>'+
        (p.approved_by ? '<br><span class="small muted">by '+esc(p.approved_by)+'</span>' : '')+
        (p.suspension_reason ? '<br><span class="small" style="color:#C62828;">'+esc(p.suspension_reason)+'</span>' : '')+
      '</td>'+
      '<td style="text-align:right;"><div style="display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;">'+actions.join(' ')+'</div></td>'+
    '</tr>';
  });

  html += '</tbody></table>';
  wrap.innerHTML = '<div class="table-wrap">' + html + '</div>';

  wrap.querySelectorAll('button[data-action]').forEach(function(btn){
    btn.addEventListener('click', async function(){
      var action = btn.dataset.action;
      var id = btn.dataset.id;
      var reason = '';
      if(action === 'suspend'){
        reason = prompt('Enter suspension reason for register:');
        if(reason === null) return;
      }
      if(action === 'purge_contacts'){
        if(!confirm('Permanently wipe telephone numbers and email address for this prefect to respect student data privacy?')) return;
      }
      btn.disabled = true;
      var r = await apiCall('prefects_admin.php', {action: action, prefectId: id, reason: reason});
      btn.disabled = false;
      if(r.ok){
        toast(r.message || 'Action executed successfully.');
        loadDirectory();
      } else {
        toast(r.error || 'Action failed.', true);
      }
    });
  });
}

document.getElementById('filterStatus').addEventListener('change', renderPrefectsTable);
document.getElementById('searchPrefect').addEventListener('input', renderPrefectsTable);

// Create Admin UI Toggle
document.getElementById('showCreateAdminBtn').addEventListener('click', function(){
  document.getElementById('createAdminBox').style.display = 'block';
});
document.getElementById('cancelCreateAdminBtn').addEventListener('click', function(){
  document.getElementById('createAdminBox').style.display = 'none';
});

document.getElementById('submitCreateAdminBtn').addEventListener('click', async function(){
  var role = document.getElementById('admRole').value;
  var name = document.getElementById('admName').value.trim();
  var user = document.getElementById('admUsername').value.trim();
  var pass = document.getElementById('admPasscode').value.trim();

  if(!name || !user || pass.length < 4){
    toast('All fields are required (passcode at least 4 chars).', true);
    return;
  }

  var btn = this;
  btn.disabled = true;
  var r = await apiCall('prefects_admin.php', {action:'create_admin', role:role, name:name, username:user, passcode:pass});
  btn.disabled = false;

  if(r.ok){
    toast('Admin account created successfully.');
    document.getElementById('createAdminBox').style.display = 'none';
    document.getElementById('admName').value = '';
    document.getElementById('admUsername').value = '';
    document.getElementById('admPasscode').value = '';
  } else {
    toast(r.error || 'Failed to create admin.', true);
  }
});

loadDirectory();
</script>