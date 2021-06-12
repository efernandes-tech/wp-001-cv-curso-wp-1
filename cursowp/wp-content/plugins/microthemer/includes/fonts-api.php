<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Microthemer Fonts API</title>

<link href="../css/fonts.css" rel="stylesheet">

<link id="google-fonts" rel="stylesheet" type="text/css" href=""/>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js" type="text/javascript"></script>

<?php

// resolve JS file
$dev_mode = true;
$mt_script = $dev_mode ? '../js/page/fonts.js' :  '../js-min/fonts.js';

// ensure non-cached js
$time = time();

// pagination
$results_per_page = 7;

// get user config / default to value
$page = isset($_GET['page']) ? htmlentities(intval($_GET['page'])) : 1;
$order = isset($_GET['order']) ? htmlentities($_GET['order']) : 'popularity';

// determine start and end
$start = ($page-1)*$results_per_page;
$end = $start+$results_per_page;

$selected = 'selected="selected"';
?>



<script src="<?php echo $mt_script; ?>?v=<?php echo $time; ?>" type="text/javascript"></script>

</head>

<body>



<span id='page-num' rel='<?php echo $page; ?>'></span>
<span id='results-per-page' rel='<?php echo $results_per_page; ?>'></span>
<span id='sort-order' rel='<?php echo $order; ?>'></span>
<span id='start-slice' rel='<?php echo $start; ?>'></span>
<span id='end-slice' rel='<?php echo $end; ?>'></span>

<div class='content-box google-fonts'>

		<div class="inner-box">

		<div id="controls">

			<label for="order">Sort By:</label>
			<select id="order" name="order">
				<option value="popularity" <?php if ($order == 'popularity') echo $selected; ?>>Popularity</option>
				<option value="trending" <?php if ($order == 'trending') echo $selected; ?>>Trending</option>
				<option value="alpha" <?php if ($order == 'alpha') echo $selected; ?>>Alphabetical</option>
				<option value="date" <?php if ($order == 'date') echo $selected; ?>>Date Added</option>
				<option value="style" <?php if ($order == 'style') echo $selected; ?>>Number of Styles</option>
			</select>

			<!--<label for="preview-text">Preview Text:</label>
			<select id="preview-text" name="preview_text">
				<option value="Grumpy wizards make toxic brew for the evil Queen and Jack.">Grumpy wizards make ...</option>
				<option value="The quick brown fox jumps over the lazy dog.">The quick brown fox ...</option>
				<option value="AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz 0123456789">AaBbCcDdEeFfGgHhIiJj...</option>
			</select>-->

			<!--<label for="font-size">Font Size:</label> <input id="font-size" type="text" name="font_size" />px-->

			<div id="page-arrows">
			<?php
			if ($page != 1) {
				?>
				<a href="?page=<?php echo $page-1; ?>&order=<?php echo $order; ?>" id="prev-page">&laquo; Previous</a>
				<?php
			}
			?>
			<a href="?page=<?php echo $page+1; ?>&order=<?php echo $order; ?>" id="next-page">Next &raquo;</a>
			</div>
			<div id="pagination"></div>
		</div>

		<div id="output"></div>

		</div>
	</div>

</body>
</html>
