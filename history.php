<?php
require __DIR__.'/includes/bootstrap.php';
page('Trip history');
if (!driver_id() && !admin()) { echo '<p>Please <a href="'.h(url()).'">identify yourself</a> first.</p>';end_page();exit; }
$s=db()->query('SELECT t.*,v.name vehicle,d.name driver FROM trips t JOIN vehicles v ON v.id=t.vehicle_id JOIN drivers d ON d.id=t.driver_id ORDER BY t.id DESC LIMIT 100');
echo '<h2>Journeys</h2><div class="scroll"><table><thead><tr><th>Started (UTC)</th><th>Vehicle</th><th>Driver</th><th>Start km</th><th>Finish km</th><th>Distance</th><th>Description</th><th>Status</th></tr></thead><tbody>';
foreach($s as $r) echo '<tr><td>'.h($r['start_at']).'</td><td>'.h($r['vehicle']).'</td><td>'.h($r['driver']).'</td><td>'.number_format((int)$r['start_odometer']).'</td><td>'.($r['end_odometer']===null?'—':number_format((int)$r['end_odometer'])).'</td><td>'.($r['end_odometer']===null?'—':number_format((int)$r['end_odometer']-(int)$r['start_odometer']).' km').'</td><td>'.h($r['trip_description']).'</td><td>'.($r['end_at']===null?'In progress':'Completed').'</td></tr>';
echo '</tbody></table></div>';
$legacy=db()->query('SELECT r.recorded_at,r.reading_type,r.odometer,r.trip_description,v.name vehicle,d.name driver FROM readings r JOIN vehicles v ON v.id=r.vehicle_id JOIN drivers d ON d.id=r.driver_id ORDER BY r.id DESC LIMIT 100');
echo '<details><summary>Earlier individual readings (preserved)</summary><div class="scroll"><table><thead><tr><th>Date (UTC)</th><th>Vehicle</th><th>Driver</th><th>Type</th><th>km</th><th>Description</th></tr></thead><tbody>';
foreach($legacy as $r) echo '<tr><td>'.h($r['recorded_at']).'</td><td>'.h($r['vehicle']).'</td><td>'.h($r['driver']).'</td><td>'.h($r['reading_type']).'</td><td>'.number_format((int)$r['odometer']).'</td><td>'.h($r['trip_description']).'</td></tr>';
echo '</tbody></table></div></details>';end_page();
