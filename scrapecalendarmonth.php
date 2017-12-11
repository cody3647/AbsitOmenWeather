<?php
require_once(dirname(__FILE__) . '/Goutte/vendor/autoload.php');
use Goutte\Client;

$months = array (1=>'January', 'February', 'March', 'April', 'May', 'June','July','August','September','October','November', 'December');
$months_short = array(1 => 'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');

echo '
<!DOCTYPE html>
<html>
	<head>
		<title>Absit Omen Weather Almanac Creator</title>
		<style>
			body, a {
				color:#ccc;
				background-color:#333333;
			}
			form {
				clear:both;
			}
			fieldset.one {
				width:20%;
				float:left;
				height:250px;
			}
			fieldset.two {
				width:70%;
				float:left;
				height:250px;
			}
			dt {
				clear:both;
				float:left;
				padding-bottom:1em;
			}
			
			dd {
				float:left;
				padding-bottom:1em;
			}
			
			.button {
				float:right;
				clear:both;
				display:block;
			}
			
			h1 {
				clear:both;
			}
			input,textarea,#weather_almanc {
				background-color:#222222;
				color:#ccc;
			}
			textarea {
				width:100%;
				height:226px;
				
			}
			
			.temp{
				background-color:darkmagenta;
				color:#ddd;
			}
			
			.header{
				background-color:darkblue;
				color:#ddd;
			}
			p {
				clear:both;
			}
			
			#weather_almanc {
				border:1px solid white;
				padding: 1em;
				width:75%;
				margin:auto;

			}
		</style>
		<script type="text/javascript">
			function ireland_link(){
				
				var months_short = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"];
				var link = document.getElementById("irelandLink");
				var yearinput = document.getElementById("year");
				var monthinput = document.getElementById("month");
				
				var url = "http://www.met.ie/climate/MonthlyWeather/clim-" + yearinput.value + "-"+ months_short[monthinput.value-1] +".pdf";
				link.href = url;
				link.innerHTML = "Weather Bulletin: " + yearinput.value + "-"+ months_short[monthinput.value-1];
			}
		</script>
	</head>
	<body>';

echo '
		<form action="scrapecalendarmonth.php" method="post">
			<fieldset class="one">
			<legend>Date Information:</legend>
				<dl>
					<dt><label for="year">Year:</label></dt>
					<dd><input type="text" id="year" name="year" value="' , !empty($_POST['year']) ?  $_POST['year'] : '' , '" required pattern="[0-9]{4}"  onchange="ireland_link()"  onkeyup="ireland_link()"/></dd>
					<dt><label for="month">Month:</label></dt>
					<dd><select name="month" id="month" onchange="ireland_link()" required>';
					foreach($months as $m => $month)
						echo '
						<option value="' , $m , '" ' , (!empty($_POST['month']) && $m == $_POST['month']) ? 'selected' : '' , '>' , $month , '</option>';
					echo'
					</select></dd>
				</dl>
				<p><a id="irelandLink" href="http://www.met.ie/climate/MonthlyWeather/clim-' ,( !empty($_POST['year']) ?  $_POST['year'] : '' ) , '-', (!empty($_POST['month'])? $months_short[$_POST['month']] : '' ), '">Weather Bulletin: ' ,( !empty($_POST['year']) ?  $_POST['year'] : '' ) , '-', (!empty($_POST['month'])? $months_short[$_POST['month']] : '' ), '</a></p>
				<input class="button" type="submit" />
				<p>Enter information from Ireland\'s Montly breifing to the right.  Text below will be editable for changes.<br />
				<span class="temp">Regex edited temperatures</span><br />
				<span class="header">Regex editd headings</span></p>
			</fieldset>
			<fieldset class="two">
				<legend>Ireland Information</legend>
				<textarea name="ireland">' , !empty($_POST['ireland']) ?  $_POST['ireland'] : ''  , '</textarea>
			</fieldset>
		</form>';
		
if(!empty($_POST['year']) && ((!empty($_POST['month']) && $_POST['month'] >=1 || $_POST['month'] <=12))){
	
	$wiki_calendar = wiki_calendar($_POST['year'], $_POST['month']);
	$weather_overview = weather_overview($_POST['year'], $_POST['month']);

	weather_almanac($wiki_calendar, $weather_overview, $_POST['year'], $_POST['month']);
}

echo '
	</body>
</html>';




