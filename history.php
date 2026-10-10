<?php
require __DIR__.'/includes/bootstrap.php';
page('Recent readings');
if (!driver_id() && !admin()) { echo '<p>Please <a href="'.h(url()).'">identify yourself</a> first.</p>';end_page();exit; }
$s=db()->query('SELECT r.recorded_at,r.reading_type,r.odometer,v.name vehicle,d.name driver FROM readings r JOIN vehicles v ON v.id=r.vehicle_id JOIN drivers d ON d.id=r.driver_id ORDER BY r.id DESC LIMIT 100');
echo '<div class="scroll"><table><thead><tr><th>Date (UTC)</th><th>Vehicle</th><th>Driver</th><th>Type</th><th>km</th></tr></thead><tbody>';foreach($s as $r) echo '<tr><td>'.h($r['recorded_at']).'</td><td>'.h($r['vehicle']).'</td><td>'.h($r['driver']).'</td><td>'.h($r['reading_type']).'</td><td>'.(int)$r['odometer'].'</td></tr>';echo '</tbody></table></div>';end_page();
