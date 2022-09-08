<?php
$STUDIENSEMESTER = '\''.$this->variablelib->getVar('projektuebersicht_studiensemester').'\'';
$ERSTBEGUTACHTER = '\'Erstbegutachter\'';
$BEGUTACHTER = '\'Begutachter\'';
$ZWEITBEGUTACHTER = '\'Zweitbegutachter\'';
$KOMISSIONVORSITZ = '\'Senatsvorsitz\'';
$KOMISSIONPRUEFER = '\'Senatspruefer\'';

$oeKurz = '\''. implode('\',\'', $oeKurz) . '\'';

$query = '
		SELECT DISTINCT ON (parbeit.projektarbeit_id) parbeit.projektarbeit_id AS "ProjectWorkID",
			parbeit.titel AS "Titel",
			Erstbegutachter.vorname AS "ErstVorname",
			Erstbegutachter.nachname AS "ErstNachname",
			Erstbegutachter.person_id AS "ErstPersonID",
			Erstbegutachter.betreuerart_kurzbz AS "ErstBetreuerart",
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
			UPPER(sg.typ) as "Typ",
			Kommission.Mitglieder AS "Kommissionmitglieder",
			Kommission.MitgliederPersonId AS "KommissionmitgliederPersonId"
		FROM lehre.tbl_projektarbeit parbeit
		JOIN lehre.tbl_projektbetreuer pbetreuer ON parbeit.projektarbeit_id = pbetreuer.projektarbeit_id
		JOIN lehre.tbl_projekttyp USING (projekttyp_kurzbz)
		JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
		JOIN public.tbl_student student ON parbeit.student_uid = student.student_uid
		JOIN public.tbl_benutzer stubenutzer ON student.student_uid = stubenutzer.uid
		JOIN public.tbl_person stuperson ON stubenutzer.person_id = stuperson.person_id
		JOIN public.tbl_studiengang sg USING(studiengang_kz)
		LEFT JOIN extension.tbl_projektarbeitsbeurteilung pbeurteilung ON parbeit.projektarbeit_id = pbeurteilung.projektarbeit_id AND pbetreuer.person_id = pbeurteilung.betreuer_person_id
		FULL JOIN
		(
			(
				SELECT beurteilung.abgeschicktamum as abgeschickt,
						p.vorname,
						p.nachname,
						betreuer.person_id,
						betreuer.betreuerart_kurzbz,
						arbeit.projektarbeit_id as ProjektID
				FROM lehre.tbl_projektbetreuer betreuer
				JOIN lehre.tbl_projektarbeit arbeit USING(projektarbeit_id)
				LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurteilung ON arbeit.projektarbeit_id = beurteilung.projektarbeit_id AND betreuer.person_id = beurteilung.betreuer_person_id
				LEFT JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				WHERE betreuer.betreuerart_kurzbz IN ('.$ERSTBEGUTACHTER.', '. $BEGUTACHTER .', '.$KOMISSIONVORSITZ.')
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
				LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurteilung
					ON arbeit.projektarbeit_id = beurteilung.projektarbeit_id AND betreuer.person_id = beurteilung.betreuer_person_id
				LEFT JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				LEFT JOIN public.tbl_benutzer benutzer ON p.person_id = benutzer.person_id
				WHERE betreuer.betreuerart_kurzbz = '.$ZWEITBEGUTACHTER.'
				AND (benutzer.aktiv OR benutzer.aktiv IS NULL)
			)
		) Zweitbegutachter ON parbeit.projektarbeit_id = Zweitbegutachter.ProjektID
		FULL JOIN
		(
			(
				SELECT ARRAY_TO_STRING(ARRAY_AGG(DISTINCT (
					p.person_id || \' \' || (CASE WHEN benutzer.uid IS NULL THEN TRUE ELSE FALSE END)
					|| \' \' || p.vorname || \' \' || p.nachname
				)), \', \') AS Mitglieder,
					ARRAY_TO_STRING(ARRAY_AGG(DISTINCT (betreuer.person_id)), \', \') AS MitgliederPersonId,
					arbeit.projektarbeit_id as ProjektID
				FROM lehre.tbl_projektbetreuer betreuer
				JOIN lehre.tbl_projektarbeit arbeit USING(projektarbeit_id)
				JOIN public.tbl_person p ON betreuer.person_id = p.person_id
				LEFT JOIN public.tbl_benutzer benutzer ON p.person_id = benutzer.person_id
				WHERE betreuer.betreuerart_kurzbz = '.$KOMISSIONPRUEFER.'
				AND (benutzer.aktiv OR benutzer.aktiv IS NULL)
				GROUP BY arbeit.projektarbeit_id
			)
		) Kommission ON parbeit.projektarbeit_id = Kommission.ProjektID
		WHERE studiensemester_kurzbz = '. $STUDIENSEMESTER .' AND oe_kurzbz IN ('. $oeKurz .')
		ORDER BY parbeit.projektarbeit_id DESC;';

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
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')). ' ' . $this->p->t('projektarbeitsbeurteilung', 'betreuerart'),
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
		ucfirst($this->p->t('global', 'typ')),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'kommissionmitglieder')),
		ucfirst($this->p->t('projektarbeitsbeurteilung', 'kommissionmitglieder')) . ' PersonID'
	),
	'formatRow' => function($datasetRaw) {

		/* Token resend */
		if ($datasetRaw->{'ErstPersonID'} !== null && $datasetRaw->{'Note'} === null)
		{
			if ($datasetRaw->{'ZweitPersonID'} !== null && $datasetRaw->{'ZweitUID'} === null)
			{
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = sprintf(
					'<button class="resend" data-personid="%s" data-projektid="%s"  data-studentid="%s">'
					.' Token senden '.
					'</button>',
					$datasetRaw->{'ErstPersonID'},
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'StudentID'}
				);
			}
			elseif ($datasetRaw->{'KommissionmitgliederPersonId'} !== null)
			{
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = '';
				$mitglieder = explode(', ', $datasetRaw->{'Kommissionmitglieder'});
				foreach ($mitglieder as $kommissionsmitglied)
				{
					$person_data = explode(' ', $kommissionsmitglied);
					if ($person_data[1] == 'false')
						break;
					$kommission_person_id = $person_data[0];
					$voller_name = $person_data[2].' '.$person_data[3];
					$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} .=
					'<div class="kommissionsendtoken">'.
					$voller_name . ': <br />' .
					sprintf(
						'<button class="resend" data-personid="%s" data-projektid="%s" data-studentid="%s" data-kommissionprueferid="%s" >'
						.' Token senden '.
						'</button>',
						$datasetRaw->{'ErstPersonID'},
						$datasetRaw->{'ProjectWorkID'},
						$datasetRaw->{'StudentID'},
						$kommission_person_id
					).
					'</div>';
				}
			}
			else
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = '-';
		}
		else
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'resendToken')))} = '-';

		/* Document download */
		$download = '';
		if ($datasetRaw->{'Note'} !== null)
		{
			if ($datasetRaw->{'ErstAbgeschickt'} !== null)
			{
				// different Dokumentvorlage depending on type
				$xsl = $datasetRaw->{'Typ'} === 'B' ? 'ProjektBeurteilungBA' : 'ProjektBeurteilungMAErst';

				/* Bewertung document Download */
				$download = sprintf(
					'<a href="%s&xsl=%s&betreuerart_kurzbz=%s&projektarbeit_id=%s&person_id=%s"><i class="fa fa-file-pdf-o"></i> '
					. 'Beurteilung'.
					'</a>',
					APP_ROOT.'/content/pdfExport.php?xml=projektarbeitsbeurteilung.xml.php',
					$xsl,
					$datasetRaw->{'ErstBetreuerart'},
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ErstPersonID'}
				);

				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
					'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ErstPersonID'},
					$datasetRaw->{'ErstAbgeschickt'}
				);
			}
			else
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';

			if ($datasetRaw->{'ErstAbgeschickt'} !== null & $datasetRaw->{'ZweitAbgeschickt'} !== null)
				$download .= ' <br /> ';

			if ($datasetRaw->{'ZweitAbgeschickt'} !== null)
			{
				$download .= sprintf(
					'<a href="%s&xsl=%s&betreuerart_kurzbz=%s&projektarbeit_id=%s&person_id=%s"><i class="fa fa-file-pdf-o"></i> '
					. 'Gutachten' .
					'</a>',
					APP_ROOT.'/content/pdfExport.php?xml=projektarbeitsbeurteilung.xml.php',
					'ProjektBeurteilungMAZweit',
					'Zweitbegutachter',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ZweitPersonID'}
				);
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
					'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ZweitPersonID'},
					$datasetRaw->{'ZweitAbgeschickt'}
				);
			}
			else
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';


		}
		else
		{
			$datasetRaw->{'Note'} = '-';

			/* Bewertung freischalten */
			if ($datasetRaw->{'ErstAbgeschickt'} !== null)
			{
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
					'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ErstPersonID'},
					$datasetRaw->{'ErstAbgeschickt'}
				);
			}
			else
			{
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
			}

			if ($datasetRaw->{'ZweitAbgeschickt'} !== null)
			{
				$download .= sprintf(
					'<a href="%s&xsl=%s&betreuerart_kurzbz=%s&projektarbeit_id=%s&person_id=%s"><i class="fa fa-file-pdf-o"></i> '
					. 'Gutachten' .
					'</a>',
					APP_ROOT.'/content/pdfExport.php?xml=projektarbeitsbeurteilung.xml.php',
					'ProjektBeurteilungMAZweit',
					'Zweitbegutachter',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ZweitPersonID'}
				);
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
					'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
					$datasetRaw->{'ProjectWorkID'},
					$datasetRaw->{'ZweitPersonID'},
					$datasetRaw->{'ZweitAbgeschickt'}
				);
			}
			else
			{
				$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
			}
		}
		$datasetRaw->{'Download'} = $download;

		if ($datasetRaw->{'Abgabedatum'} !== null)
		{
			$datasetRaw->{'Abgabedatum'} = date_format(date_create($datasetRaw->{'Abgabedatum'}), 'd.m.Y');
		}
		else
			$datasetRaw->{'Abgabedatum'} = '-';

		if ($datasetRaw->{'ErstBetreuerart'} === null)
			$datasetRaw->{'ErstBetreuerart'} = '-';

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

		if ($datasetRaw->{'Kommissionmitglieder'} === null)
			$datasetRaw->{'Kommissionmitglieder'} = '-';

		return $datasetRaw;
	}
);

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
?>
