<?php
// Have a fart about with everything as you see fit, I'm no code monkey but it does the job.
// A bog standard PHP install should do the trick, bc is required for the math but is usually installed by default.

// Set your routers IP here, the default is 192.168.8.1
$router="192.168.8.1";

// Set your Montly Quota (in GB) here.
$quota="500";

// Safety factor. The amount of data added in case the router under reports.
$sfactor=1.015;

// Set your timezone here if PHP is bitching
//date_default_timezone_set("Australia/Brisbane")
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Huawei Data</title>
	<style>
	span {
		font-family: Verdana, Geneva, sans-serif;
		font-size: 58px;
		}
	
	table.main {
		border-collapse: separate;
		padding: 2px;
		font-family: Verdana, Geneva, sans-serif;
		font-size: 20px;
		}
	
	table.inner {
		border-collapse: collapse;
		}

	@media screen and (device-aspect-ratio: 40/71) {
		span {
			font-family: Verdana, Geneva, sans-serif;
			font-size: 56px;
			}
	
		table.main {
			border-collapse: separate;
			padding: 2px;
			font-family: Verdana, Geneva, sans-serif;
			font-size: 30px;
			width: 90%;
			}
	
		table.inner {
			border-collapse: collapse;
			}
		}
	
	
	</style>
</head>

<?php
$initurl="http://$router/html/home.html";
$dataurl="http://$router/api/monitoring/month_statistics";

//get api data

$ch = curl_init($dataurl);
curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookies); 
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec ($ch);

$usage = new SimpleXMLElement($output);

//api data into vars
$down=$usage->CurrentMonthDownload;
$up=$usage->CurrentMonthUpload;
$dur=$usage->MonthDuration;
$clr=$usage->MonthLastClearTime;

//add safety factor
$down=bcmul($down, $sfactor);
$up=bcmul($up, $sfactor);

//get rollover month number of days
$dates = explode("-", $clr);
$datesmo = $dates[1];
$datesyr = $dates[0];
$daysinmonth = cal_days_in_month(CAL_GREGORIAN, $datesmo, $datesyr);

//do some date math in ticks
$ticknow = strtotime(date('Y-m-d H:i:s'));
$tickroll = strtotime($clr); 
$daysprogress = abs($ticknow - $tickroll) / 86400;

//general math
$monthup=bcdiv($up, 1073741824, 2);
$monthdown=bcdiv($down, 1073741824, 2);
$monthtotal=bcdiv(bcadd($up, $down), 1073741824, 2);
$daysremaining=bcsub($daysinmonth, $daysprogress, 2);
$dataremaining=bcsub($quota, bcdiv(bcadd($up, $down), 1073741824, 2), 2);
$gbdailysofar=bcdiv($monthtotal, $daysprogress, 2);
$gbdailyleft=bcdiv($dataremaining, $daysremaining, 2);
$dataprogress=bcmul(bcdiv($monthtotal, $quota, 4), 100, 2);
$timeprogress=bcmul(bcdiv($daysprogress, $daysinmonth, 4), 100, 2);
$dataprojected=bcmul($gbdailysofar, $daysinmonth, 2);
$sleevedata=bcsub($quota, $dataprojected, 2);
$gbspare=bcsub(bcmul($daysprogress, bcdiv($quota, $daysinmonth, 2), 2), $monthtotal, 2);

//progress bar colour
//above 1gb - green
if ($gbspare >= 2) {
    $progbarcolour="#00CC33";
//0 to 1gb - yellow
	} elseif (($gbspare >= .5) && ($gbspare < 2)) {
    $progbarcolour="#ffff1a";
//-1 to 0gb - orange
	} elseif (($gbspare >= -.5) && ($gbspare < .5)) {
    $progbarcolour="#ff8000";
//below -1 - red
	} elseif ($gbspare < -.5) {
    $progbarcolour="#ff0000";
}

//gb/d data results with time less than 1 makes for hilarious results so get redone
if ($daysprogress < 1) {
	$gbdailysofar=bcdiv($monthtotal, 1, 2);
}
if ($daysremaining < 1) {
	$gbdailyleft=bcdiv($dataremaining, 1, 2);
}
?>

<body bgcolor="#F5F5F5">
	<center>
	<span>Optus Wireless Broadband</span><br/><br/>
	<table class="main">
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Data uploaded so far this Month.">Monthly Upload</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $monthup; ?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Data downloaded so far this Month.">Monthly Down</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $monthdown; ?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Total data so far this Month.">Monthly Total</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $monthtotal; ?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Days remaining this Month.">Time Remaining</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $daysremaining; ?> Days</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Data remaining this Month.">Data Remaining</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $dataremaining; ?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Average data usage per day this Month.">GB/D To Date</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $gbdailysofar ;?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Daily data availabe for remaining days to reach the quota.">GB/D Remaining</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $gbdailyleft ;?> GB</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Data 'Spare'. The total Montly average daily data allocation which has not been used.">GB Spare</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $gbspare ;?> GB</td>
		</tr>
		<tr>
			<td bgcolor=#03C8D8><div title="Percentage bar of the days in to the Month.">Month Progress</div></td>
			<td bgcolor=#E5E5E5>
				<table class="inner" width=<?= $timeprogress ;?>% title=<?= $timeprogress ;?>>
					<tr>
						<td bgcolor=<?= $progbarcolour ;?>><br style="visibility:hidden"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td bgcolor=#03C8D8><div title="Percentage bar of the data usage so far this Month.">Data Progress</div></td>
			<td bgcolor=#E5E5E5>
				<table class="inner" width=<?= $dataprogress ;?>% title=<?= $dataprogress ;?>>
					<tr>
						<td bgcolor=#00CC33><br style="visibility:hidden"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width=200 bgcolor=#03C8D8><div title="Estimated total usage based off of the daily usage to date.">Est. Use/Remain</div></td>
			<td width=500 bgcolor=#E5E5E5><?= $dataprojected; ?> / <?= $sleevedata; ?> GB</td>
		</tr>
		<tr>
			<td bgcolor=#03C8D8><div title="The last date the Modem rolled over for data. This can be set via the hlink app if incorrect.">Last Data Reset</div></td>
			<td bgcolor=#E5E5E5><?= $clr; ?></td>
		</tr>
		<tr>
			<td bgcolor=#03C8D8><div title="The date and time this page was generated.">Last Updated</div></td>
			<td bgcolor=#E5E5E5><?= date("g:i:s A, F j, Y"); ?></td>
		</tr>
	</table>
	</center>
</body>
</html>
