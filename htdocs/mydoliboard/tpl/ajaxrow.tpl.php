<?php
/* Copyright (C) 2013		Charles-Fr BENKE		<charles.fr@benke.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Javascript code to activate drag and drop on lines
 */
?>

<!-- BEGIN PHP TEMPLATE FOR JQUERY -->
<script type="text/javascript">
function getParent(cell){ return $(cell).parent('tr') }
$(document).ready(function(){
	$('.upArrow').live('click', function(){
		var parent = getParent(this)
		var prev = parent.prev('tr')
		if(prev.length == 1){
			$.get( './ajax/row.php', { pageid:parent.attr("pageid"), displaycell: parent.attr("displaycell"), cellorder: parent.attr("cellorder"), sens: 'up' });
			parent.prev().before(parent);
			parent.attr("cellorder", parent.attr("cellorder")-1);
			prev.attr("cellorder", prev.attr("cellorder")+1);
		}
	})
	$('.downArrow').live('click', function(){
		var parent = getParent(this)
		var next = parent.next('tr')
		if(next.length == 1){ 
			$.get( './ajax/row.php', { pageid:parent.attr("pageid"), displaycell: parent.attr("displaycell"), cellorder: parent.attr("cellorder"), sens: 'down' });
			parent.next().after(parent);
			parent.attr("cellorder", parent.attr("cellorder")+1);
			prev.attr("cellorder", prev.attr("cellorder")-1);
		}
	});
})
</script>
<!-- END PHP TEMPLATE -->