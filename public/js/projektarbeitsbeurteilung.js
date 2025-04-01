$("document").ready(function() {

	// temporarily save current form bewertung data
	Projektarbeitsbeurteilung.storeBewertungFormData();

	// set the compound categories being used for calculation
	Projektarbeitsbeurteilung.setUsedCompoundCategoriesWeight();

	// refresh plagiatscheck after page load
	Projektarbeitsbeurteilung.refreshPlagiatscheck();

	// refresh grade and points after page load
	Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

	Projektarbeitsbeurteilung.formatBewertungPoints();

	// set JS events
	Projektarbeitsbeurteilung.setEvents();

	// enable tooltips
	$('[data-toggle="tooltip"]').tooltip();

});

var Projektarbeitsbeurteilung = {
	gesamtpunkte: null, // total points
	finalNote: null, // final grade to save
	negativeNoteValue: 5, // grade value of negative grade
	bewertungData: null,
	categoryMaxPoints: 100, // max reachable points of every category
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
	useScaling: false, // when more crtieria in one compound categories: wether to use scaling to ensure consistent max points
	// each compound category consists of multiple criteria. if points of any compound category are below 50%, assessment is negative
	compoundCategories: {
		'bewertung_problemstellung': 1,
		'bewertung_methode': 2,
		'bewertung_ergebnissediskussion': 3,
		'bewertung_struktur': 4,
		'bewertung_stil': 5,
		'bewertung_zitierregeln': 6
	},
	// points from each compound category can be weighted differently (in percent)
	compoundCategoriesWeight: {
		'bachelor': {
			1: 5,
			2: 40,
			3: 40,
			4: 5,
			5: 5,
			6: 5
		},
		'master': {
			1: 5,
			2: 40,
			3: 40,
			4: 5,
			5: 5,
			6: 5
		}
	},
	usedCompoundCategoriesWeight: {},
	setEvents: function()
	{
		// set event for language change dropdown
		if ($("#lang").length)
		{
			// language dropdown change
			$("#lang").change(
				function()
				{
					var successCallback = function()
					{
						// save entered bewertung data so it doesn't get lost on refresh
						localStorage.setItem("erstbegutachterFormData", JSON.stringify(Projektarbeitsbeurteilung.bewertungData));
						// reload page to show text in different language
						window.location.reload();
					}

					ProjektarbeitsbeurteilungLib.changeLanguage($(this).val(), successCallback);
				}
			);

			// retrieve saved bewertung data
			var formData = JSON.parse(localStorage.getItem("erstbegutachterFormData"));
			if (formData)
			{
				// set temporarily saved bewertung data
				Projektarbeitsbeurteilung.bewertungData = formData;
				Projektarbeitsbeurteilung.fillBewertungFormWithData();
				// recalculate grade and points
				Projektarbeitsbeurteilung.refreshPlagiatscheck();
				Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
				Projektarbeitsbeurteilung.formatBewertungPoints();
				// remove temporarily saved data
				localStorage.removeItem("erstbegutachterFormData");
			}
		}

		// refresh plagiatscheck after checkbox change
		if ($("#plagiatscheck_unauffaellig").length)
		{
			$("#plagiatscheck_unauffaellig").change(
				function()
				{
					Projektarbeitsbeurteilung.storePlagiatscheck();
					Projektarbeitsbeurteilung.refreshPlagiatscheck();
					Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
				}
			);
		}

		// make title editable
		Projektarbeitsbeurteilung.setTitleEditEvent($("#titleField").text());

		// refresh grade and points after changing Bewertung
		if ($("#beurteilungtbl input.pointsInput").length)
		{
			$("#beurteilungtbl input.pointsInput").on('input',
				function()
				{
					Projektarbeitsbeurteilung.storeBewertungFormData();
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

	},
	refreshPlagiatscheck: function()
	{
		if ($("#plagiatscheck_unauffaellig").length)
		{
			var plagiatscheck_unauffaellig = $("#plagiatscheck_unauffaellig");

			// grey out all point inputs, no input should be possible
			if ($("#beurteilungtbl input.pointsInput").length)
			{
				var inputs = $("#beurteilungtbl input.pointsInput");
				// span for selects for displaying tooltip
				var tooltipElements = $("#beurteilungtbl .selectTooltip");

				// if plagiatcheck checkbox is ticked
				if (plagiatscheck_unauffaellig.prop('checked') === true)
				{
					// enable input inputs and remove tooltip
					inputs.prop("disabled", null);

					tooltipElements.attr("data-original-title", "");
					$("#plagiatscheckHinweisNegativ").hide();

					// set the values in html form
					Projektarbeitsbeurteilung.fillBewertungFormWithData();
				}
				else // if not ticked, disable input inputs and add tooltip
				{
					inputs.prop("disabled", true);
					inputs.val("0"); // all criteria 0, grade negative if plagiatscheck false

					// changing of bootstrap tooltip only works with attr function and setting data-original-title
					var title = FHC_PhrasesLib.t("projektarbeitsbeurteilung", "plagiatscheckNichtGesetzt");
					tooltipElements.attr("data-original-title", title);
					$("#plagiatscheckHinweisNegativ").show();
				}
			}
		}
	},
	// recalculate points and resulting grade value
	refreshBewertungPointsAndNote: function()
	{
		// display the Gewichtung
		Projektarbeitsbeurteilung.fillGewichtung();

		// get selected language
		var language = ProjektarbeitsbeurteilungLib.getSelectedLanguage();

		// set language attribute
		$("html").attr("lang", ProjektarbeitsbeurteilungLib.languages[language]['langAttr']);

		var pointsEl = $("#beurteilungtbl td.beurteilungpoints");

		if (pointsEl.length)
		{
			var sumPoints = 0;
			var sumMaxPoints = 0;
			var finalNote = null;
			var compoundCategoryPoints = {};
			var finished = true;
			var ctgNegative = false;

			pointsEl.each(
				function()
				{
					// get points from dropdown if form not sent or data attribute if sent
					var el = $(this).find('input.pointsInput');
					var points = el.val() || $(this).find('span').attr("data-points");


					var categoryName = $(this).attr("id");
					var compoundCategoryNumber = Projektarbeitsbeurteilung.compoundCategories[categoryName];

					// null if score not entered => not finished, do not display grade yet
					if (typeof points == 'undefined')
					{
						points = "0";
						finished = false;
					}
					else if(parseFloat(points) == 0) // if only one category has 0 points => final grade is negative
					{
						ctgNegative = true;
					}

					// check if valid points value
					if (el)
					{
						Projektarbeitsbeurteilung._checkPoints(points, el.parent());
					}

					// correctly format points
					points = Projektarbeitsbeurteilung._formatDecimal(points);

					// weight each score
					// weight factor, multiplied by number of compound categories to scale up to 100 to have 100 points in total
					var categoryWeight = Projektarbeitsbeurteilung.usedCompoundCategoriesWeight[compoundCategoryNumber] / 100;
					var numCategoriesInCompoundCat = Projektarbeitsbeurteilung._getNumberCategoriesInCompoundCategory(compoundCategoryNumber);
					// scale up so total points stay the same.
					var scalingFactor =
						this.useScaling
						? Object.keys(Projektarbeitsbeurteilung.compoundCategories).length / numCategoriesInCompoundCat
						: 1;

					// get max points for each category
					var maxPoints = Projektarbeitsbeurteilung.categoryMaxPoints * categoryWeight * scalingFactor;
					sumMaxPoints += maxPoints;
					if (!compoundCategoryPoints[compoundCategoryNumber])
					{
						compoundCategoryPoints[compoundCategoryNumber] = {
							points: 0,
							maxpoints: maxPoints
						};
					}
					else
					{
						compoundCategoryPoints[compoundCategoryNumber].maxpoints += maxPoints;
					}

					// calculate points and maxpoints for each compound category
					if (jQuery.isNumeric(points))
					{
						var floatPoints = parseFloat(points) * categoryWeight * scalingFactor;

						// add points to total sum
						sumPoints += floatPoints;

						// add the points to compound category
						compoundCategoryPoints[compoundCategoryNumber].points += floatPoints;
					}
				}
			)

			// show sum of points with correct language format
			$("#gesamtpunkte").text(Projektarbeitsbeurteilung._formatDecimal(sumPoints.toString(), language, 2));
			$("#maxpunkte").text(Projektarbeitsbeurteilung._formatDecimal(sumMaxPoints.toString(), language, 2));

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
					var sumPointsPercent = sumPoints / sumMaxPoints * 100;
					if (!compoundCtgNegative)
					{
						for (var note in Projektarbeitsbeurteilung.notenschluessel)
						{
							var lower = Projektarbeitsbeurteilung.notenschluessel[note][0];
							var upper = Projektarbeitsbeurteilung.notenschluessel[note][1];

							// get correct grade depending on upper/lower boundaries
							if (sumPointsPercent >= lower && sumPointsPercent < upper)
							{
								finalNote = note;
								break;
							}
						}
					}
				}
			}

			Projektarbeitsbeurteilung.gesamtpunkte = sumPoints;
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
		);

		// edit and save title
		$("#editTitle").click(
			function()
			{
				var title = jQuery.trim($("#titleFieldValue").text());

				$("#titleField").html(
					'<input type="text" class="form-control inline-inputfield" id="titleInputField" value="'+title+'">' +
					'&nbsp;<i class="fa fa-check text-success" id="confirmTitleEdit">'
				);

				var saveTitleFunc = function()
				{
					Projektarbeitsbeurteilung.saveTitle($("#projektarbeit_id").val(), $("#titleInputField").val());
				}

				$("#confirmTitleEdit").click(saveTitleFunc); // when click on tick sign, save title
				$("#titleInputField").keypress(function(event)
					{
						if (event.which == '13') // when hit enter, save title as well
							saveTitleFunc();
						event.stopPropagation();
					}
				);
			}
		);
	},
	setUsedCompoundCategoriesWeight: function()
	{
		Projektarbeitsbeurteilung.usedCompoundCategoriesWeight = $("#paarbeittyp").val() === 'm'
			? Projektarbeitsbeurteilung.compoundCategoriesWeight['master']
			: Projektarbeitsbeurteilung.compoundCategoriesWeight['bachelor'];
	},
	fillGewichtung: function()
	{
		for (compoundCategoryName in Projektarbeitsbeurteilung.compoundCategories)
		{
			var compoundCategoryNumber = Projektarbeitsbeurteilung.compoundCategories[compoundCategoryName];
			$("#gewichtung_"+compoundCategoryName).text(Projektarbeitsbeurteilung.usedCompoundCategoriesWeight[compoundCategoryNumber]);
		}
	},
	// save final grade in property and print it
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
		var bewertung = Projektarbeitsbeurteilung.prepareBewertungPoints(Projektarbeitsbeurteilung.getBewertungFormData());

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
		var inputCategories = $("#beurteilungform input.pointsInput");

		// get the points from form
		inputCategories.each(function(key, value) {
			//var obj = $.extend(true, [], value);obj.value;
			bewertungData[$(value).prop('name')] = value.value;
		});

		//~ for (var category in Projektarbeitsbeurteilung.compoundCategories)
		//~ {
			//~ var points = $("#beurteilungform input.pointsInput[name="+category+"]");
			//~ bewertungData[category] = $("#beurteilungform input.pointsInput[name="+category+"]").val();
			//~ bewertungData[category] = points.val().replace(",", ".");
		//~ }

		bewertungData['begruendung'] = $("#beurteilungform textarea[name=begruendung]").val();

		// add points to data
		if (jQuery.isNumeric(Projektarbeitsbeurteilung.gesamtpunkte))
			bewertungData['gesamtpunkte'] = Projektarbeitsbeurteilung.gesamtpunkte;

		// add final grade to data
		if (jQuery.isNumeric(Projektarbeitsbeurteilung.finalNote))
			bewertungData['betreuernote'] = Projektarbeitsbeurteilung.finalNote;

		// add plagiatscheck to data
		if ($("#plagiatscheck_unauffaellig").length > 0)
			bewertungData['plagiatscheck_unauffaellig'] = $("#plagiatscheck_unauffaellig").prop('checked') === true;

		return bewertungData;
	},
	prepareBewertungPoints: function(bewertungData) // prepare Bewertugn points, i.e. put them in the format they need to be saved
	{
		for (var compoundCategoryName in Projektarbeitsbeurteilung.compoundCategories)
		{
			bewertungData[compoundCategoryName] = Projektarbeitsbeurteilung._formatDecimal(bewertungData[compoundCategoryName]);
		}

		return bewertungData;
	},
	formatBewertungPoints: function()
	{
		var readOnly = false;
		var language = ProjektarbeitsbeurteilungLib.getSelectedLanguage();
		var elements = $("#beurteilungform input.pointsInput");

		if (!elements.length)
		{
			elements = $("#beurteilungform span.readOnlyPoints");
			readOnly = true;
		}
		elements.each(
			function() {
				if (readOnly)
					$(this).text(Projektarbeitsbeurteilung._formatDecimal($(this).text(), language));
				else
					$(this).val(Projektarbeitsbeurteilung._formatDecimal($(this).val(), language));
			}
		);
	},
	storeBewertungFormData: function() // storing current (but maybe not saved) Bewertung data in JS
	{
		Projektarbeitsbeurteilung.bewertungData = Projektarbeitsbeurteilung.getBewertungFormData();
	},
	storePlagiatscheck: function() // store only plagiatscheck bool in JS
	{
		Projektarbeitsbeurteilung.bewertungData.plagiatscheck_unauffaellig =
			Projektarbeitsbeurteilung.getBewertungFormData().plagiatscheck_unauffaellig;
	},
	fillBewertungFormWithData: function()
	{
		// prefill with default null values
		$("#beurteilungtbl input").val("");

		// fill the form with tempoararily saved data
		if (Projektarbeitsbeurteilung.bewertungData != null)
		{
			// set plagiat checkbox
			$("#plagiatscheck_unauffaellig").prop('checked', Projektarbeitsbeurteilung.bewertungData.plagiatscheck_unauffaellig);

			// set points
			for (var name in Projektarbeitsbeurteilung.bewertungData)
			{
				$("#beurteilungform input[name=" + name + "]").val(Projektarbeitsbeurteilung.bewertungData[name]);
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
								url: null,
								data: null,
								success: function(resp) {
									FHC_AjaxClient._showVeil();

									// refresh storage after save
									Projektarbeitsbeurteilung.storeBewertungFormData();

									// reload part of html to refresh display
									var html = $(resp).find("#containerFluid").html();
									$("#containerFluid").html(html);

									// points and grade have to be recalculated
									Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

									// set events
									Projektarbeitsbeurteilung.setEvents();

									// format points display
									Projektarbeitsbeurteilung.formatBewertungPoints();

									FHC_AjaxClient._hideVeil();

									FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungGespeichertGesendet"));
								}
							})
						}
						else
						{
							FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungGespeichert"));
							Projektarbeitsbeurteilung.formatBewertungPoints();
						}

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
	 * Formats a numeric value as a float, depending on language
	 */
	_formatDecimal: function(sum, language = '', decimals = null)
	{
		// do not format if not a numeric string
		let pattern = /^[0-9]+[.,]?[0-9]*$/;
		if (typeof sum != 'string' || !pattern.test(sum)) return sum;

		var defaultReplacement = '.';
		var replacementMapping =
			typeof ProjektarbeitsbeurteilungLib.languages[language] != "undefined"
			? ProjektarbeitsbeurteilungLib.languages[language]['replacementMapping']
			: {',': defaultReplacement};
		var toReplace = Object.keys(replacementMapping)[0];
		var replacement = replacementMapping[toReplace];

		// replace "foreign" decimal points
		var dec = sum.replace(toReplace, defaultReplacement).replace(replacement, defaultReplacement);

		// get the number to required decimals (if given)
		if (Number.isInteger(decimals)) dec = parseFloat(dec).toFixed(decimals);

		// remove trailing zeros (second parseFloat), and convert to string again
		dec = parseFloat(dec).toString();

		// split by decimal point, replace the point with language-specific point
		var decSplitted = dec.split('.');

		if (decSplitted.length == 2)
		{
			var dec1 = decSplitted[0];
			var dec2 = replacement + decSplitted[1];
			dec = dec1 + dec2;
		}

		return dec;
	},
	_checkPoints: function(pts, el)
	{
		var errorClass = 'has-error';
		var pattern = /^[0-9]*[.,]?[0-9]{0,2}$/;
		if (pattern.test(pts) && parseFloat(Projektarbeitsbeurteilung._formatDecimal(pts)) <= 100)
		{
			if (el) el.removeClass(errorClass);
			return true;
		}
		else
		{
			if (el) el.addClass(errorClass);
			return false;
		}
	},
	_getNumberCategoriesInCompoundCategory: function(compoundCategoryNumber)
	{
		var num = 0;
		for (var cat in Projektarbeitsbeurteilung.compoundCategories)
		{
			if (Projektarbeitsbeurteilung.compoundCategories[cat] == compoundCategoryNumber)
				num++;
		}
		return num;
	}
}
