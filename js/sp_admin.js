/*
 * JS for sp_admin class
 * Used in dashboard/admin page
 */

(function($) {
    spAdmin.adminpage = {

        /* Changes rightArrow to downArrow on toggle and
         * displays DOM Element based off of divID
         * @param string arrowClass - The class associated with the arrow image, default: '.expandArrow'
         */
        expandArrow: function(arrowClass){
            if(arrowClass == undefined)
                arrowClass = '.expandArrow';

            $(arrowClass).toggle(
                function(){
                    var divID = $(this).attr('data-divID');
                    $(this).attr('src', IMAGE_PATH + '/downArrow.png');
                    $('#' + divID).show();
                },
                function(){
                    var divID = $(this).attr('data-divID');
                    $(this).attr('src', IMAGE_PATH + '/rightArrow.png');
                    $('#' + divID).hide();
                });

        },

        /**
         * Displays errors to the user.
         * @param errorDivID - The HTML DOM elm's ID where the error will be displayed. Default: #setting_errors
         * @param errorText  - The error message to display (can be HTML)
         */
        showError: function(errorDivID, errorText){
            if(errorDivID == undefined)
                errorDivID = '#setting_errors';

            $(errorDivID).show().html(
                '<h4> Error: ' +	errorText + '<h4>').attr("class", "error");
        },

        /**
         * Load settings for a given category and render it to an HTML DOM elem
         * @param catID        - the category ID
         * @param settingsElem - The HTML DOM elem container to render the settings. Default: '#the_settings'
         */
        loadCatSettings: function(catID, settingsElem){
            if(settingsElem == undefined)
                settingsElem = '#the_settings';

            $.ajax({
                url				  : ajaxurl,
                type     : 'POST',
                data			  : {action: 'renderSPCatSettingsAJAX', nonce: SP_NONCE, catID: catID},
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    $(settingsElem).html(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    showError(statusText);
                }
            });
        },

        /**
         * Loads a new or existing category form based off of action.
         * i.e. Title, description of a category - not to be confused with
         * settings.
         * @param action - coincides with functions actions in sp_adminAJAX.php
         * See sp_adminAJAX::newSPCatAJAX() and sp_adminAJAX::updateSPCatAJAX()
         */
        setCatOptions: function(action){
            var thisObj = this;
            var setOptions = {
                url  	 : ajaxurl,
                type	 : 'POST',
                data	 : {action: action, nonce: SP_NONCE},
                dataType : 'json',
                success	 : function(response, statusText, xhr, $form){
                    if(response){
                        if(response.error){
                            this.showError(response.error);
                        }else{
                            window.location.href = adminurl + '?page=smartpost&catID=' + response.catID;
                        }
                    }
                },
                beforeSubmit : function(formData, jqForm, options){ // form validation
                    var form = jqForm[0];
                    if(!form.cat_name.value){
                        showError('Please fill in the category name');
                        $('#cat_name').focus();
                        return false;
                    }
                },
                error 							: function(data){
                    console.log(data);
                    showError(data.statusText);
                },
            };
        },

        /**
         * Loads a new category form via AJAX
         */
        getNewCatForm: function(){
            $.post(
                ajaxurl,
                {nonce: SP_NONCE, action: 'catFormAJAX', newSPCat: 1},
                function(data){
                    $('#the_settings').html(data);
                    $('#cat_name').focus();
                    $('#cat_form').submit(function(){
                        var newSPCatOptions = spCatOptions('newSPCatAJAX');
                        $('#cat_form').ajaxSubmit(newSPCatOptions);
                        return false;
                    });
                },
                'html'
            );
        },
        /**
         * Category Response Update
         */
        submitResponseCatForm: function(){
            $('#responseCatsForm').submit(function(){
                $(this).ajaxSubmit({
                    url	     : ajaxurl,
                    type     : 'POST',
                    data	 : {action: 'responseCatAJAX', nonce: SP_NONCE},
                    dataType : 'json',
                    success	 : function(response, statusText, xhr, $form){
                        if(response.error){
                            showError(response.error);
                        }else{
                            if( !$('#successCatUpdate').exists() ){
                                var success = $('<p id="successCatUpdate"> Response Categories saved! </p>');
                                $('#submitResponseCats').after(success);
                                success.delay(3000).fadeOut();
                                success.delay(3000, function(){ $(this).remove() });
                            }
                        }
                    },
                    error	 : function(data){
                        showError(data.statusText);
                    },
                }); //end ajaxSubmit
                return false;
            }); //end submit
        },

        /**
         * Initializes the spAdmin object with click handlers and variables
         * necessary for initialization.
         */
        init: function(){


            $( "#sp_compTabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
            $( "#sp_compTabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
            $( "#sp_compTabs ul").removeClass( "ui-corner-all");

            //Initialize a dynatree instance for the SP category tree.
            $('#sp_catTree').dynatree({
                imagePath: "",
                onActivate: function (node) {
                    window.open(node.data.href, node.data.target);
                },
                debugLevel: 0
            });

            $('#newSPCatForm').click(function(){
                getNewCatForm();
            });

            if($('#cat_form').exists()){
                $('#cat_form').submit(function(){
                    var catOptions = spCatOptions('updateSPCatAJAX');
                    $(this).ajaxSubmit(catOptions);
                    return false;
                });
            }
        }
    };//end adminpage object

    $(document).ready(function($){
        spAdmin.adminpage.init();
    }); //end document.ready

})(jQuery);