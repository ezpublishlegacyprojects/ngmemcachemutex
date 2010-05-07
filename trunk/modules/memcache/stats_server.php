<?php
include_once('extension/ngmemcachemutex/classes/common/memcachestat.php');

$time       = time();

$oMemCacheStats = new memcachestat();
$server = $oMemCacheStats->get_memcache_servers();

// host stats
$phpversion          = phpversion();
$memcacheStats       = $oMemCacheStats->getMemcacheStats();

$mem_size    = $memcacheStats['limit_maxbytes'];
$mem_used    = $memcacheStats['bytes'];
$mem_avail   = $mem_size - $mem_used;
$startTime   = time() - array_sum($memcacheStats['uptime']);

$curr_items  = $memcacheStats['curr_items'];
$total_items = $memcacheStats['total_items'];
$hits        = ($memcacheStats['get_hits']==0) ? 1:$memcacheStats['get_hits'];
$misses      = ($memcacheStats['get_misses']==0) ? 1:$memcacheStats['get_misses'];
$sets        = $memcacheStats['cmd_set'];

$req_rate    = sprintf("%.2f",($hits+$misses)/($time-$startTime));
$hit_rate    = sprintf("%.2f",($hits)/($time-$startTime));
$miss_rate   = sprintf("%.2f",($misses)/($time-$startTime));
$set_rate    = sprintf("%.2f",($sets)/($time-$startTime));

$sResult = '<div class="info div1"><h2>General Cache Information</h2>
				<table cellspacing=0><tbody>
				<tr class=tr-1><td class=td-0>PHP Version</td><td>' . $phpversion . '</td></tr>
					<tr class=tr-0><td class=td-0>Memcached Host</td><td>';

$sResult .=  $server;
$sResult .= "</td></tr>\n";
$sResult .= "<tr class=tr-1><td class=td-0>Total Memcache Cache</td><td>" . $oMemCacheStats->bsize($memcacheStats['limit_maxbytes']) . "</td></tr>\n";
$sResult .= "</tbody></table></div><div class=\"info div1\"><h2>Memcache Server Information</h2>";

$sResult .= '<table cellspacing=0><tbody>';
$sResult .= '<tr class=tr-0><td class=td-0>Start Time</td><td>' . date(MEMCACHE_DATE_FORMAT, $memcacheStats['time'] - $memcacheStats['uptime']) . '</td></tr>';
$sResult .= '<tr class=tr-1><td class=td-0>Uptime</td><td>' . $oMemCacheStats->duration($memcacheStats['time'] - $memcacheStats['uptime']) . '</td></tr>';
$sResult .= '<tr class=tr-0><td class=td-0>Memcached Server Version</td><td>' . $memcacheStats['version'] .'</td></tr>';
$sResult .= '<tr class=tr-1><td class=td-0>Used Cache Size</td><td>' . $oMemCacheStats->bsize($memcacheStats['bytes']) . '</td></tr>';
$sResult .= '<tr class=tr-0><td class=td-0>Total Cache Size</td><td>' . $oMemCacheStats->bsize($memcacheStats['limit_maxbytes']) .  '</td></tr>';
$sResult .= '</tbody></table>';

$sResult .= '</div><div class="graph div3"><h2>Host Status Diagrams</h2><table cellspacing=0><tbody>';

$sResult .= '<tr><td class=td-0>Cache Usage</td><td class=td-1>Hits &amp; Misses</td></tr>';

$sResult .= '<tr><td class=td-0><span class="green box">&nbsp;</span>Free: ' . $oMemCacheStats->bsize($mem_avail).sprintf(" (%.1f%%)", $mem_avail * 100 / $mem_size) . "</td>\n" .
			'<td class=td-1><span class="green box">&nbsp;</span>Hits: ' . $hits.sprintf(" (%.1f%%)",$hits * 100 / ($hits+$misses)) . "</td>\n" . 
			'</tr><tr>' .
			'<td class=td-0><span class="red box">&nbsp;</span>Used: ' . $oMemCacheStats->bsize($mem_used ).sprintf(" (%.1f%%)", $mem_used * 100 / $mem_size). "</td>\n" .
			'<td class=td-1><span class="red box">&nbsp;</span>Misses: ' . $misses.sprintf(" (%.1f%%)", $misses * 100 /($hits+$misses)) . "</td>\n";

$sResult .= '</tr></tbody></table><br/>
<div class="info"><h2>Cache Information</h2>
<table cellspacing=0><tbody>
<tr class=tr-0><td class=td-0>Current Items(total)</td><td>'. $curr_items . ' (' . $total_items. ') </td></tr>
<tr class=tr-1><td class=td-0>Hits</td><td>' . $hits . '</td></tr>
<tr class=tr-0><td class=td-0>Misses</td><td>' . $misses. '</td></tr>
<tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td>' . $req_rate. ' cache requests/second</td></tr>
<tr class=tr-0><td class=td-0>Hit Rate</td><td>' . $hit_rate . ' cache requests/second</td></tr>
<tr class=tr-1><td class=td-0>Miss Rate</td><td>' . $miss_rate . ' cache requests/second</td></tr>
<tr class=tr-0><td class=td-0>Set Rate</td><td>' . $set_rate . ' cache requests/second</td></tr>
</tbody></table>
</div>';

echo $sResult;

eZExecution::cleanExit();

?>