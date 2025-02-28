const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

// functionality for all types of Projektarbeitsbeurteilung
var ProjektarbeitsbeurteilungLib = {
	languages: { // languages and corresponding replacement mappings
		'German': {'langAttr': 'de', 'replacementMapping': {'.': ','}},
		'English': {'langAttr': 'en', 'replacementMapping': {',': '.'}}
	},
	getSelectedLanguage: function()
	{
		return $("#lang").length ? $("#lang").val() : Object.keys(ProjektarbeitsbeurteilungLib.languages)[0];
	},
	// ajax calls
	// -----------------------------------------------------------------------------------------------------------------
	changeLanguage: function(language, successCallback)
	{
		// call for changing language in session
		FHC_AjaxClient.ajaxCallGet(
			CALLED_PATH + '/changeLanguage',
			{
				language: language
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						successCallback();
					}
					else if(FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));
					}
				},
				errorCallback: function() {
					FHC_DialogLib.alertError(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "spracheAendernFehler"));
				},
				veilTimeout: 0
			}
		);
	}
}