function wiki_calendar($year, $month){
	$client = new Client();
	
	$london = $client->request('GET', 'https://www.wunderground.com/history/airport/EGLL/' . $year . '/' . $month . '/1/MonthlyCalendar.html');

	$wiki_calendar['london'] =  '
			===London===<br />
			{{#vardefine:lat|51.5}}<br />
			{{#vardefine:long|-0.13}}<br />';

	$wiki_calendar['london'] .=  calendar($london);
	unset($london);
	
	$hogwarts = $client->request('GET', 'https://www.wunderground.com/history/airport/EGQK/' . $year . '/' . $month . '/1/MonthlyCalendar.html');
			
	$wiki_calendar['hogwarts'] =  '
			===Hogwarts===<br />
			{{#vardefine:lat|57.44}}<br />
			{{#vardefine:long|-3.13}}<br />';
			
	$wiki_calendar['hogwarts'] .=  calendar($hogwarts);

	unset($hogwarts);
	
	return $wiki_calendar;
}

function calendar($doc){

		$calendar = '<br />
{| class="almanac"<br />
! Sunday<br />
! Monday<br />
! Tuesday<br />
! Wednesday<br />
! Thursday<br />
! Friday<br />
! Saturday<br />
|-<br />
';

	$month = array();
	$empty_days = $doc->filterXPath("//table[@class='calendar-history-table']/tbody/tr/td[1]")->attr('colspan');
	
	$month = $doc->filter("td.day")->each(
		function ($node) {
			return array(
				'day' => trim($node->filterXPath("//a[@class='dateText']")->text()),
				'high' => $node->filterXPath("//td[contains(text(),'Actual:')]//following-sibling::td/span[@class='high']")->text(),
				'low' => $node->filterXPath("//td[contains(text(),'Actual:')]//following-sibling::td/span[@class='low']")->text(),
				'condition' => trim($node->filterXPath("//td[@class='show-for-large-up']")->text()),
				'icon' => basename($node->filterXPath("//td[@class='condition-icon']/img")->attr('src')),
			);

		}
	);

	$i=0;
	while($i<$empty_days){
		$calendar .= '|<br />' . PHP_EOL;
		$i++;
	}

	foreach($month as $day){
		if($i % 7 == 0 && $i != 0)
			$calendar .= '|-<br />' . PHP_EOL;
		$calendar .= '|{{almanac|' . $day['day'] . '|' . $day['high'] . '|f|' . $day['low'] . '|f|File:' . ucfirst($day['icon']) . '|' . $day['condition'] . '}}<br />' . PHP_EOL;
		
		$i++;
	}

	while(($i)%7 != 0){
		$calendar .= '|<br />' . PHP_EOL;
		$i++;
	}

	$calendar .= '|}<br />';
	$calendar = str_replace('°', '', $calendar);
	return $calendar;
	
	
}

function weather_overview($year, $month){
	global $months;
	$client = new Client();
	$overview = array();
	$uk = $client->request('GET', 'http://www.metoffice.gov.uk/climate/uk/summaries/' . $year . '/' . $months[$month]);
	$section = 0;
	$regex = array(
			'pattern' => array(
				'~\xa0~u',
				'~−~',
				'~(?<! differences of )(\-?[0-9]+\.?[0-9]+ ?°C)(?! above| below)~',
				'~^([0-9]+(?:st|th|rd|nd)+ to [0-9]+(?:st|th|rd|nd)+):?$~'
			),
			'replacement' => array(
				' ',
				'-',
				'<span class="temp">{{#temperature:$1}}</span>',
				'<span class="header">====$1====</span>',
			)
		);
	
	$article = $uk->filterXPath("//article/p[not(time|@class)]|//article/h2[not(contains(.,'UK climate video'))]")->each(
		function ($node) {
			return  array($node->nodeName(), $node->text());
		}
	);

	foreach($article as $tag){
		
		if($tag[0] == 'h2' && strtolower($tag[1]) != 'weather impacts'){
			$section++;
			$overview[$section] = '===' . $tag[1] . '===<br />';
		} elseif($tag != 'p' && strtolower($tag[1]) == 'weather impacts'){
			$overview[$section] .= '====' . $tag[1] . '====<br />';
		} else {
			$overview[$section] .= preg_replace($regex['pattern'], $regex['replacement'], $tag[1]) . '<br /><br />';
			
		}
			$overview[$section] .= PHP_EOL;
	}

	return $overview;
}


function weather_almanac($wiki_calendar, $weather_overview, $year, $month){
	global $months;
	echo '
		<h1><a href="http://absitomen.com/wiki/index.php?title=Weather_Almanac/' , $year, '/' , $months[$month] , '&action=edit">AO Lexicon: Weather Almanac / ' , $year, ' / ', $months[$month] , '</a></h2>';
		
	echo '
		<div id="weather_almanc"  onClick="this.contentEditable=\'true\';">
			{{#vardefine:month|'. $month.  '}}<br />
			{{#vardefine:year|'. $year. '}}<br />
			{{Weather Almanac}}<br />
			==' . $months[$month] .  ' ' . $year . '==<br />';
	echo array_shift($weather_overview);
	
	echo $wiki_calendar['london'];
	echo $wiki_calendar['hogwarts'];
	
	foreach($weather_overview as $section)
		echo $section;
		
	if(!empty($_POST['ireland']))
		echo '===Ireland diary of highlights===<br />', PHP_EOL , ireland($_POST['ireland']);
	
	echo '
		</div>';
}

function ireland($ireland){
	
	$regex = array(
			'pattern' => array(
				'~\xa0~u',
				'~−~',
				'~(?<! differences of )(\-?[0-9]+\.?[0-9]+ ?°C)(?! above| below)~',
				'~^([0-9]+(?:st|th|rd|nd)+ to [0-9]+(?:st|th|rd|nd)+):?~m',
				'~\R~',
			),
			'replacement' => array(
				' ',
				'-',
				'<span class="temp">{{#temperature:$1}}</span>',
				'<br /><br /><span class="header">====$1====</span><br /><br />',
				' ',
			)
		);
		
	return preg_replace($regex['pattern'], $regex['replacement'], $ireland);
}