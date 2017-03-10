<?php


// $massaction must be defined
// $objectclass and $$objectlabel must be defined
// $toselect may be defined

$error=0;

$reprogramup = GETPOST('reprogramup','int');
$reprogramdatemonth = GETPOST('reprogramdatemonth', 'int');
$reprogramdateday = GETPOST('reprogramdateday', 'int');
$reprogramdateyear = GETPOST('reprogramdateyear', 'int');
$datereprogram = '';

// Protection
if (empty($objectclass))
{
	dol_print_error(null, 'include of actions_agenda.inc.php is done but var $massaction or $objectclass was not defined');
	exit;
}

// Mass actions. Controls on number of lines checked
$maxformassaction=1000;
if (! empty($massaction) && count($toselect) < 1)
{
	$error++;
	setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (! $error && count($toselect) > $maxformassaction)
{
	setEventMessages($langs->trans('TooManyRecordForMassAction',$maxformassaction), null, 'errors');
	$error++;
}

// Check either number or date
if( ! empty($reprogramdatemonth.$reprogramdateday.$reprogramdateyear) && ! empty($reprogramup)) {
	setEventMessages('PostponedNumberCanBeChooseOnlyWithoutDate', '', 'errors');
	$massaction = 'reprogram';
	$error++;
}

if(! empty($reprogramdatemonth.$reprogramdateday.$reprogramdateyear)) {
	$datereprogram=dol_mktime(0, 0, 0, $reprogramdatemonth, $reprogramdateday, $reprogramdateyear);
}

if (! $error && $massaction == 'confirm_reprogram')
{
	$objecttmp=new ActionComm($db);
	$nbok = 0;
	
	foreach($toselect as $toselectid)
	{
		$result=$objecttmp->fetch($toselectid);
		if ($result > 0)
		{
			if($reprogramup > 0) {
				$objecttmp->datep = dol_time_plus_duree($objecttmp->datep, $reprogramup, 'd');				
			} else {
				$objecttmp->datep = $datereprogram;
			}
			if ($user->rights->agenda->allactions->create ||
					(($objecttmp->authorid == $user->id || $objecttmp->userownerid == $user->id) && $user->rights->agenda->myactions->create))
			{
				$ret = $objecttmp->update($user);
				$nbok++;
			}
		}
		else
		{
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}
	if (! $error)
	{
		if ($nbok > 1) setEventMessages($langs->trans("RecordsPostponed", $nbok), null, 'mesgs');
	
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}
