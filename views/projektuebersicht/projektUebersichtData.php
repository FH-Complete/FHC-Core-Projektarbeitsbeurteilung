<?php
$STUDIENSEMESTER = '\''.$this->variablelib->getVar('projektuebersicht_studiensemester').'\'';
$ERSTBEGUTACHTER = '\'Erstbegutachter\'';
$BEGUTACHTER = '\'Begutachter\'';
$ZWEITBEGUTACHTER = '\'Zweitbegutachter\'';
$KOMISSION = '\'Kommission\'';

$oeKurz = '\''. implode('\',\'', $oeKurz) . '\'';

$query = '
		SELECT DISTINCT(pbeurteilung.projektarbeit_id) AS "ProjectWorkID",
			parbeit.titel AS "Titel",
			Erstbegutachter.vorname AS "ErstVorname",
			Erstbegutachter.nachname AS "ErstNachname",
			Erstbegutachter.person_id AS "ErstPersonID",
			Erstbegutachter.abgeschickt AS "ErstAbgeschickt",
			Zweitbegutachter.vorname AS "ZweitVorname",
			Zweitbegutachter.nachname AS "ZweitNachname",
			Zweitbegutachter.person_id AS "ZweitPersonID",
			Zweitbegutachter.uid as "ZweitUID",
			Zweitbegutachter.abgeschickt AS "ZweitAbgeschickt",
			student.student_uid as "StudentID",
			stuperson.vorname as "StudentVorname",
			stuperson.nachname as "StudentNachname",
			parbeit.note AS "Note",
			parbeit.abgabedatum AS "Abgabedatum",
			sg.kurzbzlang AS "Studiengang",
			Kommission.Mitglieder AS "Kommissionsmitglieder"
		FROM extension.tbl_projektarbeitsbeurteilung pbeurteilung
		JOIN lehre.tbl_projektarbeit parbeit USING(projektarbeit_id)
		JOIN lehre.tbl_projektbetreuer pbetreuer ON parbeit.projektarbeit_id = pbetreuer.projektarbeit_id
		JOIN lehre.tbl_projekttyp USING (projekttyp_kurzbz)
		JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
		JOIN public.tbl_student student ON parbeit.student_uid = student.student_uid
		JOIN public.tbl_benutzer stubenutzer ON student.student_uid = stubenutzer.uid
		JOIN public.tbl_person stuperson ON stubenutzer.person_id = stuperson.person_id
		JOIN public.tbl_studiengang sg USING(studiengang_kz)
		FULL JOIN 
		(
			(
				SELECT beurteilung.abgeschicktamum as abgeschickt,
						p.vorname,
						p.nachname,
						betreuer.person_id,
						arbeit.projektarbeit_id as ProjektID
				FROM lehre.tbl_projektbetreuer betreuer
				JOIN lehre.tbl_projektarbeit arbeit USING(projektarbeit_id)
				LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurteilung ON arbeit.projektarbeit_id = beurteilung.projektarbeit_id AND betreuer.person_id = beurteilung.betreuer_person_id
				LEFT JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				WHERE betreuer.betreuerart_kurzbz = '.$ERSTBEGUTACHTER.' OR betreuer.betreuerart_kurzbz = '. $BEGUTACHTER .'
			)
		) Erstbegutachter ON parbeit.projektarbeit_id = Erstbegutachter.ProjektID
		FULL JOIN
		(
			(
				SELECT beurteilung.abgeschicktamum as abgeschickt,
						p.vorname,
						p.nachname,
						betreuer.person_id,
						benutzer.uid,
						arbeit.projektarbeit_id as ProjektID
				FROM lehre.tbl_projektbetreuer betreuer
				JOIN lehre.tbl_projektarbeit arbeit USING(projektarbeit_id)
				LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurteilung ON arbeit.projektarbeit_id = beurteilung.projektarbeit_id AND betreuer.person_id = beurteilung.betreuer_person_id
				LEFT JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				LEFT JOIN public.tbl_benutzer benutzer ON p.person_id = benutzer.person_id
				WHERE betreuer.betreuerart_kurzbz = '.$ZWEITBEGUTACHTER.' AND benutzer.aktiv OR benutzer.aktiv IS NULL
				)
		) Zweitbegutachter ON parbeit.projektarbeit_id = Zweitbegutachter.ProjektID
		FULL JOIN 
		(
			(
				SELECT ARRAY_TO_STRING(ARRAY_AGG(DISTINCT (p.vorname || \' \' || p.nachname)), \', \') AS Mitglieder,
						arbeit.projektarbeit_id as ProjektID
				FROM lehre.tbl_projektbetreuer betreuer
				JOIN lehre.tbl_projektarbeit arbeit USING(projektarbeit_id)
				JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				JOIN public.tbl_benutzer benutzer ON p.person_id = benutzer.person_id
				WHERE betreuer.betreuerart_kurzbz = '.$KOMISSION.'
				AND benutzer.aktiv
				GROUP BY arbeit.projektarbeit_id
			)
		) Kommission ON parbeit.projektarbeit_id = Kommission.ProjektID
		WHERE studiensemester_kurzbz = '. $STUDIENSEMESTER .' AND oe_kurzbz IN ('. $oeKurz .')
		ORDER BY pbeurteilung.projektarbeit_id DESC;';

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'projektarbeitsbeurteilung',
	'datasetName' => 'projektuebersicht',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'assistenz',
	'datasetRepresentation' => 'tablesorter',
	'tableUniqueId' => 'projectWorkAssessment',
	'hideOptions' => false,
	'additionalColumns' => array(
								(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung'))),
								(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung'))),
								(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken'))),
								'Download'),
	'columnsAliases' => array(
		'ProjektarbeitID',
		ucfirst($this->p->t('ui', 'projektarbeit')) . ' ' . $this->p->t('global', 'titel'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) .' ' . $this->p->t('person', 'vorname'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) .' ' . $this->p->t('person', 'nachname'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')). ' PersonID',
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) .' ' . $this->p->t('global', 'abgeschickt'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) .' ' . $this->p->t('person', 'vorname'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) .' ' . $this->p->t('person', 'nachname'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')). ' PersonID',
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . $this->p->t('person', 'uid'),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) .' ' . $this->p->t('global', 'abgeschickt'),
		ucfirst($this->p->t('person', 'student')) . $this->p->t('person', 'uid'),
		ucfirst($this->p->t('person', 'student')) . ' ' . $this->p->t('person', 'vorname'),
		ucfirst($this->p->t('person', 'student')) . ' ' .$this->p->t('person', 'nachname'),
		ucfirst($this->p->t('ui', 'projektarbeit')) . ' ' . $this->p->t('lehre', 'note'),
		ucfirst($this->p->t('ui', 'projektarbeit')) . ' ' . $this->p->t('global', 'uploaddatum'),
		ucfirst($this->p->t('lehre', 'studiengang')),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'kommissionsmitglieder'))
	),
	'formatRow' => function($datasetRaw) {

		if ($datasetRaw->{'ZweitPersonID'} !== null && $datasetRaw->{'ErstPersonID'} !== null && $datasetRaw->{'ZweitUID'} === null && $datasetRaw->{'Note'} === null)
		{
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = sprintf(
				'<button class="resend" data-personid="%s" data-projektid="%s"  data-studentid="%s">' . ucfirst($this->p->t('ui', 'senden')) . '</button>',
				$datasetRaw->{'ErstPersonID'},
				$datasetRaw->{'ProjectWorkID'},
				$datasetRaw->{'StudentID'}
			);
		}
		else
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = '-';

		$download = '';
		if ($datasetRaw->{'Note'} !== null)
		{

			if ($datasetRaw->{'ErstAbgeschickt'} !== null)
			{
				$download = sprintf(
					'<a href="%s&xsl=%s&betreuerart_kurzbz=%s&projektarbeit_id=%s&person_id=%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . '</a>',
					APP_ROOT.'/cis/private/pdfExport.php?xml=projektarbeitsbeurteilung.xml.php',
					'Projektbeurteilung',
					'Erstbegutachter',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ErstPersonID'}
				);
			}

			if ($datasetRaw->{'ErstAbgeschickt'} !== null & $datasetRaw->{'ZweitAbgeschickt'} !== null)
				$download .= '/';

			if ($datasetRaw->{'ZweitAbgeschickt'} !== null)
			{
				$download .= sprintf(
					'<a href="%s&xsl=%s&betreuerart_kurzbz=%s&projektarbeit_id=%s&person_id=%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . '</a>',
					APP_ROOT.'/cis/private/pdfExport.php?xml=projektarbeitsbeurteilung.xml.php',
					'Projektbeurteilung',
					'Zweitbegutachter',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ZweitPersonID'}
				);
			}

			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
		}
		else
		{
			$datasetRaw->{'Note'} = '-';

			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
				'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
				$datasetRaw->{'ProjectWorkID'},
				$datasetRaw->{'ErstPersonID'},
				$datasetRaw->{'ErstAbgeschickt'}
			);

			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
				'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
				$datasetRaw->{'ProjectWorkID'},
				$datasetRaw->{'ZweitPersonID'},
				$datasetRaw->{'ZweitAbgeschickt'}
			);

		}
		$datasetRaw->{'Download'} = $download;

		if ($datasetRaw->{'Abgabedatum'} !== null)
		{
			$datasetRaw->{'Abgabedatum'} = date_format(date_create($datasetRaw->{'Abgabedatum'}), 'd.m.Y');
		}
		else
			$datasetRaw->{'Abgabedatum'} = '-';

		if ($datasetRaw->{'ErstAbgeschickt'} !== null)
		{
			$datasetRaw->{'ErstAbgeschickt'} = date_format(date_create($datasetRaw->{'ErstAbgeschickt'}), 'd.m.Y H:i');
		}
		else
		{
			$datasetRaw->{'ErstAbgeschickt'} = '-';
		}

		if ($datasetRaw->{'ZweitAbgeschickt'} !== null)
		{
			$datasetRaw->{'ZweitAbgeschickt'} = date_format(date_create($datasetRaw->{'ZweitAbgeschickt'}), 'd.m.Y H:i');;
		}
		else
		{
			$datasetRaw->{'ZweitAbgeschickt'} = '-';
		}

		if ($datasetRaw->{'Titel'} === null)
			$datasetRaw->{'Titel'} = '-';

		if ($datasetRaw->{'ErstVorname'} === null)
			$datasetRaw->{'ErstVorname'} = '-';

		if ($datasetRaw->{'ErstNachname'} === null)
			$datasetRaw->{'ErstNachname'} = '-';

		if ($datasetRaw->{'ErstPersonID'} === null)
			$datasetRaw->{'ErstPersonID'} = '-';

		if ($datasetRaw->{'ZweitVorname'} === null)
			$datasetRaw->{'ZweitVorname'} = '-';

		if ($datasetRaw->{'ZweitNachname'} === null)
			$datasetRaw->{'ZweitNachname'} = '-';

		if ($datasetRaw->{'ZweitPersonID'} === null)
			$datasetRaw->{'ZweitPersonID'} = '-';

		if ($datasetRaw->{'Kommissionsmitglieder'} === null)
			$datasetRaw->{'Kommissionsmitglieder'} = '-';

		return $datasetRaw;
	}
);

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
?>
