<?php

	$server_check_version = '1.0.4';
	$start_time = microtime(TRUE);

	$operating_system = PHP_OS_FAMILY;

	if ($operating_system === 'Windows') {
		// Win CPU
		$wmi = new COM('WinMgmts:\\\\.');
		$cpus = $wmi->InstancesOf('Win32_Processor');
		$cpuload = 0;
		$cpu_count = 0;
		foreach ($cpus as $key => $cpu) {
			$cpuload += $cpu->LoadPercentage;
			$cpu_count++;
		}
		// WIN MEM
		$res = $wmi->ExecQuery('SELECT FreePhysicalMemory,FreeVirtualMemory,TotalSwapSpaceSize,TotalVirtualMemorySize,TotalVisibleMemorySize FROM Win32_OperatingSystem');
		$mem = $res->ItemIndex(0);
		$memtotal = round($mem->TotalVisibleMemorySize / 1000000,2);
		$memavailable = round($mem->FreePhysicalMemory / 1000000,2);
		$memused = round($memtotal-$memavailable,2);
		// WIN CONNECTIONS
		$connections = shell_exec('netstat -nt | findstr :80 | findstr ESTABLISHED | find /C /V ""'); 
		$totalconnections = shell_exec('netstat -nt | findstr :80 | find /C /V ""');
	} else {
		// Linux CPU
		$load = sys_getloadavg();
		$cpuload = $load[0];
		// Linux MEM
		$free = shell_exec('free');
		$free = (string)trim($free);
		$free_arr = explode("\n", $free);
		$mem = explode(" ", $free_arr[1]);
		$mem = array_filter($mem, function($value) { return ($value !== null && $value !== false && $value !== ''); }); // removes nulls from array
		$mem = array_merge($mem); // puts arrays back to [0],[1],[2] after 
		$memtotal = round($mem[1] / 1000000,2);
		$memused = round($mem[2] / 1000000,2);
		$memfree = round($mem[3] / 1000000,2);
		$memshared = round($mem[4] / 1000000,2);
		$memcached = round($mem[5] / 1000000,2);
		$memavailable = round($mem[6] / 1000000,2);
		// Linux Connections
		$connections = `netstat -ntu | grep :80 | grep ESTABLISHED | grep -v LISTEN | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -rn | grep -v 127.0.0.1 | wc -l`; 
		$totalconnections = `netstat -ntu | grep :80 | grep -v LISTEN | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -rn | grep -v 127.0.0.1 | wc -l`; 
	}

	$memusage = round(($memavailable/$memtotal)*100);



	$phpload = round(memory_get_usage() / 1000000,2);

	$diskfree = round(disk_free_space(".") / 1000000000);
	$disktotal = round(disk_total_space(".") / 1000000000);
	$diskused = round($disktotal - $diskfree);

	$diskusage = round($diskused/$disktotal*100);

	if ($memusage > 85 || $cpuload > 85 || $diskusage > 85) {
		$trafficlight = 'red';
	} elseif ($memusage > 50 || $cpuload > 50 || $diskusage > 50) {
		$trafficlight = 'orange';
	} else {
		$trafficlight = '#2F2';
	}

	$end_time = microtime(TRUE);
	$time_taken = $end_time - $start_time;
	$total_time = round($time_taken,4);

	// use servercheck.php?json=1
	if (isset($_GET['json'])) {
		echo '{"ram":'.$memusage.',"cpu":'.$cpuload.',"disk":'.$diskusage.',"connections":'.$totalconnections.'}';
		exit;
	}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>ServerCheck</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	html {
		background: #FFF;
	}
	body {
		background: #FFF;
		font-family: Arial,sans-serif;
		margin: 0;
		padding: 0;
		color: #333;
	}
	#container {
		width: 320px;
		margin: 10px auto;
		padding: 10px 20px;
		background: #EFEFEF;
		border-radius: 5px;
		box-shadow: 0 0 5px #AAA;
		-webkit-box-shadow: 0 0 5px #AAA;
		-moz-box-shadow: 0 0 5px #AAA;
		box-sizing: border-box;
		-moz-box-sizing: border-box;
		-webkit-box-sizing: border-box;
	}
	.description {
		font-weight: bold;
	}
	#trafficlight {
		float: right;
		margin-top: 15px;
		width: 50px;
		height: 50px;
		border-radius: 50px;
		background: <?php echo $trafficlight; ?>;
		border: 3px solid #333;
	}
	#details {
		font-size: 0.8em;
	}
	hr {
		border: 0;
		height: 1px;
		background-image: linear-gradient(to right, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0));
	}
	.big {
		font-size: 1.2em;
	}
	.footer {
		font-size: 0.5em;
		color: #888;
		text-align: center;
	}
	.footer a {
		color: #888;
	}
	.footer a:visited {
		color: #888;
	}
	.dark {
		background: #000;
		filter: invert(1) hue-rotate(180deg);
	}
	</style>
