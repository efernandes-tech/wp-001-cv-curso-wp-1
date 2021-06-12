<?php

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('Please do not call this page directly.');
}

// config
$grid_size = 24;
$col_labels = '';
$row_labels = '';

// generate columns and row labels
for ($x = 1; $x <= $grid_size; $x++) {
	$clabel = $x;
	$rlabel= $x;
	if (true){
		$clabel = 'C'.$x;
		$rlabel= 'R'.$x;
	}
	$col_labels.= '
	<li class="col-label">
		<span class="large-heading-label">'.$clabel.'</span>
	</li>';
	$row_labels.= '<li class="row-label">'.$rlabel.'</li>';
}


// grid control start
$grid_control = '
<div id="grid-control-wrap" class="grid-control-wrap">
	
	<div class="graph-area">
		<div class="grid-control">
	
			<span class="clear-all-grid-styles" data-input-level="group" title="'.esc_attr__('Clear all grid styles', 'microthemer').'"></span>
		
			<ul class="col-labels">'.$col_labels.'</ul>
			<ul class="row-labels">'.$row_labels.'</ul>
			
			<div class="mt-grid-areas"></div>
			
			<div class="implicit-grid"></div>
			<div class="explicit-grid">
				<div class="explicit-grid-toggle" title="'.esc_attr__('Drag grid template', 'microthemer').'"></div>
			</div>
			
			<div class="grid-canvas grid-stack"></div>
			
			<div class="mt-lookup-grid"></div>
	
		</div>
		
	</div>
	
	<div class="nth-item-radios tab-control tab-control-griditems">
		<span class="nth-item-heading">nth</span>
		<ul class="fake-radio-parent"></ul>
	</div>
		
</div>';


// grid control end
/*$grid_control.= '

	
	
</div>';*/

// append grid control
$html.= $grid_control;