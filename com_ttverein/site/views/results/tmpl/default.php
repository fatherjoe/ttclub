<?php
defined('_JEXEC') or die('Restricted access');

$title = 'Spielergebnisse';
$document=JFactory::getDocument();
$app=JFactory::getApplication();
$document->setTitle( $title . ' - ' . $app->getCfg('sitename') );

JFactory::getDocument()->setDescription("Die Ergebnisse der letzten Spiele unserer Mannschaften. Ständig aktualisiert mit den Daten aus click-TT" );
?>

<div class="page-header clearfix">
	<h1 class="page-title"><?php echo $title;?></h1>
</div>
<div class="item-page clearfix">
	<span id="teamnextmatches"></span>
</div>

<?php 
$rootpath = JURI::root().'administrator/components/com_ttverein/lib/clicktt/ajaxresults.php';

$ajaxurl = '?verband=' .urlencode($this->ajax->verband) 
	.'&vereinsnummer='.urlencode($this->ajax->vereinsnummer) 
	.'&clubname='.urlencode($this->ajax->clubname)
	.'&clubid='.urlencode($this->ajax->clubid);

?> 

<script type="text/javascript">
/* <![CDATA[ */
	
	var xmlHttp = false;

	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)	
	try {
  		xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
    		xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (e2) {
    		xmlHttp = false;
  		}
	}
	@end @*/

	if (!xmlHttp && typeof XMLHttpRequest != 'undefined') {
  		xmlHttp = new XMLHttpRequest();
  	}
  	getNextMatches();
	function getNextMatches(){
		if (xmlHttp) {
			xmlHttp.open('GET', '<?php echo $rootpath.$ajaxurl ?>', true);
			xmlHttp.onreadystatechange = function () {
        		if (xmlHttp.readyState == 4) {
        			document.getElementById("teamnextmatches").innerHTML = xmlHttp.responseText;
        			allocateSidebarHeightForResults();
         		}
     		};
     		xmlHttp.send(null);
 		}
	}
	
	function allocateSidebarHeightForResults() {
		var contentDom = document.getElementById("wrapper2");
		var sidebarDom = document.getElementById("nav");
		var tableDom = document.getElementsByClassName("contentpaneopen")[0];
		if((tableDom.offsetHeight + 100) > contentDom.offsetHeight) {
			contentDom.style.height = (tableDom.offsetHeight + 100)+"px"; 
		}
		if((contentDom.offsetHeight) > sidebarDom.offsetHeight) {
			sidebarDom.style.height = (contentDom.offsetHeight)+"px"; 
		}
	}

/* ]]> */	
</script>