</head>
<body>
	<div id="container">
		<div id="trafficlight" class="nodark"></div>

		<p><span class="description big">🌡️ RAM Usage:</span> <span class="result big"><?php echo $memusage; ?>%</span></p>
		<p><span class="description big">🖥️ CPU Usage: </span> <span class="result big"><?php echo $cpuload; ?>%</span></p>
		<p><span class="description">💽 Hard Disk Usage: </span> <span class="result"><?php echo $diskusage; ?>%</span></p>
		<p><span class="description">🖧 Established Connections: </span> <span class="result"><?php echo $connections; ?></span></p>
		<p><span class="description">🖧 Total Connections: </span> <span class="result"><?php echo $totalconnections; ?></span></p>
		<hr>
		<p><span class="description">🌡️ RAM Total:</span> <span class="result"><?php echo $memtotal; ?> GB</span></p>
		<p><span class="description">🌡️ RAM Used:</span> <span class="result"><?php echo $memused; ?> GB</span></p>
		<p><span class="description">🌡️ RAM Available:</span> <span class="result"><?php echo $memavailable; ?> GB</span></p>
		<hr>
		<p><span class="description">💽 Hard Disk Free:</span> <span class="result"><?php echo $diskfree; ?> GB</span></p>
		<p><span class="description">💽 Hard Disk Used:</span> <span class="result"><?php echo $diskused; ?> GB</span></p>
		<p><span class="description">💽 Hard Disk Total:</span> <span class="result"><?php echo $disktotal; ?> GB</span></p>
		<hr>
		<div id="details">
			<p><span class="description">📟 Server Name: </span> <span class="result"><?php echo $_SERVER['SERVER_NAME']; ?></span></p>
			<p><span class="description">💻 Server Addr: </span> <span class="result"><?php echo $_SERVER['SERVER_ADDR']; ?></span></p>
			<p><span class="description">🌀 PHP Version: </span> <span class="result"><?php echo phpversion(); ?></span></p>
			<p><span class="description">🏋️ PHP Load: </span> <span class="result"><?php echo $phpload; ?> GB</span></p>
			
			<p><span class="description">⏱️ Load Time: </span> <span class="result"><?php echo $total_time; ?> sec</span></p>
		</div>
	</div>
	<footer>
		<div class="footer">
			<a href="https://github.com/jamesbachini/Server-Check-PHP">Server Check PHP</a> v <?php echo $server_check_version; ?> | 
			Built by <a href="https://jamesbachini.com">James Bachini</a> | <a href="?json=1">JSON</a> | 🌙 <a href="javascript:void(0)" onclick="toggleDarkMode();">Dark Mode</a>
		</div>
	</footer>
<script>
	const toggleDarkMode = () => {
		if (localStorage.getItem('darkMode') && localStorage.getItem('darkMode') === 'true') {
			localStorage.setItem('darkMode',false);
		} else {
			localStorage.setItem('darkMode',true);
		}
		setDarkMode();
	}
	const setDarkMode = () => {
		if (localStorage.getItem('darkMode') && localStorage.getItem('darkMode') === 'true') {
			document.documentElement.classList.add('dark');
		} else {
			document.documentElement.classList.remove('dark');
		}
	}
	setDarkMode();
</script>
</body>
</html>