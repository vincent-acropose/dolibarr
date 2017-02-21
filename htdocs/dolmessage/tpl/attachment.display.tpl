<?php 
/**
*/
	global $Message; 
	

if($Message->GetId() > 0 )
	$path = 'id='.$Message->GetId();
else 
	$path = 'uid='.$Message->GetUid();
?>



<article >
	<iframe src="<?php echo dol_buildpath('/dolmessage/core/ajax/ajax.php', 1).'?action=attachment&'.$path.'&attach='.GETPOST('attach') ?>" style="width:100%; height:350px;"></iframe> 
</article>
	


