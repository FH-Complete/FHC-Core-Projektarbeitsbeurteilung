const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

$("document").ready(function() {


    Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

    $("#beurteilungtbl select").change(
        function()
        {
            Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
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
    finalNote: null,
    categoryMaxPoints: 10,
    notenphrasen: { // notenwert: phrasenname
        1: 'sehrGut',
        2: 'gut',
        3: 'befriedigend',
        4: 'genuegend',
        5: 'nichtGenuegend'
    },
    notenschluessel: { // inclusive upper and lower boundaries
        1: [88, 100],
        2: [75, 87],
        3: [63, 74],
        4: [50, 62],
        5: [0, 49]
    },
    punkteCategories: { // if points of any category are below 50%, assessment is negative
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
    refreshBewertungPointsAndNote: function()
    {
        var oldBetreuernote = $("#oldbetreuernote").val();
        Projektarbeitsbeurteilung.setFinalNote(oldBetreuernote);

        var pointsEl = $("#beurteilungtbl td.beurteilungpoints select");

        if (pointsEl.length)
        {
            var sumPoints = 0;
            var finalNote = null;
            var categoryPoints = {};

            pointsEl.each(
                function()
                {
                    var points = $(this).val();
                    var categoryName = $(this).prop('name').replace('bewertung_', '');
                    var categoryNumber = Projektarbeitsbeurteilung.punkteCategories[categoryName];

                    if (points == 'null')
                        points = 0;

                    // calculate points and maxpoints for each category
                    if (jQuery.isNumeric(points))
                    {
                        var intPoints = parseInt(points);
                        sumPoints += intPoints;
                        if (!categoryPoints[categoryNumber])
                            categoryPoints[categoryNumber] = {
                                points: intPoints,
                                maxpoints: Projektarbeitsbeurteilung.categoryMaxPoints
                            };
                        else
                        {
                            categoryPoints[categoryNumber].points += intPoints;
                            categoryPoints[categoryNumber].maxpoints += Projektarbeitsbeurteilung.categoryMaxPoints;
                        }
                    }
                }
            )

            var ctgNegative = false;
            for (var catNr in categoryPoints)
            {
                var ctgPercent = categoryPoints[catNr].points / categoryPoints[catNr].maxpoints * 100;

                if (ctgPercent <= Projektarbeitsbeurteilung.notenschluessel[5][1])
                {
                    finalNote = 5
                    ctgNegative = true;
                    break;
                }
            }

            if (!ctgNegative)
            {
                for (var note in Projektarbeitsbeurteilung.notenschluessel)
                {
                    var lower = Projektarbeitsbeurteilung.notenschluessel[note][0];
                    var upper = Projektarbeitsbeurteilung.notenschluessel[note][1];

                    if (sumPoints >= lower && sumPoints <= upper)
                    {
                        finalNote = note;
                        break;
                    }
                }
            }

            $("#gesamtpunkte").text(sumPoints);
            Projektarbeitsbeurteilung.setFinalNote(finalNote);
        }
    },
    setFinalNote: function(finalNote) {
        if (jQuery.isNumeric(finalNote))
        {
            Projektarbeitsbeurteilung.finalNote = finalNote;
            var finalNotePhrase = FHC_PhrasesLib.t("lehre", Projektarbeitsbeurteilung.notenphrasen[finalNote]);
            $("#betreuernote").text(finalNotePhrase + " (" + finalNote + ")");
        }
    },
    initSaveProjektarbeitsbeurteilung: function(saveAndSend)
    {
        Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

        // get form data into object
        var bewertung = $('#beurteilungform').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        if (jQuery.isNumeric(Projektarbeitsbeurteilung.finalNote))
            bewertung.betreuernote = Projektarbeitsbeurteilung.finalNote;

        if ($("#plagiatscheck_unauffaellig").length > 0)
            bewertung.plagiatscheck_unauffaellig = bewertung.plagiatscheck_unauffaellig === 'true';

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
                        {// when saved and send, reload the form so it is read only
                            $.ajax({
                                type: 'POST',
                                url: $("#authtokenform").attr('action'),
                                data: $("#authtokenform").serialize(),
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
    }
}
