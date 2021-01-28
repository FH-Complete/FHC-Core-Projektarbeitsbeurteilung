const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

$("document").ready(function() {

    $("#beurteilungtbl select").click(
        function()
        {
            Projektarbeitsbeurteilung.refreshBewertungPoints();
        }
    );

    $("#saveBeurteilungBtn").click(
        function() {
            Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(false);
        }
    )

    $("#saveSendBeurteilungBtn").click(
        function() {
            Projektarbeitsbeurteilung.initSaveProjektarbeitsbeurteilung(true);
        }
    )

})

var Projektarbeitsbeurteilung = {
    refreshBewertungPoints: function()
    {
        var sumPoints = 0;
        var pointsEl = $("#beurteilungtbl td.beurteilungpoints select");

        pointsEl.each(
            function()
            {
                var points = $(this).val();

                if (jQuery.isNumeric(points))
                    sumPoints += parseInt(points);
            }
        )
        $("#gesamtpunkte").text(sumPoints);
    },
    initSaveProjektarbeitsbeurteilung: function(saveAndSend)
    {
        var bewertung = $('#beurteilungform').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        if ($("#plagiatscheck_unauffaellig").length > 0)
            bewertung.plagiatscheck_unauffaellig = bewertung.plagiatscheck_unauffaellig === 'true';

        var projektarbeit_id = $("#projektarbeit_id").val();
        var betreuerart = $("#betreuerart").val();

        //var checkFields = Pruefungsprotokoll.checkFields(data, freigebendata, $("#verfCheck").prop('checked'));
        /*            if (checkFields.length > 0)
					{
						var errortext = '';
						for (var i = 0; i < checkFields.length; i++)
						{
							var error = checkFields[i];
							$.each(error, function(i, n)
							{
							   $("#"+i).closest('td').addClass('has-error');
							   if (errortext !== '')
								   errortext += '; ';
							   errortext += n;
							});
						}

						FHC_DialogLib.alertError(errortext);
						return;
					}*/

        console.log(bewertung);

        Projektarbeitsbeurteilung.saveProjektarbeitsbeurteilung(projektarbeit_id, betreuerart, bewertung, saveAndSend);
    },
    // ajax calls
    // -----------------------------------------------------------------------------------------------------------------
    saveProjektarbeitsbeurteilung: function(projektarbeit_id, betreuerart, bewertung, saveAndSend)
    {
        FHC_AjaxClient.ajaxCallPost(
            CALLED_PATH + '/saveProjektarbeitsbeurteilung',
            {
                projektarbeit_id: projektarbeit_id,
                betreuerart: betreuerart,
                bewertung: bewertung,
                saveAndSend: saveAndSend
            },
            {
                successCallback: function(data, textStatus, jqXHR) {
                    if (FHC_AjaxClient.hasData(data))
                    {
                        console.log(data);
                        var dataresponse = FHC_AjaxClient.getData(data);

                        if (saveAndSend === true)
                        {// when saved and send, reload the form so it is read only
                            $("#containerFluid").load(location.href + " #containerFluid");
                            FHC_DialogLib.alertSuccess(FHC_PhrasesLib.t("projektarbeitsbeurteilung", "beurteilungGespeichertGesendet"));
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
                },
                veilTimeout: 0
            }
        );
    }
}
