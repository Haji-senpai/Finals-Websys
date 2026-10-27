<?php require __DIR__.'/../includes/functions.php'; need();
if(in_array(me()['role'],['student','professor']))deny('This page is not available for your role. Go back to the dashboard.','No access');
$sem=(int)val('SELECT id FROM semesters WHERE is_active=1');
$R=['Enrollment per subject'=>"SELECT sub.code,sub.title,COUNT(en.id) enrolled FROM subjects sub LEFT JOIN sections sec ON sec.subject_id=sub.id AND sec.semester_id=$sem LEFT JOIN enrollments en ON en.section_id=sec.id AND en.status='approved' GROUP BY sub.id ORDER BY enrolled DESC",
'Faculty load'=>"SELECT u.full_name professor,COUNT(sec.id) sections,COALESCE(SUM(sub.units),0) units FROM users u LEFT JOIN sections sec ON sec.professor_id=u.id AND sec.semester_id=$sem LEFT JOIN subjects sub ON sub.id=sec.subject_id WHERE u.role='professor' GROUP BY u.id",
'Grade submission status'=>"SELECT g.status,COUNT(*) total FROM grades g JOIN sections sec ON sec.id=g.section_id WHERE sec.semester_id=$sem GROUP BY g.status",
'Room utilization, hours per week'=>"SELECT r.name room,ROUND(COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sc.end_time,sc.start_time)))/3600,0),1) hours FROM rooms r LEFT JOIN schedules sc ON sc.room_id=r.id LEFT JOIN sections sec ON sec.id=sc.section_id AND sec.semester_id=$sem WHERE sc.id IS NULL OR sec.id IS NOT NULL GROUP BY r.id ORDER BY hours DESC"];
if(me()['role']==='facilities')$R=array_slice($R,3);
head('Reports'); echo '<p class="sub">Counts are scoped to the active semester: '.e(val('SELECT name FROM semesters WHERE is_active=1')?:'No active semester').'.</p>';
foreach($R as $t=>$q){$d=rows($q);echo '<section class="card"><h2>'.e($t).'</h2><div class="scroll"><table><thead><tr>';
 foreach(array_keys($d[0]??['no_data'=>0]) as $h)echo '<th>'.lbl($h).'</th>';echo '</tr></thead><tbody>';
 foreach($d as $x){echo '<tr>';foreach($x as $v)echo '<td>'.e($v).'</td>';echo '</tr>';}
 echo '</tbody></table></div></section>';}
foot();
