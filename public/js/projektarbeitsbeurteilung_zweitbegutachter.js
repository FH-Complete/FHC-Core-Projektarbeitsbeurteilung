$("document").ready(function() {

	Projektarbeitsbeurteilung.setEvents();

})

var Projektarbeitsbeurteilung = {
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
						localStorage.setItem("zweitbegutachterFormData", JSON.stringify(Projektarbeitsbeurteilung.getBewertungFormData()));
						// reload page by sending form with authtoken param to show text in different language
						$("#authtokenform").removeAttr('onsubmit');
						$("#authtokenform").submit();
					}
					ProjektarbeitsbeurteilungLib.changeLanguage($(this).val(), successCallback);
				}
			);
			var formData = JSON.parse(localStorage.getItem("zweitbegutachterFormData"));
			if (formData)
			{
				// retrieve saved bewertung data
				$("#beurteilung_zweitbegutachter").val(formData.beurteilung_zweitbegutachter);
				// remove temporarily saved data
				localStorage.removeItem("zweitbegutachterFormData");
			}
		}

		// event for form data save
		if ($("#saveBeurteilungBtn").length)
		{
			$("#saveBeurteilungBtn").click(
				function()
				{
					Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(false);
				}
			)
		}

		// event for form data save and send
		if ($("#saveSendBeurteilungBtn").length)
		{
			$("#saveSendBeurteilungBtn").click(
				function()
				{
					Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(true);
				}
			)
		}
	},
	initSaveProjektarbeitsbeurteilung: function(saveAndSend)
	{
		// get form data into object
		var bewertung = Projektarbeitsbeurteilung.getBewertungFormData();

		var projektarbeit_id = $("#projektarbeit_id").val();
		var betreuerart = $("#betreuerart").val();
		var authtoken = $("#authtoken").val();

		// call ajax save method
		Projektarbeitsbeurteilung.saveProjektarbeitsbeurteilung(projektarbeit_id, betreuerart, bewertung, saveAndSend, authtoken);
	},
	getBewertungFormData: function()
	{
		// get form data into object
		return $('#beurteilungform').serializeArray().reduce(function(obj, item) {
			obj[item.name] = item.value;
			return obj;
		}, {});
	},
	// ajax calls
	// -----------------------------------------------------------------------------------------------------------------
	saveProjektarbeitsbeurteilung: function(projektarbeit_id, betreuerart, bewertung, saveAndSend, authtoken)
	{
		var projektarbeitData = {
			projektarbeit_id: projektarbeit_id,
			betreuerart: betreuerart,
			bewertung: bewertung,
			saveAndSend: saveAndSend
		}

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
								data: $("#authtokenform").serialize(),
								success: function(resp) {
									FHC_AjaxClient._showVeil();
									var html = $(resp).find("#containerFluid").html();
									$("#containerFluid").html(html);
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
	}
}
