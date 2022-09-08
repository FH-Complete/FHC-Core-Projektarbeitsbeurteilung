/**
 * Javascript file for projekt overview page
 */

const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

var ProjektUebersichtDataset = {
	projektuebersicht_studiensemester_variablename: 'projektuebersicht_studiensemester',

	appendTableActionsHtml: function(projektuebersicht_studiensemester)
	{
		var studienSemesterHtml = '<button class="btn btn-default btn-xs decStudiensemester">' +
			'<i class="fa fa-chevron-left"></i>' +
			'</button>&nbsp;' +
			projektuebersicht_studiensemester +
			'&nbsp;<button class="btn btn-default btn-xs incStudiensemester">' +
			'<i class="fa fa-chevron-right"></i>' +
			'</button>';

		// userdefined Semestervariable shown independently of personcount,
		// it is possible to change the semester
		$("#datasetActionsTop").append(
			"<div class='row text-center'>" +
			"<div class='col-xs-12'>" + studienSemesterHtml + "</div>" +
			"</div>" +
			"<div class='h-divider'></div><hr class='studiensemesterline'>"
		);

		$("button.incStudiensemester").click(function() {
			ProjektUebersichtDataset.changeStudiensemesterUservar(1);
		});

		$("button.decStudiensemester").click(function() {
			ProjektUebersichtDataset.changeStudiensemesterUservar(-1);
		});
	},

	changeStudiensemesterUservar: function(change)
	{
		FHC_AjaxClient.showVeil();

		FHC_AjaxClient.ajaxCallPost(
			'system/Variables/changeStudiensemesterVar',
			{
				'name': ProjektUebersichtDataset.projektuebersicht_studiensemester_variablename,
				'change': change
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						// refresh filterwidget with page reload
						FHC_FilterWidget.reloadDataset();
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					FHC_AjaxClient.hideVeil();
					alert(textStatus);//TODO dialoglib
				}
			}
		);
	},
	/**
	 * initializes call to get the Studiensemester user variable
	 */
	getStudiensemesterUservar: function(callback)
	{

		FHC_AjaxClient.ajaxCallGet(
			'system/Variables/getVar',
			{
				'name' : ProjektUebersichtDataset.projektuebersicht_studiensemester_variablename
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.hasData(data))
					{
						if (typeof callback === "function")
						{
							var projektuebersicht_studiensemester = FHC_AjaxClient.getData(data);
							callback(projektuebersicht_studiensemester[ProjektUebersichtDataset.projektuebersicht_studiensemester_variablename]);
						}
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					alert(textStatus);
				}
			}
		);
	},

	unlockAssessment: function(personid, projektid, td)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/unlockAssessment',
			{
				'personid': personid,
				'projektid': projektid
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isError(data))
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));

					if (FHC_AjaxClient.isSuccess(data))
					{
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data));
						td.closest('td').text('-');
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					FHC_DialogLib.alertError(textStatus);
				}
			}
		);
	},

	resendToken: function(personid, projektid, studentid, kommissionprueferid)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + '/resendToken',
			{
				'personid': personid,
				'projektid': projektid,
				'studentid': studentid,
				'kommissionprueferid': kommissionprueferid
			},
			{
				successCallback: function(data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data));
					}

					if (FHC_AjaxClient.isSuccess(data))
					{
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data));
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown) {
					FHC_DialogLib.alertError(textStatus);
				}
			}
		);
	}
};

/**
 * When JQuery is up
 */
$(document).ready(function() {

	ProjektUebersichtDataset.getStudiensemesterUservar(ProjektUebersichtDataset.appendTableActionsHtml);


});

$(document).on('click', '.freischalten', function()
{
	var personid = $(this).data('personid');
	var projektid = $(this).data('projektid');
	var abgeschickt = $(this).data('abgeschickt');

	if (personid === '')
		return FHC_DialogLib.alertInfo("Erst- /Zweitbegutachter nicht eingetragen.");

	if (abgeschickt === '')
		return FHC_DialogLib.alertInfo("Freischaltung nicht möglich, da es noch nicht abgeschickt wurde.");

	if (confirm("Soll die Beurteilung wirklich nochmal zur Bearbeitung freigeschalten werden?"))
		ProjektUebersichtDataset.unlockAssessment(personid, projektid, $(this));
});

$(document).on('click', '.resend', function()
{
	var personid = $(this).data('personid');
	var projektid = $(this).data('projektid');
	var studentid = $(this).data('studentid');
	var kommissionprueferid = $(this).data('kommissionprueferid') ? $(this).data('kommissionprueferid') : null;

	if (personid === '')
		return FHC_DialogLib.alertInfo("Erstbegutachter nicht eingetragen.");

	if (confirm("Soll der Zugangstoken erneut an den Betreuer geschickt werden?"))
		ProjektUebersichtDataset.resendToken(personid, projektid, studentid, kommissionprueferid);
});
