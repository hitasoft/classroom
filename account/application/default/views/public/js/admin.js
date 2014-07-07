$(document).ready(function(){
    $("input#user-lookup").autocomplete({
        minLength: 2,
        source: window.rootUrl + "/admin-users/autocomplete"
    });

    $("._collapsible_ ._head_").live("click", function(){
        $(this).closest("._item_").toggleClass('_open_');
    });
    $(".grid-wrap").ngrid();

    $('#admin-login').submit(function(){
        //$('#admin-login').hide();
        $.ajax({
            global: false,
            type : 'POST',
            url: $('#admin-login form').attr('action'), 
            data: $('#admin-login input').serializeArray(),
            complete: function (response)
            {
                data = $.parseJSON(response.responseText);
                if (!data) // bad response, redirect to login page
                {
                    window.location.href = window.rootUrl + '/admin'
                    return;
                }
                if (data.ok)
                {
                    $('#admin-login').dialog('destroy');
                } else {
                    $("#admin-login .errors li").text(data.err);
                    $("#admin-login .errors").show();
                }
            }
            });
        $('#admin-login input[name="passwd"]').val('');
        return false;
    });

    function displayLoginForm()
    {
        $('#admin-login').dialog({
            modal: true,
            title: "Administrator Login",
            width: '500'
        });
    }

    $('body').ajaxComplete(function(event,request, settings){
        if (request.status == 402) 
        {
            var vars = $.parseJSON(request.responseText);
            $('#admin-login .error').text(vars['err'] ? vars['err'] : null);
            displayLoginForm();
        }
    })

    $('div.ajax-loading').ajaxStart(function(){
            var div = this;
            div.ajaxActive = true;
            setTimeout(function(){
                if (div.ajaxActive) $(div).show()
            }, 200);
        })
    $('div.ajax-loading').ajaxStop(function(){
        this.ajaxActive = false;
        $(this).hide();
    })

    $("a.email-template").live('click', function() {
        var $div = $('<div style="display:none;" id="email-template-popup"></div>');
        $('body').append($div);
        
        var url = this.href;
        var actionUrl = url.replace(/\?.*$/, '');
        var getQuery= url.replace(/^.*?\?/, '');

        var $a = $(this);

        $div.dialog({
            autoOpen: false,
            modal : true,
            title : "Email Template",
            width : 800,
            position : ['center', 100],
            buttons: {
                "Save" : function() {
                    $div.find('form#EmailTemplate').ajaxSubmit({
                        success : function(res) {
                            if (res.content) {
                                $a.closest('.element').empty().append(res.content);
                            }
                            $div.dialog('close');
                        },
                        beforeSerialize : function(){
                            if(CKEDITOR && CKEDITOR != 'undefined')
                                for ( instance in CKEDITOR.instances )
                                    CKEDITOR.instances[instance].updateElement();

                        }
                    });
                },
                "Cancel" : function() {
                    $(this).dialog("close");
                }
            },
            close : function() {
                $div.remove();
            }
        });
            
        $.ajax({
            type : 'post',
            data : getQuery,
            url : actionUrl,
            dataType : 'html',
            success : function(data, textStatus, XMLHttpRequest) {
                $div.empty().append(data);
                $div.dialog("open");
            }
        });
        
        return false;
    })

    $(".admin-menu").adminMenu(window.amActiveMenuID);
    $(".magicselect").magicSelect();
    $(".magicselect-sortable").magicSelect({sortable:true});
    if (window.amLangCount>1) {
        $('.translate').translate();
    }
    $('input.options-editor').optionsEditor();
    $('.upload').upload();
    $('.reupload').reupload();
    $('input[type=file].styled').fileStyle();
    initDatepicker();
    $('body').ajaxComplete(function(){
        //allow ajax handler to do needed tasks before convert elements
        setTimeout(function(){
            $(".magicselect").magicSelect();
            $(".magicselect-sortable").magicSelect({sortable:true});
            if (window.amLangCount>1) {
                $('.translate').translate();
            }
            $('input.options-editor').optionsEditor();
            $('.upload').upload();
            $('.reupload').reupload();
            $('input[type=file].styled').fileStyle();
            initDatepicker();
            $(".grid-wrap").ngrid();
        }, 100);
    })
    
    // scroll to error message if any
    var errors = $(".errors:visible:first,.error:visible:first");
    if (errors.length) 
        $("html, body").scrollTop(Math.floor(errors.offset().top));
});

