const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

$("document").ready(function() {

	if ($("#saveBeurteilungBtn").length)
	{
		$("#saveBeurteilungBtn").click(
			function()
			{
				Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(false);
			}
		)
	}

	if ($("#saveSendBeurteilungBtn").length)
	{
		$("#saveSendBeurteilungBtn").click(
			function()
			{
				Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(true);
			}
		)
	}
})

var Projektarbeitsbeurteilung = {
	initSaveProjektarbeitsbeurteilung: function(saveAndSend)
	{
		// get form data into object
		var bewertung = $('#beurteilungform').serializeArray().reduce(function(obj, item) {
			obj[item.name] = item.value;
			return obj;
		}, {});

		var projektarbeit_id = $("#projektarbeit_id").val();
		var betreuerart = $("#betreuerart").val();
		var authtoken = $("#authtoken").val();

		Projektarbeitsbeurteilung.saveProjektarbeitsbeurteilung(projektarbeit_id, betreuerart, bewertung, saveAndSend, authtoken);
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
