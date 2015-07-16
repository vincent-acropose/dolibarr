<?php
/* Copyright (C) 2009-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Philippe Grand <philippe.grand@atoo-net.com>
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
<?php require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php'; ?>
<?php $form = new Form($db); ?>

<form name="form_index" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="" />

<?php $var=true; ?>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("DesignInfo"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><span class="fieldrequired"><?php echo $langs->trans("Label"); ?></span></td>
	<td><input name="label" size="30" value="<?php echo $this->tpl['label']; ?>" /></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td valign="top"><?php echo $langs->trans("Description"); ?></td>
	<td><textarea class="flat" name="description" cols="60" rows="<?php echo ROWS_3; ?>"><?php echo $this->tpl['description']; ?></textarea></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("BackgroundColorByDefault"); ?></td>
	<td><?php echo $this->tpl['select_bgcolor']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("BorderColorByDefault"); ?></td>
	<td><?php echo $this->tpl['select_bordercolor']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("TextcolorByDefault"); ?></td>
	<td><?php echo $this->tpl['select_textcolor']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("QRcodeColorByDefault"); ?></td>
	<td><?php echo $this->tpl['select_qrcodecolor']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("SetBorderToDashDotted"); ?></td>
	<td><?php echo $this->tpl['select_dashdotted']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("HideByDefaultProductTvaInsideUltimatepdf"), $langs->trans("SelectWithoutVatDescription")); ?></td>
	<td><?php echo $this->tpl['select_withoutvat']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetInvertSenderRecipient"), $langs->trans("SetInvertSenderRecipientDescription")); ?></td>
	<td><?php echo $this->tpl['invertSenderRecipient']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("SetFontToWhatYouWant"); ?></td>
	<td><?php echo $this->tpl['select_otherfont']; ?></td>
</tr>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("UseBackGround"), $langs->trans("UseBackGroundDescription")); ?></td>
	<td><input name="usebackground" size="30" value="<?php echo $this->tpl['usebackground']; ?>" /></td>
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SetLogoHeigth"); ?></td>
	<td><?php echo $langs->trans("Logo"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>

<?php global $mysoc; ?>
<?php  if (! empty($mysoc->logo))
    {
		$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
	}
	else
    {
        echo '<img height="30" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
    }?>

<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetLogoHeigth"), $langs->trans("SetLogoHeigthDescription")); ?></td>
	<td>
		<div id="container" class="ui-widget-content">
			<div class="ui-state-active"> 				
				<img id="resizable-1" src="<?php echo $urllogo; ?>" />
			</div>
		</div>
	</td>
	<td><input type="text" name="logoheight" id="logoheight" size="30" value="<?php echo $this->tpl['logoheight']; ?>" /><input type="text" name="logowidth" id="logowidth" size="30" value="<?php echo $this->tpl['logowidth']; ?>" /><br><span id="resizable-2"></span></td>	
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SelectAnOtherlogo"); ?></td>
	<td><?php echo $langs->trans("OtherLogo"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SelectAnOtherlogo"), $langs->trans("OtherlogoDescription")); ?></td>
	<td><input type="url" id="otherlogo" name="otherlogo" size="80" placeholder="Ex : http://www.dolibarr.fr/templates/dolibarr/images/bg2.png" value="<?php echo $this->tpl['select_otherlogo']; ?>" /></td>
	<td><button type="button" id="maj_img"><?php echo $langs->trans("Update"); ?></button></td>
</tr>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetLogoHeigth"), $langs->trans("SetLogoHeigthDescription")); ?></td>
	<td>
		<div id="container" class="ui-widget-content">
			<div id="ui-state-active" class="ui-state-active"> 
				<img id="resizable-3" src="<?php echo (empty($this->tpl['select_otherlogo'])?DOL_URL_ROOT.'/theme/common/nophoto.jpg':$this->tpl['select_otherlogo']); ?>" />
			</div>		
		</div>
	</td>
	<td><input type="text" name="otherlogoheight" id="otherlogoheight" size="30" value="<?php echo $this->tpl['otherlogoheight']; ?>" /><input type="text" name="otherlogowidth" id="otherlogowidth" size="30" value="<?php echo $this->tpl['otherlogowidth']; ?>" /><br><span id="resizable-4"></span></td>	
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SetPdfMargin"); ?></td>
	<td><?php echo $langs->trans("Margins"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetPdfMargin"), $langs->trans("SetPdfMarginDescription")); ?></td>
	<td>
		<div id="container2" class="ui-widget-content">
			<div id="resizable-5" class="ui-state-active"> 				
				<h3 class="ui-widget-header"><?php echo $langs->trans("SetPdfMargin"); ?></h3>
			</div>
		</div>
	</td>
	<td><input type="text" name="marge_gauche" id="marge_gauche" size="30" value="<?php echo $this->tpl['marge_gauche']; ?>" /><br><input type="text" name="marge_droite" id="marge_droite" size="30" value="<?php echo $this->tpl['marge_droite']; ?>" /><br><input type="text" name="marge_haute" id="marge_haute" size="30" value="<?php echo $this->tpl['marge_haute']; ?>" /><br><input type="text" name="marge_basse" id="marge_basse" size="30" value="<?php echo $this->tpl['marge_basse']; ?>" /><br><span id="resizable-6"></span></td>	
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SetRefColumn"); ?></td>
	<td colspan="2"><?php echo $langs->trans("RefWidth"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SelectWithRef"), $langs->trans("SelectWithRefDescription")); ?></td>
	<td colspan="3"><?php echo $this->tpl['select_withref']; ?></td>
</tr>
<br>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetRefWidth"), $langs->trans("SetRefWidthDescription")); ?></td>
	<td>
		<div id="container3" class="ui-widget-content">
			<div id="resizable-7" class="ui-state-active"> 				
				<h3 class="ui-widget-header"><?php echo $langs->trans("SetRefWidth"); ?></h3>
			</div>
		</div>
	</td>
	<td><input type="text" name="widthref" id="widthref" size="30" value="<?php echo $this->tpl['widthref']; ?>" /><br><span id="resizable-8"></span></td>
	<td><button type="button" id="maj_img"><?php echo $langs->trans("Update"); ?></button></td>	
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SetImageColumn"); ?></td>
	<td colspan="2"><?php echo $langs->trans("Parameters"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>
<br>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetImageWidth"), $langs->trans("SetImageWidthDescription")); ?></td>
	<td>
		<div id="container4" class="ui-widget-content">
			<div id="resizable-9" class="ui-state-active"> 				
				<h3 class="ui-widget-header"><?php echo $langs->trans("SetImageWidth"); ?></h3>
			</div>
		</div>
	</td>
	<td><input type="text" name="imglinesize" id="imglinesize" size="30" value="<?php echo $this->tpl['imglinesize']; ?>" /><br><span id="resizable-10"></span></td>
	<td><button type="button" id="maj_img"><?php echo $langs->trans("Update"); ?></button></td>
</tr>
</table>
<br>

<table class="noborder">
<tr class="liste_titre">
	<td width="35%"><?php echo $langs->trans("SetFreeTextBloc"); ?></td>
	<td colspan="2"><?php echo $langs->trans("Parameters"); ?></td>
	<td><?php echo $langs->trans("Value"); ?></td>
</tr>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $langs->trans("SetFontSizeForFreeText"); ?></td>
	<td colspan="3"><input name="freetextfontsize" size="30" value="<?php echo $this->tpl['select_freetextfontsize']; ?>" /></td>
</tr>
<br>
<?php $var=!$var; ?>
<tr <?php echo $bc[$var]; ?>>
	<td><?php echo $form->textwithpicto($langs->trans("SetHeightForFreeText"), $langs->trans("SetHeightForFreeTextDescription")); ?></td>
	<td>
		<div id="container5" class="ui-widget-content">
			<div id="resizable-11" class="ui-state-active"> 				
				<h3 class="ui-widget-header"><?php echo $langs->trans("SetHeightForFreeText"); ?></h3>
			</div>
		</div>
	</td>
	<td><input type="text" name="heightforfreetext" id="heightforfreetext" size="30" value="<?php echo $this->tpl['select_heightforfreetext']; ?>" /><br><span id="resizable-12"></span></td>
	<td><button type="button" id="maj_img"><?php echo $langs->trans("Update"); ?></button></td>
</tr>
</table>
<br>

<div class="tabsAction">
<input type="submit" class="butAction linkobject" name="add" value="<?php echo $langs->trans('Add'); ?>" />
<input type="submit" class="butAction linkobject" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>" />
</div>
<!-- Javascript -->
    <script>
        $(function() {
		const k=72/254;
            $( "#resizable-1" ).resizable({ 
				containment: "#container",
			    minHeight: 70,
			    minWidth: 150,
				maxHeight: #height,
				maxWidth: #width,
                resize: function (event, ui)
                {
					$("#resizable-2").text ("<?php echo $langs->trans("Width"); ?> = " + Math.round(ui.size.width*k) + "mm" +
						", <?php echo $langs->trans("Height"); ?> = " + Math.round(ui.size.height*k) + "mm");
					$("#logoheight").val(Math.round(ui.size.height*k));
					$("#logowidth").val(Math.round(ui.size.width*k));
                }
            });
        });
    </script>
	<script>
        $(function() {
		const k=72/254;
            $( "#resizable-3" ).resizable({ 
				containment: "#container",
			    minHeight: 70,
			    minWidth: 150,
				maxHeight: 250,
				maxWidth: 340,
                resize: function (event, ui)
                {
					$("#resizable-4").text ("<?php echo $langs->trans("Width"); ?> = " + Math.round(ui.size.width*k) + "mm" +
						", <?php echo $langs->trans("Height"); ?> = " + Math.round(ui.size.height*k) + "mm");
					$("#otherlogoheight").val(Math.round(ui.size.height*k));
					$("#otherlogowidth").val(Math.round(ui.size.width*k));
                }
            });
        });
    </script>
	<script>
	  $(function() {
		$('#maj_img').click(function() {
		  $('#resizable-3').attr("src",$('#otherlogo').val()).load()
		});
	  });
	</script>
	<script>
	  $(function() {
		$("#resizable-5").resizable({ 
		containment: "#container2",
		minHeight: 257,
		minWidth: 170,
		maxHeight: 297,
		maxWidth: 210,
		resize: function (event, ui)
			{
			var posleft=ui.position.left;
			var posright=210 - ui.size.width - ui.position.left;
			var postop=ui.position.top;			
			var posbottom=297 - ui.size.height - ui.position.top;		
				if(posleft < 0)
					posleft=0;
				if(posright < 0)
					posright=0;
				if(postop < 0)
					postop=0;
				if(posbottom < 0)
					posbottom=0;	
				$("#resizable-6").text ("<?php echo $langs->trans("MargeGauche"); ?> = " + Math.round(posleft) + "px" +
					", <?php echo $langs->trans("MargeDroite"); ?> = " + Math.round(posright) + "px" +
					", <?php echo $langs->trans("MargeHaute"); ?> = " + Math.round(postop) + "px" +
					", <?php echo $langs->trans("MargeBasse"); ?> = " + Math.round(posbottom) + "px");								
				$("#marge_gauche").val(Math.round(posleft));
				$("#marge_droite").val(Math.round(posright));
				$("#marge_haute").val(Math.round(postop));
				$("#marge_basse").val(Math.round(posbottom));
			},
		handles: "n, e, s, w" });
		var handles = $("#resizable-5").resizable("option", "handles");
		$("#resizable-5").resizable("option", "handles", "n, e, s, w");
		$("#marge_gauche").change(function() {
			var margeleft = parseInt($(this).val());
			var margecurrentleft = parseInt($('#resizable-5').css('left').replace('px',''));
			var margewidth = parseInt($('#resizable-5').css('width').replace('px',''));
			var blockwidth = (margecurrentleft + margewidth) - margeleft;
			$('#resizable-5').css({'left': margeleft + 'px', 'width': blockwidth + 'px'});
			$('#resizable-6').text("<?php echo $langs->trans("MargeGauche"); ?> = " + margeleft + 'px');
		});
		$("#marge_droite").change(function() {
			var margeright = parseInt($(this).val());
			var margecurrentright = parseInt($('#resizable-5').css('right').replace('px',''));
			var margewidth = parseInt($('#resizable-5').css('width').replace('px',''));
			var blockwidth = (margecurrentright + margewidth) - margeright;
			$('#resizable-5').css({'right': margeright + 'px', 'width': blockwidth + 'px'});
			$('#resizable-6').text("<?php echo $langs->trans("MargeDroite"); ?> = " + margeright + 'px');
		});
		$("#marge_haute").change(function() {
			var margetop = parseInt($(this).val());
			var margecurrenttop = parseInt($('#resizable-5').css('top').replace('px',''));
			var margeheight = parseInt($('#resizable-5').css('height').replace('px',''));
			var blockheight = (margecurrenttop + margeheight) - margetop;
			$('#resizable-5').css({'top': margetop + 'px', 'height': blockheight + 'px'});
			$('#resizable-6').text("<?php echo $langs->trans("MargeHaute"); ?> = " + margetop + 'px');
		});
		$("#marge_basse").change(function() {
			var margebottom = parseInt($(this).val());
			var margecurrentbottom = parseInt($('#resizable-5').css('bottom').replace('px',''));
			var margeheight = parseInt($('#resizable-5').css('height').replace('px',''));
			var blockheight = (margecurrentbottom + margeheight) - margebottom;
			$('#resizable-5').css({'bottom': margebottom + 'px', 'height': blockheight + 'px'});
			$('#resizable-6').text("<?php echo $langs->trans("MargeBasse"); ?> = " + margebottom + 'px');
		});
	});
	</script>
	<script>
        $(function() {
            $( "#resizable-7" ).resizable({ 
				containment: "#container3",
				minHeight: 297,
			    minWidth: 10,
				maxWidth: 80,
                resize: function (event, ui)
                {
					var widthref=ui.size.width;
					$("#resizable-8").text ("<?php echo $langs->trans("Width"); ?> = " + Math.round(widthref) + "px");
					$("#widthref").val(Math.round(widthref));
                }
			});				
            $("#widthref").change(function() {	
			var blockwidth = parseInt($(this).val());
			var blockwidthcurrent = parseInt($('#resizable-7').css('width').replace('px',''));
			$('#resizable-7').css({'width': blockwidth + 'px'});
			$('#resizable-8').text("<?php echo $langs->trans("Width"); ?> = " + blockwidth + 'px');
		});
    });
    </script>
	<script>
        $(function() {
            $( "#resizable-9" ).resizable({ 
				containment: "#container4",
				minHeight: 297,
			    minWidth: 16,
				maxWidth: 80,
                resize: function (event, ui)
                {
					var imglinesize=ui.size.width;
					$("#resizable-10").text ("<?php echo $langs->trans("Width"); ?> = " + Math.round(imglinesize) + "px");
					$("#imglinesize").val(Math.round(imglinesize));
                },
				handles: "w,sw" });
			var handles = $("#resizable-9").resizable("option", "handles");
			$("#resizable-9").resizable("option", "handles", "w,sw");
			$('.ui-resizable-sw').addClass('ui-icon ui-icon-gripsmall-diagonal-sw');
		$("#imglinesize").change(function() {
			var blockwidth = parseInt($(this).val());
			var blockwidthcurrent = parseInt($('#resizable-9').css('width').replace('px',''));
			var blockleftcurrent = parseInt($('#resizable-9').css('left').replace('px',''));
			var blockleft = blockleftcurrent + (blockwidthcurrent - blockwidth);
			$('#resizable-9').css({'width': blockwidth + 'px'});
			$('#resizable-9').css({'left': blockleft + 'px'});
			$('#resizable-10').text("<?php echo $langs->trans("Width"); ?> = " + blockwidth + 'px');
		});
    });
    </script>
	<script>
        $(function() {
			
            $( "#resizable-11" ).resizable({ 
				containment: "#container5",
			    minHeight: 10,
			    minWidth: 210,
				maxHeight: 80,
                resize: function (event, ui)
                {
					$("#resizable-12").text ("<?php echo $langs->trans("Height"); ?> = " + Math.round(ui.size.height) + "px");
					$("#heightforfreetext").val(Math.round(ui.size.height));
                },
				handles: "n" });
			var handles = $("#resizable-11").resizable("option", "handles");
			$("#resizable-11").resizable("option", "handles", "n"); 
		$("#heightforfreetext").change(function() {	
			var blockheight = parseInt($(this).val());
			var blockheightcurrent = parseInt($('#resizable-11').css('height').replace('px',''));
			var blocktopcurrent = parseInt($('#resizable-11').css('top').replace('px',''));
			var blocktop = blocktopcurrent + (blockheightcurrent - blockheight);
			$('#resizable-11').css({'height': blockheight + 'px'});
			$('#resizable-11').css({'top': blocktop + 'px'});
			$('#resizable-12').text("<?php echo $langs->trans("Height"); ?> = " + blockheight + 'px');
		});			
    });
    </script>
	

</form>
<!-- END PHP TEMPLATE -->