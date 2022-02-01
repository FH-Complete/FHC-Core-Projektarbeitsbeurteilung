const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

$("document").ready(function() {

	// temporarily save current form bewertung data
	if ($("#plagiatscheck_unauffaellig").prop('checked') === true)
		Projektarbeitsbeurteilung.bewertungData = Projektarbeitsbeurteilung.getBewertungFormData();

	// refresh plagiatscheck after page load
	Projektarbeitsbeurteilung.refreshPlagiatscheck();
	// refresh grade and points after page load
	Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

	// refresh plagiatscheck after checkbox change
	if ($("#plagiatscheck_unauffaellig").length)
	{
		$("#plagiatscheck_unauffaellig").change(
			function()
			{
				Projektarbeitsbeurteilung.refreshPlagiatscheck();
				Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
			}
		);
	}

	// make title editable
	Projektarbeitsbeurteilung.setTitleEditEvent($("#titleField").text());

	// refresh grade and points after changing Bewertung
	if ($("#beurteilungtbl select").length)
	{
		$("#beurteilungtbl select").change(
			function()
			{
				Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
			}
		);
	}

	// click on save -> initiate saving of entered data
	if ($("#saveBeurteilungBtn").length)
	{
		$("#saveBeurteilungBtn").click(
			function()
			{
				Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(false);
			}
		)
	}

	// click on save and send -> initiate saving of entered data and finish grading
	if ($("#saveSendBeurteilungBtn").length)
	{
		$("#saveSendBeurteilungBtn").click(
			function()
			{
				Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(true);
			}
		)
	}

	// button for sending info mail to committee members
	if ($("#sendKommissionMail").length)
	{
		$("#sendKommissionMail").click(
			function()
			{
				Projektarbeitsbeurteilung.sendInfoMailToKommission($("#projektarbeit_id").val());
			}
		)
	}

	// enable tooltips
	$('[data-toggle="tooltip"]').tooltip();
})

