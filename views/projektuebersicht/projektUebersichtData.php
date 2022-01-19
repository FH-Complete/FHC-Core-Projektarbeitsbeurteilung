<?php
$STUDIENSEMESTER = '\''.$this->variablelib->getVar('projektuebersicht_studiensemester').'\'';
$ERSTBEGUTACHTER = '\'Erstbegutachter\'';
$ZWEITBEGUTACHTER = '\'Zweitbegutachter\'';

$query = '
		SELECT DISTINCT(beurteilung.projektarbeit_id) AS "ProjectWorkID",
			arbeit.titel AS "Titel",
			Erstbegutachter.vorname AS "ErstVorname",
			Erstbegutachter.nachname AS "ErstNachname",
			Erstbegutachter.betreuer_person_id AS "ErstPersonID",
			Erstbegutachter.abgeschicktamum AS "ErstAbgeschickt",
			Zweitbegutachter.vorname AS "ZweitVorname",
			Zweitbegutachter.nachname AS "ZweitNachname",
			Zweitbegutachter.betreuer_person_id AS "ZweitPersonID",
			Zweitbegutachter.uid as "ZweitUID",
			Zweitbegutachter.abgeschicktamum AS "ZweitAbgeschickt",
			student.student_uid as "StudentID",
			stuperson.vorname as "StudentVorname",
			stuperson.nachname as "StudentNachname",
			arbeit.note AS "Note",
			arbeit.abgabedatum AS "Abgabedatum"
		FROM extension.tbl_projektarbeitsbeurteilung beurteilung
		JOIN lehre.tbl_projektarbeit arbeit ON beurteilung.projektarbeit_id = arbeit.projektarbeit_id
		JOIN lehre.tbl_projekttyp USING (projekttyp_kurzbz)
		JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
		JOIN public.tbl_student student ON arbeit.student_uid = student.student_uid
		JOIN public.tbl_benutzer stubenutzer ON student.student_uid = stubenutzer.uid
		JOIN public.tbl_person stuperson ON stubenutzer.person_id = stuperson.person_id
		FULL JOIN
			(
				(
					SELECT sbeurteilung.betreuer_person_id,
							sbeurteilung.projektarbeit_id,
							p.vorname,
							p.nachname,
							sbeurteilung.abgeschicktamum
					FROM extension.tbl_projektarbeitsbeurteilung sbeurteilung
					JOIN public.tbl_person p ON sbeurteilung.betreuer_person_id = p.person_id
					WHERE sbeurteilung.betreuerart_kurzbz = '. $ERSTBEGUTACHTER .'
				)
			) Erstbegutachter ON (beurteilung.projektarbeit_id = Erstbegutachter.projektarbeit_id)
		FULL JOIN
			 (
				 (
					SELECT sbeurteilung.betreuer_person_id,
							sbeurteilung.projektarbeit_id,
							p.vorname,
							p.nachname,
							sbeurteilung.abgeschicktamum,
							tbl_benutzer.uid
					FROM extension.tbl_projektarbeitsbeurteilung sbeurteilung
					JOIN public.tbl_person p ON sbeurteilung.betreuer_person_id = p.person_id
					LEFT JOIN public.tbl_benutzer on p.person_id = tbl_benutzer.person_id
					WHERE sbeurteilung.betreuerart_kurzbz = '. $ZWEITBEGUTACHTER .'
					AND tbl_benutzer.aktiv OR tbl_benutzer.aktiv IS NULL
				 )
			 ) Zweitbegutachter ON (beurteilung.projektarbeit_id = Zweitbegutachter.projektarbeit_id)
		WHERE studiensemester_kurzbz = '. $STUDIENSEMESTER .'
		ORDER BY beurteilung.projektarbeit_id DESC;';

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'projektarbeitsbeurteilung',
	'datasetName' => 'projektuebersicht',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'admin',
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
		ucfirst($this->p->t('ui', 'projektarbeit')) . ' ' . $this->p->t('global', 'uploaddatum')
	),
	'formatRow' => function($datasetRaw) {

		if ($datasetRaw->{'ZweitPersonID'} !== null && $datasetRaw->{'ErstPersonID'} !== null && $datasetRaw->{'ZweitUID'} === null)
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
		}
		else
		{
			$datasetRaw->{'Note'} = '-';
		}
		$datasetRaw->{'Download'} = $download;

		if ($datasetRaw->{'Abgabedatum'} !== null)
		{
			$datasetRaw->{'Abgabedatum'} = date_format(date_create($datasetRaw->{'Abgabedatum'}), 'Y-m-d');
		}
		else
			$datasetRaw->{'Abgabedatum'} = '-';

		if ($datasetRaw->{'ErstAbgeschickt'} !== null)
		{
			$datasetRaw->{'ErstAbgeschickt'} = date_format(date_create($datasetRaw->{'ErstAbgeschickt'}), 'Y-m-d H:i');

			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
				'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
				$datasetRaw->{'ProjectWorkID'},
				$datasetRaw->{'ErstPersonID'},
				$datasetRaw->{'ErstAbgeschickt'}
			);
		}
		else
		{
			$datasetRaw->{'ErstAbgeschickt'} = '-';
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'erstBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
		}

		if ($datasetRaw->{'ZweitAbgeschickt'} !== null)
		{
			$datasetRaw->{'ZweitAbgeschickt'} = date_format(date_create($datasetRaw->{'ZweitAbgeschickt'}), 'Y-m-d H:i');;

			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = sprintf(
				'<button class="freischalten" data-projektid="%s" data-personid="%s" data-abgeschickt="%s">' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischalten')) . '</button>',
				$datasetRaw->{'ProjectWorkID'},
				$datasetRaw->{'ZweitPersonID'},
				$datasetRaw->{'ZweitAbgeschickt'}
			);
		}
		else
		{
			$datasetRaw->{'ZweitAbgeschickt'} = '-';
			$datasetRaw->{(ucfirst($this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter')) . ' ' . ucfirst($this->p->t('projektarbeitsbeurteilung', 'freischaltung')))} = '-';
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

		return $datasetRaw;
	}
);

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
?>
