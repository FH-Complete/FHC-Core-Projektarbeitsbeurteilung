const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;

$("document").ready(function() {

    Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();

    if ($("#beurteilungtbl select").length)
    {
        $("#beurteilungtbl select").change(
            function()
            {
                Projektarbeitsbeurteilung.refreshBewertungPointsAndNote();
            }
        );
    }

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

    if ($("#sendKommissionMail").length)
    {
        $("#sendKommissionMail").click(
            function()
            {
                Projektarbeitsbeurteilung.sendInfoMailToKommission($("#projektarbeit_id").val());
            }
        )
    }
})

var Projektarbeitsbeurteilung = {
    finalNote: null,
    negativeNoteValue: 5,
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
                        console.log(floatPoints);

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

                        if (compoundCtgPercent <= Projektarbeitsbeurteilung.notenschluessel[5][1]) // if compound category negative
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
                            if (sumPoints >= lower && sumPoints <= upper)
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
                        {// when saved and sent, reload the form so it is read only
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
    },
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
            dec = parseFloat(sum).toFixed(2);

            dec = dec.split('.');
            var dec1 = dec[0];
            var dec2 = ',' + dec[1];
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(dec1)) {
                dec1 = dec1.replace(rgx, '$1' + '.' + '$2');
            }
            dec = dec1 + dec2;
        }
        return dec;
    }
}
