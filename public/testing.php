<?php
require_once __DIR__ . '/common.php';
$base=$GLOBALS['site_addr_oath'] ?? ($GLOBALS['site_addr'] ?? '');
nb_page_start('Integration Testing Console', 'testing.php'); ?>
<div class="alert alert-warning">This page generates commands only. It does not store OAuth access tokens in OpenEMR.</div>
<div class="card shadow-sm mb-3"><div class="card-body"><label class="form-label">OpenEMR base URL</label><input id="base" class="form-control" value="<?php echo attr(rtrim((string)$base,'/')); ?>"><label class="form-label mt-3">Bearer token</label><input id="token" class="form-control" placeholder="Paste temporary OAuth token"><label class="form-label mt-3">Connection key</label><input id="connection" class="form-control" value="envision_openemr"></div></div>
<div class="row g-3"><div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Capability test</div><div class="card-body"><pre id="cap" class="code-wrap bg-dark text-light p-3 rounded"></pre><button class="btn btn-primary" onclick="copyCmd('cap')">Copy command</button></div></div></div><div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Profile capability test</div><div class="card-body"><pre id="profile" class="code-wrap bg-dark text-light p-3 rounded"></pre><button class="btn btn-primary" onclick="copyCmd('profile')">Copy command</button></div></div></div></div>
<script>
function refresh(){const b=document.getElementById('base').value.replace(/\/$/,'');const t=document.getElementById('token').value||'$TOKEN';const c=document.getElementById('connection').value;document.getElementById('cap').textContent=`curl -sk -H "Authorization: Bearer ${t}" "${b}/apis/default/api/neolims/capabilities" | python3 -m json.tool`;document.getElementById('profile').textContent=`curl -sk -H "Authorization: Bearer ${t}" "${b}/apis/default/api/neolims/profile-capabilities?connection_key=${c}" | python3 -m json.tool`;}
function copyCmd(id){navigator.clipboard.writeText(document.getElementById(id).textContent)}
document.querySelectorAll('input').forEach(x=>x.addEventListener('input',refresh));refresh();
</script>
<?php nb_page_end(); ?>
