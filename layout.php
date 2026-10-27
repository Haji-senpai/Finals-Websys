<?php
/* Layout */
function head($t){$u=me();global $M;
 echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' | GRC Campus</title><link rel="stylesheet" href="../assets/style.css"></head><body class="'.($u?'app':'auth').'">';
 if($u){echo '<aside id="side"><div class="brand">GRC <b>Campus</b></div><nav><a href="dashboard.php">Dashboard</a>';
  foreach($M as $k=>$v)if(perm($k,'R'))echo '<a href="module.php?m='.$k.'">'.e($k==='users'&&perm($k,'R')==='own'?'My Account':$v['title']).'</a>';
  if(in_array($u['role'],['student','registrar','dean']))echo '<a href="enroll.php">Enrollment</a>';
  if(!in_array($u['role'],['student','professor']))echo '<a href="reports.php">Reports</a>';
  if(!in_array($u['role'],['professor','facilities']))echo '<a href="transcript.php">Grade report</a>';
  echo '</nav><div class="me"><b>'.e($u['full_name']).'</b><small>'.e(ucfirst($u['role'])).'</small><form method="post" action="../auth/logout.php"><input type="hidden" name="t" value="'.csrf().'"><button class="link" type="submit">Sign out</button></form></div></aside>';}
 echo '<main>'.($u?'<button id="burger" aria-label="Open menu">Menu</button>':'').'<h1>'.e($t).'</h1>';}
function foot(){echo '</main><script src="../assets/app.js"></script></body></html>';}
