<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// get params
$sitename  = $this->params->get('sitename');
$slogan    = $this->params->get('slogan', '');
$logotype  = $this->params->get('logotype', 'text');
$logoimage = $logotype == 'image' ? T3Path::getUrl('images/ttc/logo.png', '', true) : '';
$logoimgsm = $logotype == 'image' ? T3Path::getUrl('images/ttc/logo_sm_v2.png', '', true) : "";
$logoright = $logotype == 'image' ? T3Path::getUrl('images/ttc/header_right_v2.png', '', true) : "";

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}

$logosize = 'col-sm-12';
if ($headright = $this->countModules('head-search or languageswitcherload')) {
	$logosize = 'col-sm-8';
}

?>
<!-- Google analytics tag -->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-49540969-1', 'auto');
  ga('send', 'pageview');

</script>
<div id="fb-root"></div>

<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/de_DE/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>


<!-- HEADER -->
<header id="t3-header" class="container t3-header">
	<div class="row">

		<!-- LOGO -->
		<div class="col-xs-12 <?php echo $logosize ?> logo">
			<div class="logo-<?php echo $logotype, ($logoimgsm ? ' logo-control' : '') ?>">
				<a href="<?php echo JURI::base(true) ?>" title="<?php echo strip_tags($sitename) ?>">
					<?php if($logotype == 'image'): ?>
						<img class="logo-img" src="<?php echo JURI::base(true) . '/' . $logoimage ?>" alt="<?php echo strip_tags($sitename) ?>" />
						<img class="logo-right" src="<?php echo JURI::base(true) . '/' . $logoright ?>" alt="<?php echo strip_tags($sitename) ?>">
					<?php endif ?>
					<?php if($logoimgsm) : ?>
						<img class="logo-img-sm" src="<?php echo JURI::base(true) . '/' . $logoimgsm ?>" alt="<?php echo strip_tags($sitename) ?>" />
					<?php endif ?>
					<span><?php echo $sitename ?></span>
				</a>
				<small class="site-slogan"><?php echo $slogan ?></small>
			</div>
		</div>
		<!-- //LOGO -->

		<?php if ($headright): ?>
			<div class="col-xs-12 col-sm-4">
				<?php if ($this->countModules('head-search')) : ?>
					<!-- HEAD SEARCH -->
					<div class="head-search <?php $this->_c('head-search') ?>">
						<jdoc:include type="modules" name="<?php $this->_p('head-search') ?>" style="raw" />
					</div>
					<!-- //HEAD SEARCH -->
				<?php endif ?>

				<?php if ($this->countModules('languageswitcherload')) : ?>
					<!-- LANGUAGE SWITCHER -->
					<div class="languageswitcherload">
						<jdoc:include type="modules" name="<?php $this->_p('languageswitcherload') ?>" style="raw" />
					</div>
					<!-- //LANGUAGE SWITCHER -->
				<?php endif ?>
			</div>
		<?php endif ?>

	</div>
</header>
<!-- //HEADER -->
