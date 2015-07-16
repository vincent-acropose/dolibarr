<?php
/* Copyright (C) 2009-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */
?>
 
<!-- BEGIN PHP TEMPLATE -->

<?php 
echo $this->tpl['action_delete'];
?>

<table class="noborder">

<tr class="liste_titre">

   <td><?php echo $langs->trans('ID'); ?></td>
   <td align="left"><?php echo $langs->trans('Label'); ?></td>
   <td align="left"><?php echo $langs->trans('Description'); ?></td>
   <td align="left"><?php echo $langs->trans('Dashdotted'); ?></td>
   <td align="left"><?php echo $langs->trans('Bgcolor'); ?></td>
   <td align="left"><?php echo $langs->trans('Bordercolor'); ?></td>
   <td align="left"><?php echo $langs->trans('Textcolor'); ?></td>
   <td align="center"><?php echo $langs->trans('Status'); ?></td>
   <td align="center" colspan="2">&nbsp;</td>
</tr>

<?php
$var=true;
foreach ($this->tpl['designs'] as $design) {
?>

<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $design->id; ?></td>
	<td align="left"><?php echo $design->label; ?></td>
	<td align="left"><?php echo $design->description; ?></td>
	<td align="left"><?php echo ($design->options['dashdotted']?$langs->trans('Dashdotted'):$langs->trans('No')); ?></td>
	<?php $bgcolor = html2rgb($design->options['bgcolor']); ?>
	<td align="left" style="background-color:rgb(<?php echo implode(",", $bgcolor); ?>);color: rgb(255, 255, 255);"><?php echo implode(",", $bgcolor); ?></td>
	<?php $bordercolor = html2rgb($design->options['bordercolor']) ?>
	<td align="left" style="background-color:rgb(<?php echo implode(",", $bordercolor); ?>);color: rgb(255, 255, 255);"><?php echo implode(",", $bordercolor); ?></td>
	<?php $textcolor = html2rgb($design->options['textcolor']) ?>
	<td align="left" style="background-color:rgb(<?php echo implode(",", $textcolor); ?>);color: rgb(255, 255, 255);"><?php echo implode(",", $textcolor); ?></td>
    <td align="center" width="30">
    <?php
    if ($design->active) 
	{
    	echo '<a href="'.$_SERVER["PHP_SELF"].'?id='.$design->id.'&amp;action=setactive&amp;value=0">'.$this->tpl['img_on'].'</a>';
    } 
	else 
	{
    	echo '<a href="'.$_SERVER["PHP_SELF"].'?id='.$design->id.'&amp;action=setactive&amp;value=1">'.$this->tpl['img_off'].'</a>';
    }
    ?>
    </td>
    
    <td align="center" width="20">
		<?php echo '<a href="'.$_SERVER["PHP_SELF"].'?id='.$design->id.'&amp;action=edit">'.$this->tpl['img_modify'].'</a>'; ?>
	</td>
	<td align="center" width="20">
		<?php if ($design->id != $conf->global->ULTIMATE_DESIGN) echo '<a href="'.$_SERVER["PHP_SELF"].'?id='.$design->id.'&amp;action=delete">'.$this->tpl['img_delete'].'</a>'; ?>
	</td>
</tr>
<?php
$var=!$var;
} 
?>

</table>
</div>

<div class="tabsAction">
<a class="butAction" href="<?php echo $_SERVER["PHP_SELF"]; ?>?action=create"><?php echo $langs->trans('AddDesign'); ?></a> 
</div>
<!-- Here is the markup our JS creates for you --> 
<div class="joyride-tip-guide"> 
	<span class="joyride-nub top"></span> 
	<div class="joyride-content-wrapper">
		<p>Hello and welcome to the Joyride documentation page.</p> 
		<a href="#" class="small button joyride-next-tip">Next</a> 
		<a href="#close" class="joyride-close-tip">&times;</a> 
	</div> 
 </div>
<!-- At the bottom of your page but inside of the body tag --> 
<div>
<ol class="joyride-list" data-joyride> 
	<li data-id="firstStop" data-text="Next" data-options="tip_location: top; prev_button: false"> 
		<p>Hello and welcome to the Joyride documentation page.</p> 
	</li> 
	<li data-id="numero1" data-class="custom so-awesome" data-text="Next" data-prev-text="Prev"> 
		<h4>Stop #1</h4> 
		<p>You can control all the details for you tour stop. Any valid HTML will work inside of Joyride.</p> 
	</li> 
	<li data-id="numero2" data-button="Next" data-prev-text="Prev" data-options="tip_location:top;tip_animation:fade"> 
		<h4>Stop #2</h4> 
		<p>Get the details right by styling Joyride with a custom stylesheet!</p> 
	</li> 
	<li data-button="End" data-prev-text="Prev"> 
		<h4>Stop #3</h4> 
		<p>It works as a modal too!</p> 
	</li> 
</ol>
</div>
<script>
    //$(document).foundation();
	$(document).foundation('joyride', 'start');
</script>

<!-- END PHP TEMPLATE -->