var Projektarbeitsbeurteilung = {
	finalNote: null, // final grade to save
	negativeNoteValue: 5, // grade value of negative grade
	categoryMaxPoints: 10, // max reachable points of every category
	bewertungData: null,
	plagiatscheckInitiallyUnaffaellig: null,
	notenphrasen: { // notenwert: phrasenname
		1: 'sehrGut',
		2: 'gut',
		3: 'befriedigend',
		4: 'genuegend',
		5: 'nichtGenuegend'
	},
	notenschluessel: { // inclusive lower, exclusive upper boundaries
		1: [88, 101],
		2: [75, 88],
		3: [63, 75],
		4: [50, 63],
		5: [0, 50]
	},
	punkteCompoundCategories: { // if points of any compound category are below 50%, assessment is negative
		'thema': 1,
		'loesungsansatz': 1,
		'methode': 1,
		'ereignissediskussion': 1,
		'eigenstaendigkeit': 1,
		'struktur': 2,
		'stil': 2,
		'form': 2,
		'literatur': 2,
		'zitierregeln': 2
	},
	refreshPlagiatscheck: function()
	{
		if ($("#plagiatscheck_unauffaellig").length)
		{
			var plagiatcheck_unauffaellig = $("#plagiatscheck_unauffaellig");

			// grey out all point dropdowns, no input should be possible
			if ($("#beurteilungtbl select").length)
			{
				var inputDropdowns = $("#beurteilungtbl select");
				// span for selects for displaying tooltip
				var tooltipElements = $("#beurteilungtbl .selectTooltip");

				// if plagiatcheck checkbox is ticked
				if (plagiatcheck_unauffaellig.prop('checked') === true)
				{
					// enable input dropdowns and remove tooltip
					inputDropdowns.prop("disabled", null);

					tooltipElements.attr("data-original-title", "")/*.attr("title", "")*/;
					$("#plagiatscheckHinweisNegativ").hide();

					// set the values in html form
					Projektarbeitsbeurteilung.fillBewertungFormWithData();
				}
				else // if not ticked, disable input dropdowns and add tooltip
				{
					inputDropdowns.prop("disabled", true);
					inputDropdowns.val(0); // all criteria 0, grade negative if plagiatscheck false

					// changing of bootstrap tooltip only works with attr function and setting data-original-title
					var title = FHC_PhrasesLib.t("projektarbeitsbeurteilung", "plagiatscheckNichtGesetzt");
					tooltipElements.attr("data-original-title", title)/*.attr("title", title)*/;
					$("#plagiatscheckHinweisNegativ").show();
				}
			}
		}
	},
	// recalculate points and resulting grade value
	refreshBewertungPointsAndNote: function()
	{
		// set existing Betreuernote if there is one
		var oldBetreuernote = $("#oldbetreuernote").val();
		Projektarbeitsbeurteilung.setFinalNote(oldBetreuernote);

		var pointsEl = $("#beurteilungtbl td.beurteilungpoints select");

		if (pointsEl.length)
		{
			var sumPoints = 0;
			var finalNote = null;
			var compoundCategoryPoints = {};
			var finished = true;
			var ctgNegative = false;

			pointsEl.each(
				function()
				{
					var points = $(this).val();
					var categoryName = $(this).prop('name').replace('bewertung_', '');
					var compoundCategoryNumber = Projektarbeitsbeurteilung.punkteCompoundCategories[categoryName];

					// null if score not entered => not finished, do not display grade yet
					if (points == 'null')
					{
						points = 0;
						finished = false;
					}
					else if(points === '0') // if only one category has 0 points => final grade is negative
					{
						ctgNegative = true;
					}

					// calculate points and maxpoints for each compound category
					if (jQuery.isNumeric(points))
					{
						var floatPoints = parseFloat(points);

						// add points to total sum
						sumPoints += floatPoints;

						// add the points to compound category
						if (!compoundCategoryPoints[compoundCategoryNumber])
						{
							compoundCategoryPoints[compoundCategoryNumber] = {
								points: floatPoints,
								maxpoints: Projektarbeitsbeurteilung.categoryMaxPoints
							};
						}
						else
						{
							compoundCategoryPoints[compoundCategoryNumber].points += floatPoints;
							compoundCategoryPoints[compoundCategoryNumber].maxpoints += Projektarbeitsbeurteilung.categoryMaxPoints;
						}
					}
				}
			)

			// show sum of points with correct language format
			var sumPointsDisplay = $("#language").val() === 'German' ? Projektarbeitsbeurteilung._formatDecimalGerman(sumPoints) : sumPoints;
			$("#gesamtpunkte").text(sumPointsDisplay);

			// if points filled out, calculate and display note
			if (finished)
			{
				if (ctgNegative) // if one category negative
					finalNote = Projektarbeitsbeurteilung.negativeNoteValue; // set finalNote to negative
				else
				{

					var compoundCtgNegative = false;

					// check: if any of the compound categories is negative, finalNote is negative
					for (var catNr in compoundCategoryPoints)
					{
						var compoundCtgPercent = compoundCategoryPoints[catNr].points / compoundCategoryPoints[catNr].maxpoints * 100;

						if (compoundCtgPercent < Projektarbeitsbeurteilung.notenschluessel[5][1]) // if compound category negative
						{
							finalNote = Projektarbeitsbeurteilung.negativeNoteValue; // set finalNote to negative
							compoundCtgNegative = true;
							break;
						}
					}

					// if compound category not negative, get appropriate grade according to Notenschlüssel
					if (!compoundCtgNegative)
					{
						for (var note in Projektarbeitsbeurteilung.notenschluessel)
						{
							var lower = Projektarbeitsbeurteilung.notenschluessel[note][0];
							var upper = Projektarbeitsbeurteilung.notenschluessel[note][1];

							// get correct grade depending on upper/lower boundaries
							if (sumPoints >= lower && sumPoints < upper)
							{
								finalNote = note;
								break;
							}
						}
					}
				}
			}

			Projektarbeitsbeurteilung.setFinalNote(finalNote);
		}
	},
	setTitleEditEvent: function(titel)
	{
		$("#titleField").html(
			'<span id="titleFieldValue">' +
			titel +
			'</span>&nbsp;' +
			'<i class="fa fa-edit" id="editTitle" title="'+FHC_PhrasesLib.t("projektarbeitsbeurteilung", "titelBearbeiten")+'"></i>'
		)

		// edit and save title
		$("#editTitle").click(
			function()
			{
				var title = jQuery.trim($("#titleFieldValue").text());

				$("#titleField").html(
					'<input type="text" class="form-control inline-inputfield" id="titleInputField" value="'+title+'">' +
					'&nbsp;<i class="fa fa-check text-success" id="confirmTitleEdit">'
				);

				$("#confirmTitleEdit").click(
					function()
					{
						Projektarbeitsbeurteilung.saveTitle($("#projektarbeit_id").val(), $("#titleInputField").val());
					}
				)
			}
		);
	},
	// print final grade
	setFinalNote: function(finalNote) {
		if (jQuery.isNumeric(finalNote))
		{
			Projektarbeitsbeurteilung.finalNote = finalNote;
			var finalNotePhrase = FHC_PhrasesLib.t("lehre", Projektarbeitsbeurteilung.notenphrasen[finalNote]);
			$("#betreuernote").text(finalNotePhrase + " (" + finalNote + ")");
		}
		else
		{
			Projektarbeitsbeurteilung.finalNote = null;
			$("#betreuernote").text('');
		}
	},
	// initiating saving of Projektarbeitsbeurteilung data, retrieve data from input fields and initiate ajax call
	initSaveProjektarbeitsbeurteilung: function(saveAndSend)
	{
		// recalculate points and grade before send
		Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

		// get form data into object
		var bewertung = Projektarbeitsbeurteilung.getBewertungFormData();

		// get params needed for save from hidden fields
		var projektarbeit_id = $("#projektarbeit_id").val();
		var betreuerart = $("#betreuerart").val();
		var authtoken = $("#authtoken").val();

		// start saving
		Projektarbeitsbeurteilung.saveProjektarbeitsbeurteilung(projektarbeit_id, betreuerart, bewertung, saveAndSend, authtoken);
	},
	getBewertungFormData: function()
	{
		var bewertungData = {};

		for (var category in Projektarbeitsbeurteilung.punkteCompoundCategories)
		{
			bewertungData['bewertung_'+category] = $("#beurteilungform select[name=bewertung_"+category+"]").val();
		}

		bewertungData['begruendung'] = $("#beurteilungform textarea[name=begruendung]").val();

		// add final grade to data
		if (jQuery.isNumeric(Projektarbeitsbeurteilung.finalNote))
			bewertungData['betreuernote'] = Projektarbeitsbeurteilung.finalNote;

		// add plagiatscheck to data
		if ($("#plagiatscheck_unauffaellig").length > 0)
			bewertungData['plagiatscheck_unauffaellig'] = $("#plagiatscheck_unauffaellig").prop('checked') === true;


		return bewertungData;
	},
	fillBewertungFormWithData: function()
	{
		// prefill with default null values
		$("#beurteilungtbl select").val("null");

		// fill the form with tempoararily saved data
		if (Projektarbeitsbeurteilung.bewertungData != null)
		{
			for (var name in Projektarbeitsbeurteilung.bewertungData)
			{
				$("#beurteilungform select[name=" + name + "]").val(Projektarbeitsbeurteilung.bewertungData[name]);
			}
		}
	},
	// ajax calls
	// -----------------------------------------------------------------------------------------------------------------

	// call to save Projektarbeit data
	saveProjektarbeitsbeurteilung: function(projektarbeit_id, betreuerart, bewertung, saveAndSend, authtoken)
	{
		var projektarbeitData = {
			projektarbeit_id: projektarbeit_id,
			betreuerart: betreuerart,
			bewertung: bewertung,
			saveAndSend: saveAndSend
		}

		// authtoken for external Zweitbetreuer
		if (authtoken !== 'null')
			projektarbeitData.authtoken = authtoken;

		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/saveProjektarbeitsbeurteilung',
			projektarbeitData,
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						if (saveAndSend === true)
						{// when saved and sent, reload the form so it is read only
							$.ajax({
								type: 'POST',
								url: null,//$("#authtokenform").attr('action'),
								data: null,//$("#authtokenform").serialize(),
								success: function(resp) {
									FHC_AjaxClient._showVeil();
									var html = $(resp).find("#containerFluid").html();
									$("#containerFluid").html(html);
									Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
									FHC_AjaxClient._hideVeil();
									FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungGespeichertGesendet"));
								}
							})
						}
						else
							FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungGespeichert"));
					}
					else if(FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));
					}
				},
				errorCallback: function() {
					FHC_DialogLib.alertError(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungFehler"));
				}
			}
		);
	},
	saveTitle: function(projektarbeit_id, titel)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/saveTitel',
			{
				projektarbeit_id: projektarbeit_id,
				titel: titel
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						Projektarbeitsbeurteilung.setTitleEditEvent(titel);

						FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "titelGespeichert"));
					}
					else if(FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));
					}
				},
				errorCallback: function() {
					FHC_DialogLib.alertError(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "titelSpeichernFehler"));
				}
			}
		);
	},
	// call for sending info mail to commitee members
	sendInfoMailToKommission: function(projektarbeit_id)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/sendInfoMailToKommission',
			{projektarbeit_id: projektarbeit_id},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "kommissionMailGesendet"));
					}
					else if(FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));
					}
				},
				errorCallback: function() {
					FHC_DialogLib.alertError(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "kommissionMailFehler"));
				}
			}
		);
	},
	// helper functions
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Formats a numeric value as a float with coma and two decimals
	 */
	_formatDecimalGerman: function(sum)
	{
		var dec = null;

		if(sum === null)
			dec = parseFloat(0).toFixed(2).replace(".", ",");
		else if(sum === '')
		{
			dec = ''
		}
		else
		{
			dec = parseFloat(sum).toFixed(1);

			dec = dec.split('.');
			var dec1 = dec[0];
			var dec2 = dec[1] === '0' ? '' : ',' + dec[1];
			dec = dec1 + dec2;
		}
		return dec;
	}
}