function flashError(msg){
    return flash(msg, 'error', 5000);
}
function flashMessage(msg){
    return flash(msg, 'message', 2500);
}
function flash(msg, msgClass, timeout)
{
    if (!$('#flash-message').length)
        $('body').append('<div id="flash-message"></div>');
    lastId = Math.ceil(10000*Math.random());
    var $div = $("<div id='flashMsg-"+lastId+"' class='"+msgClass+"' style='display:none'>"+msg+"</div>")
    $('#flash-message').append($div);
    $div.fadeIn('slow');
    if (timeout)
        setTimeout(function(id){
            $('#flashMsg-'+id).fadeOut('slow', function(){$(this).remove()});
        }, timeout, lastId);
}

$.fn.serializeAssoc = function()
{
    var res = {};
    var arr = $(this).serializeArray();
    for (i in arr)
        res[ arr[i]['name'] ] = arr[i]['value'];
    return res;
}

function filterHtml(source)
{
    HTMLReg.disablePositioning = true;
    HTMLReg.validateHTML = false;
    return HTMLReg.parse(source);
}

function initCkeditor(textareaId, options)
{
    var placeholderToolbar = null;
    options = options || {};
    if (options.placeholder_items)
    {
        placeholderToolbar = {
            name: 'amember',
            items: ['CreatePlaceholder']
        };
    }
    var toolbar_Am = [];
    toolbar_Am.push({
        name: 'basicstyles',
        items : ['Bold','Italic','Strike','-','RemoveFormat']
    });
    if (placeholderToolbar) toolbar_Am.push(placeholderToolbar);
    toolbar_Am.push({
        name: 'paragraph',
        items : ['NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight']
    });
    toolbar_Am.push({
        name: 'insert',
        items : [ 'Link','Unlink','Image','MediaEmbed','Table','HorizontalRule','PageBreak' ]
    });
    toolbar_Am.push({
        name: 'tools',
        items : [ 'Maximize', 'Source', 'Templates', 'SpellChecker' ]
    });
    toolbar_Am.push({
        name: 'clipboard',
        items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ]
    });
    toolbar_Am.push('/');
    toolbar_Am.push({
        name: 'styles',
        items : [ 'Styles','Format','Font','FontSize','TextColor','BGColor'  ]
    });

    var defaultOptions = {
        extraPlugins : 'placeholder',
        autoGrow_maxHeight: 800,
        baseHref: window.rootUrl,
        customConfig : false,
        toolbar: "Am",
        enterMode : CKEDITOR.ENTER_BR,
        toolbar_Am : toolbar_Am
    };
    
    return CKEDITOR.replace(textareaId, $.extend(defaultOptions, options));
}
function initDatepicker(selector, params)
{
    return $(selector || 'input.datepicker').datepicker($.extend({
        defaultDate: window.uiDefaultDate,
        dateFormat: window.uiDateFormat,
        changeMonth: true,
        changeYear: true,
        shortYearCutoff : 37,
        yearRange:  'c-90:c+10',
        showButtonPanel: true,
        beforeShow: function( input ) {
            setTimeout(function() {
                var buttonPane = $( input )
                    .datepicker( "widget" )
                    .find( ".ui-datepicker-buttonpane" );

                $( "<button>", {
                    text: "Lifetime",
                    click: function() {
                        $(input).datepicker('setDate', new Date(2037, 11, 31, 1, 0, 0)); //11 is Dec in javascript [0-11]
                    }
                }).appendTo( buttonPane ).addClass("ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all");
            }, 1 );
        },
        onChangeMonthYear: function( year, month, instance ) {
            setTimeout(function() {
                var buttonPane = $( instance )
                    .datepicker( "widget" )
                    .find( ".ui-datepicker-buttonpane" );

                $( "<button>", {
                    text: "Lifetime",
                    click: function() {
                        $(input).datepicker('setDate', new Date(2037, 11, 31, 1, 0, 0)); //11 is Dec in javascript [0-11]
                    }
                }).appendTo( buttonPane ).addClass("ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all");
            }, 1 );
        }
    }, params || {}));
}
