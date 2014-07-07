var _ajax={
   'running':false,
   'element':null,
   'startfn':null,
   'complfn':null,
   'serial':false,
   'reset':true,
   'error':true
   };
$(function (){
   // setup standard ajax error handling
   $('<div/>').attr('id','ajax_error')
      .appendTo('body').hide()
      .ajaxError(showError)
      .ajaxComplete(
         function(){
            if (typeof _ajax.complfn=='function') _ajax.complfn();
            if (typeof _ajax.element=='string') $(_ajax.element).fadeOut();
            if (_ajax.reset)
               _ajax.complfn=_ajax.element=_ajax.startfn=null;
            _ajax.running=false;
         }
      );
   // setup standard ajax start/complete handlers
   $.ajaxSetup({
      beforeSend: function(){
         if (!_ajax.serial || !_ajax.running){
            if (typeof _ajax.startfn=='function') _ajax.startfn();
            else if (typeof _ajax.element=='string') $(_ajax.element).fadeIn();
            _ajax.running=true;
         }else{
            alert('Please allow the previous request to complete.');
            return false;
         }
      }
   });
   // default classes for all inputs, with placeholder setup
   $(':input').addClass(typeClass);
   var aPre=new Array();
   if (typeof user!='undefined'){
      if (user.page=='status'){
         initPost();
         aPre.push(['lock.png,lock-unlock.png',user.livedir+'/image/icon/']);
         aPre.push(['prev.png,next.png',user.livedir+'/image/']);
      }else if (user.page=='profile'){
         initProfile();
         aPre.push(['dummy.png,dummy-logo.png','/image/']);
      }
      if (typeof user.livedir=='undefined') user.livedir='';
   }else{
      user={livedir:''};
   }
   setTimeout(function()
      {
         aPre.push(['close_btn.png,spinner_s.gif,spinner_l.gif,gray-btn-bg.gif',user.livedir+'/css/']);
         aPre.push(['tick.png,tick-red.png,exclamation.png',user.livedir+'/image/icon/16/']);
         preload(aPre);
      },10*1000
   );
   $('#footer>.page>a').click(
      function(evt){
         var te=$(this);
         if (typeof jQuery().overlay=='function'){
            var id='fbdPopup';var de=$('#'+id);
            if (de.length==0){
               de=$('<div />').attr('id',id).addClass('dlgPopup').hide().appendTo('body').eq(0);
            }
            if (de.is(':empty')){
               te.addClass('ajax_loader_s');
               de.load(user.livedir+'/ajax/feedback.php',"",
                  function(response,status,xhr){
                     te.removeClass('ajax_loader_s');
                     if (status=='success'){
                        var dialog=de.overlay(
                           {
                              closeOnClick:false,
                              top:'center',
                              mask:{opacity:0.4},
                              load:true,
                              onLoad:
                                 function(){
                                    de.find('textarea').focus();
                                 }
                           }
                        );
                        de.find('form').submit(
                           function(evt){
                              // validate
                              var valid=true;var vClass='fbdInvalid';
                              de.find('input,select,textarea').removeClass(vClass);
                              if (!$('#c_email').val().match(/^[A-Za-z0-9](([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z]{2,})$/)){
                                 valid=false;
                                 $('#c_email').addClass(vClass);
                              }
                              if ($('#c_area').val()=='0'){
                                 valid=false;
                                 $('#c_area').addClass(vClass);
                              }
                              if ($.trim($('#c_message').val()).length==0 && $('#c_area').val()=='others'){
                                 valid=false;
                                 $('#c_message').addClass(vClass);
                              }
                              if (valid && !_ajax.running) {
                                 param=serializeData(this);
                                 de.find('tr.actionRow>th').addClass('ajax_loader_s');
                                 $.post(user.livedir+'/ajax/feedback.php',param,
                                    function(data,status){
                                       de.find('tr.actionRow>th').removeClass('ajax_loader_s');
                                       if (data=='success'){
                                          de.hide().find('#fbdForm').remove().end()
                                             .append(
                                             '<h4>We appreciate your feedback!</h4>'+
                                             '<p>Thank you for your input. We will get back to you soon.</p>'+
                                             '<p><em>&mdash; The Educator-Classroom Team</em></p>'
                                             )
                                          ;
                                          setTimeout(
                                             function(){
                                                de.data('overlay').load();
                                             },600
                                          );
                                       }else{
                                          showError(data);
                                       }
                                       dialog.overlay().close();
                                    }
                                 );
                              }
                              return false;
                           }
                        );
                     }
                  }
               );
            }else{
               de.data('overlay').load();
            }
         }else{
			loadDialog(te);
         }
      }
   );
	
	function loadDialog(te){
        te.addClass('ajax_loader_s');
        _ajax.complfn=function(){te.removeClass('ajax_loader_s');};
        $.ajax(
           {
              url:user.livedir+'/js/overlay.js',
              success:
                 function(){
                    setTimeout(
                       function(){
                          te.click();
                       },5
                    );
                 },
              error:
                 function(jqXHR, textStatus, errorThrown){
                    showError(errorThrown+': Dialog application did not load. Please try again.');
                 },
              dataType:'script'
           }
        );
	}

	$('#headerLogin').click(
		function(evt){
			var te=$(this);
			if (typeof jQuery().overlay=='function'){
	            var id='loginPopup';var de=$('#'+id);
	            if (de.length==0){
	               de=$('<div />').attr('id',id).addClass('dlgPopup').hide().appendTo('body').eq(0);
	            }
	            if (de.is(':empty')){
	               te.addClass('ajax_loader_s');
	               de.load(user.livedir+'/ajax/loginform.php',"",
	                  function(response,status,xhr){
	                     te.removeClass('ajax_loader_s');
	                     if (status=='success'){
							$('#loginForm input:text,#loginForm input:password').focus(
								function(){
									$(this).parent().addClass('focus');
								}
							).blur(
								function(){
									$(this).parent().removeClass('focus');
								}
							).keyup(
								function(){
									if (this.value.length) $(this).next('a').html('?');
									else $(this).next('a').html('forget?');
								}
							);
							$('#loginForm tr.dataRow a').click(
								function(){
									var cc=$(this).next('div.calloutContent:visible').length;
									$('div.calloutContent:visible').fadeOut('fast');
									if (!cc) $(this).next('div.calloutContent').fadeIn('fast');
								}
							);
							$('div.calloutContent').find('div.closeBtn').click(
								function(){
									$(this).parent().fadeOut('fast');
								}
							);
		                    var dialog=de.overlay(
		                       {
		                          closeOnClick:false,
		                          top:'center',
		                          mask:{opacity:0.4},
		                          load:true,
		                          onLoad:
		                             function(){
										de.find('input:text').focus();
		                             },
								  onClose:
									function(){
										$('div.calloutContent').hide();
									}
		                       }
		                    );
	                     }
	                  }
	               );
				}else{
					de.data('overlay').load();
				}
			}else loadDialog(te);
		}
	);
	
	$('div.calloutContent').find('div.close').click(
		function(){
			$(this).parent().fadeOut('fast');
		}
	);
	$('#headerLnk').click(
		function(){
			var cc=$(this).children('div.calloutContent:visible').length;
			$('div.calloutContent:visible').fadeOut('fast');
			if (!cc) $(this).children('div.calloutContent').fadeIn('fast');
		})

});
function initPost(){
   user.hometab=(user.hometab=='')?0:parseInt(user.hometab);
   var annDirect=true;
   var ann=$("ul.tabs").tabs("div.panes > div",
      {
         effect:'fade',
         initialIndex:user.hometab,
         onBeforeClick:
            function(evt,i){
               var pane = this.getPanes().eq(i);
               var param={uid:user.amid,live:user.live};
               if (pane.attr('id')=='annPane'){
                  if ($('#streamDiv').is(':empty')){
                     if (annDirect){
                        setTimeout(
                           function(){
                              if ($('#prClass>ul>li').length>0){
                                 $('#prClass>ul>li:first').click();
                              }else{
                                 // no classes; just open the stream
                                 loadPost();
                              }
                           }
                           ,200
                        );
                     }else{
                        annDirect=true;
                     }
                  }else if ($('#streamDiv').children('div[id^="pid"]').length>0){
                     $('#streamDiv').children().wrapAll('<div />');
                     onStream($('#streamDiv').children());
                     if ($('#prClass>ul>li').length>0){
                        user.pclass=1;user.ptype='';
                        setTimeout(
                           function(){
                              $('#prClass>ul>li:first').click().addClass('open');
                           },200
                        );
                     }
                  }
                  if ($('#postTabs').data('tabs')) $('#postTabs').data('tabs').click(0);
               }else if (pane.attr('id')=='filPane') {
                  var tbl=$('#filPane table>tbody');
                  var pid=tbl.children('tr:first').attr('id');
                  if (pid){
                     pid=parseInt(pid);
                     param.postid=pid;
                  }
                  $.post(user.livedir+'/ajax/get_files.php',param,
                     function(response,status){
                        if (status=='success'){
                           if (response){
                              tbl.prepend(response);
                              tbl.children('tr:odd').removeClass('even');
                              tbl.children('tr:even').addClass('even');
                           }
                        }
                     }
                  );
				}else if (pane.attr('id')=='imgPane'){
					user.ptype='pImages';
					if ($('#streamImg').is(':empty')) loadPost();
				}else if (pane.attr('id')=='vidPane'){
					user.ptype='pVideos';
					if ($('#streamVid').is(':empty')) loadPost();
               }else if (pane.is(":empty")) {
                  var ifh=560;
                  pane.css('min-height',ifh).addClass('ajax_loader_l');
                  _ajax.complfn=function(){pane.removeClass('ajax_loader_l').css('min-height','');}
                  if (pane.attr('id')=='calPane'){
                     var ifw=pane.parent().innerWidth();
                     var ifCal=$('<iframe name="wdCal" id="wdCal" src=""/>').appendTo(pane)
                        .css(
                           {
                              width:ifw,
                              height:ifh
                           }
                        )
                        .bind('load',function(){pane.removeClass('ajax_loader_l').height('auto');})
                        .get(0)
                     ;
                     if (ifCal && ifCal.src){
                        ifCal.src='http://'+document.domain+user.livedir+'/wdCalendar/calendar.php';
                     }else{
                        showError('Required feature missing in browser');
                     }
                  }else if (pane.attr('id')=='abtPane'){
                     pane.load(user.livedir+'/ajax/about.php',param,
                        function(response,status){
                           if (status=='success' && !user.live){
                              $('body').click(
                                 function(evt){
                                    $('.actionLinks:visible').prev('.actionLinksBtn').click();
                                 }
                              );
                              $(this).find('.actionLinksBtn')
                                 .click(
                                    function(evt){
                                       evt.stopPropagation();
                                       $(this).toggleClass('active')
                                          .next('.actionLinks').toggle().end()
                                          .parent().toggleClass('active')
                                       ;
                                    }
                                 )
                              ;
                              $(this).find('.abtSubCat h3')
                                 .click(
                                    function(evt){
                                       var oe=$(this);
                                       oe.parent().next().slideToggle('normal',
                                          function(){
                                             oe.toggleClass('open');
                                          }
                                       );
                                    }
                                 )
                                 .filter(':lt(3)')
                                 .addClass('open').parent().next().slideDown()
                              ;
                              var mceLoading=false;
                              $(this).find('textarea').focus(
                                 function(){
                                    if (!mceLoading) {
                                       $('#abtCat').find('textarea.current').removeClass('current');
                                       $(this).addClass('current');
                                       var tid=this.getAttribute('id');
                                       if (typeof tinyMCE != 'undefined'){
                                          if (tinyMCE.activeEditor) tinyMCE.activeEditor.remove();
                                          tinyMCE.execCommand('mceAddControl',false,tid);
                                          tinyMCE.activeEditor.focus();
                                       }else{
                                          mceLoading=true;
                                          $(this).tinymce(
                                             {
                                                script_url:'/js/tiny_mce/tiny_mce.js',
                                                auto_focus:tid,
                                                theme:'advanced',
                                                theme_advanced_buttons1 : "bold,italic,underline,fontselect,fontsizeselect,forecolor,backcolor",
                                                theme_advanced_buttons2 : "",
                                                theme_advanced_buttons3 : "",
                                                theme_advanced_toolbar_location : "top",
                                                theme_advanced_toolbar_align : "left",
                                                content_css : "/css/tinymce.v1.css",
                                                oninit : function(){mceLoading=false;}
                                             }
                                          );
                                       }
                                    }
                                 }
                              );
                              var prevIn='x';
                              $(this).find('.abtFields')
                                 .focusin(
                                    function(){
                                       if (this.getAttribute('id')!=prevIn && !mceLoading){
                                          $('#abtCat').find('.actionRow:visible').hide();
                                          $(this).children('.actionRow').fadeIn();                                          
                                          prevIn=this.getAttribute('id');
                                       }
                                    }
                                 )
                                 .click(
                                    function(evt){
                                       if ($(evt.target).is('button') && !mceLoading){
                                          var btn=$(evt.target);
                                          if (btn.attr('id').substr(0,6)=='btnCan'){
                                             if (typeof tinyMCE != 'undefined'){
                                                if (tinyMCE.activeEditor) tinyMCE.activeEditor.remove();
                                             }
                                             btn.closest('.abtFields').find('textarea').each(
                                                function(){
                                                   $(this).val($(this).prev('input:hidden').val());
                                                }
                                             ).removeClass('current');
                                             btn.parent().fadeOut();
                                             prevIn='x';
                                          }else{
                                             if (!_ajax.running){
                                                btn.closest('.abtFields').find('textarea').each(
                                                   function(){
                                                      /*var val=$(this).val().replace(/\s*(\r\n|\r|\n){3,}/g,'$1$1')
                                                         .replace(/(<([^>]+)>)/ig,'');
                                                      val=$.trim(val);*/
                                                      val=$(this).val();
                                                      $(this).val(val).prev('input:hidden').val(val);
                                                   }
                                                );
                                                var param=$('#abtCat').find('input:hidden').filter(function(){return this.value;}).serialize();
                                                btn.parent().addClass('ajax_loader_s');
                                                _ajax.complfn=function(){btn.parent().removeClass('ajax_loader_s');};
                                                $.post('/ajax/about.php',param+'&save=1&uid='+user.amid,
                                                   function(response,status){
                                                      if (status==response){
                                                         btn.parent().fadeOut();
                                                         prevIn='x';
                                                         if (typeof tinyMCE != 'undefined'){
                                                            if (tinyMCE.activeEditor) tinyMCE.activeEditor.remove();
                                                         }
                                                         $('#abtCat').find('textarea.current').removeClass('current');
                                                      }else{
                                                         showError(response);
                                                      }
                                                   }
                                                );
                                             }else{
                                                showError('Please wait for previous operation to complete');
                                             }
                                          }
                                          evt.preventDefault();
                                          return false;
                                       }
                                    }
                                 )
                              ;
                           }
                        }
                     );
                  }
               }
				var oldPane=this.getCurrentPane();
				if (oldPane.attr('id')=='annPane'){
					if ($('#postTabs').data('tabs')) {
						var postTabsPane=$('#postTabs').data('tabs').getCurrentPane();
						if (postTabsPane.index()>0) postTabsPane.empty();
					}
				}else if (oldPane.attr('id')=='imgPane'){
					$('#uploadPane').empty().removeAttr('style').next('.actionContDiv').hide();
				}
            }
      }
   ).data('tabs');
   if (user.live){
      //if (top.location==window.location) top.location='http://www.educator.com/'+user.login;
   }else{
      user.pidEdit=null;
      var upLoad=false;
      var pText={value:'',element:null,link:false};
      $("#postTabs").tabs("#postPanes > div",{
         effect:'fade',
         onClick:
            function(evt,i){
               type=this.getCurrentTab().attr('href');
               $('input#postType').val(type.substr(1));
               if (i==0){
                  $('button[name="submit_pd"]').removeAttr('disabled');
               }else{
                  $('button[name="submit_pd"]').attr('disabled','disabled');
               }
            },
         onBeforeClick: function(event, i) {
            if (_ajax.running) return false;
            var pane = this.getPanes().eq(i);
            this.getCurrentTab().unbind('click.reset');
            if (pane.is(":empty")) {
               var ref=this.getTabs().eq(i).attr("href");
               if (pText.element && ref=='#pText'){//return back to announce after auto-switch to links
                  pane.append(pText.element);
                  pText.element=null;
                  this.getCurrentPane().empty();
                  $(ref+'Tab>a').bind('click.reset',pTextReset);
                  return;
               }else if (!pText.value){
                  pText.element=null;
                  pText.link=false;
               }
               h=this.getCurrentPane().height();
               pane.height(h).addClass('ajax_loader_s');
               if (/^(?:#pImages|#pDocs)$/.test(ref)){
                  if (!upLoad){
                     tabs=this;
					 loadUL(ref);
                     return false;
                  }
               }
               this.getCurrentPane().empty();
               _ajax.complfn=function(){pane.removeClass('ajax_loader_s').height('auto');}
               var ct=0 // text entry check timer
               pane.load('/ajax/post_tabs.php '+ref,function(){
                  switch(ref){
                     case '#pText':
                        $(ref+' :input').addClass(typeClass)
                           .bind('keyup click paste',
                              function(evt){
                                 clearTimeout(ct);
                                 ct=setTimeout(chkEntry,(evt.type=='paste')?300:1000);
                                 if (evt.type=='paste') $(this).trigger('keyup.resize');
                              }
                           )
                           .bind('keyup.resize',autoResize)
                        ;
                        $(ref+'Tab>a').bind('click.reset',pTextReset);
                        if (user.pidEdit) pTextReset(); // setup for editing
                        break;
                     case '#pImages':
                        btnText='<p>Upload a picture from your computer</p>';
                        allExt=['jpg','png','bmp','jpeg','gif'];
                        mType='image';
                        upSet(btnText,allExt,mType,ref);
                        break;
                     case '#pDocs':
                        btnText='Upload a document from your computer<br/>';
                        allExt=[];
                        mType='document';
                        upSet(btnText,allExt,mType,ref);
                        break;
                     case '#pLinks':
                        $(ref+' :input').addClass(typeClass);
                        $('#pLinks button').click(
                           function(){
                              val=$('#postLink').val();
                              if (val && val!=$('#postLink').attr('mask')){
                                 if (val.indexOf('://')<0) val='http://'+val;
                                 $('#postLinkUrl').val(val);
                                 $('#pLinks>div').hide().parent()
                                    .append('<div class="atcCont"/>')
                                    .append('<div class="inputWrap"><textarea class="pd_utext" name="pd_data[utext][]" mask="Say something about this link...">'+pText.value+'</textarea></div>')
                                    .find('textarea.pd_utext').addClass(typeClass);
                                 if (val.match(/<(embed|object|iframe)/)){
                                    val=val.replace(/.*?(<(embed|object|iframe).*>).*/,'$1');
                                    $('#postLinkUrl').val(val);
                                    var site=val.replace(/.*?https?:\/\/(.*?)\/.*/,'$1');
                                    $('#pLinks>div.atcCont').css({'min-height':0,'text-align':'center'})
                                       .append('<b>Embed link from</b><br/>'+site);
                                 }else{
                                    if (!parseLink(val,'#pLinks>div.atcCont',cbLink)){
                                       $('#pLinks>div:gt(0)').remove();
                                       $('#pLinks>div').show();
                                    }
                                 }
                              }
                              return false;
                           }
                        );
                        if (pText.value){
                           $('#postLink').val(pText.value);
                           $('#pLinks button').trigger('click');
                           pText.value='';
                        }
                        $(ref+'Tab>a').bind('click.reset',
                           function(){
                              var pData=(user.pidEdit)?$('#'+user.pidEdit).find('.atcTitle>a').attr('href'):'';
                              $('#postLink').val(pData).blur();
                              $('#pLinks').children('div:gt(0)').remove().end()
                                 .children('div').show();
                              if (user.pidEdit){
                                 // setup parse data
                                 $('#pLinks').data('newEdit',false);
                                 $('#pLinks button').trigger('click');
                              }else{
                                 $('button[name="submit_pd"]').attr('disabled','disabled');
                              }
                           }
                        );
                        if (user.pidEdit) $(ref+'Tab>a').trigger('click.reset');
                        break;
                     default:
                  };
                  $('button[name="submit_pd"]').attr('title',$('a[href="'+ref+'"]').attr('title'));
               });
            }
         }
      });
	  $('#imgDiv td>a').click(
		function(evt){
			evt.preventDefault();
			var ref=this.getAttribute('href');
			if (!upLoad){
				loadUL(ref);
				return false;
			}
			var pane=$('#uploadPane');
			_ajax.complfn=function(){pane.removeClass('ajax_loader_s').fadeTo('slow',1);};
            pane.addClass('ajax_loader_s').fadeTo('fast',0.8).load('/ajax/post_tabs.php '+ref,function(){
	            btnText='<p>Upload a photo from your drive</p>';
	            allExt=['jpg','png','bmp','jpeg','gif'];
	            mType='image';
	            upSet(btnText,allExt,mType,'#upload');
			});
			return false;
		}
	);
	  function loadUL(ref){
          $.ajax(
             {
                url:'/js/fileuploader.js',
                success:
                   function(){
                      upLoad=true;
                      setTimeout(
                         function(){
                            $('a[href="'+ref+'"]').click();
                         },5
                      );
                   },
                error:
                   function(jqXHR, textStatus, errorThrown){
                      showError(errorThrown+': Upload application did not load. Please try again.');
                   },
                dataType:'script'
             }
          );
	  }
      function cbLink(){
         $('button[name="submit_pd"]').removeAttr('disabled');
         $('#pLinks textarea.pd_utext').bind('keyup.resize',autoResize);
         if (user.pidEdit){
            if (!$('#pLinks').data('newEdit')){
               var pBox=$('#'+user.pidEdit);
               var pData=
                  {
                     title:pBox.find('.atcTitle>a').html(),
                     desc:pBox.find('.atcDesc').html(),
                     image:pBox.find('.atcImages>img').attr('src'),
                     video:pBox.find('.atcImages>a').attr('href'),
                     text:pBox.find('.pTextData').html()
                  };
               if (pData.title!=null){
                  $('#pLinks h3.atcTitle').html(pData.title);
                  $('#pLinks input.atcTitle').val(pData.title);
               }
               if (pData.desc!=null){
                  $('#pLinks div.atcDesc').html(pData.desc);
                  $('#pLinks input.atcDesc').val(pData.desc);
               }
               if (pData.text!=null){
                  pData.text=pData.text.replace(/<br>/ig,'\n').replace(/(<([^>]+)>)/ig,'')
                  $('#pLinks textarea.pd_utext').val(pData.text).blur()
                     .trigger('keyup.resize');
               }
               if (pData.image!=null){
                  var imgIndex=$('#pLinks div.atcImages').find('img[src="'+pData.image+'"]').index();
                  var uid=$('#pLinks').find('input[id$="-data"]').attr('id').replace(/-data/,'');
                  $('#pLinks div.atcImages>img:visible').hide()
                  if (imgIndex<0){
                     var imgTotal=$('#pLinks div.atcImages').find('img').length;
                     $('<img src="'+pData.image+'" id="'+uid+(imgTotal+1)+'">').appendTo('#pLinks div.atcImages');
                     $('.atcTotalImages').html(imgTotal+1);
                     $('.atcCurImage').html(imgTotal+1);
                  }else{
                     $('#pLinks div.atcImages>img').eq(imgIndex).show();
                     $('.atcCurImage').html(imgIndex+1);
                  }
                  $('#pLinks').find('input[id="'+uid+'-data"]').val(pData.image);
               }
            }
            $('<div class="closeBtn" title="Remove this link"></div>')
               .click(
                  function(evt){
                     $('#pLinks').children('div:gt(0)').remove().end()
                        .children('div').show();
                     $('button[name="submit_pd"]').attr('disabled','disabled');
                     $('#pLinks').data('newEdit',true);
                  }
               )
               .prependTo('#pLinks>.atcCont');
         }
         cn=this;
         cn.find('h3.atcTitle,div.atcDesc').click(
            function(){
               var el=$(this);
               var h=el.outerHeight()-10;var w=el.outerWidth()-10;
               el.hide();
               $('<textarea>'+el.html().replace(/<br>/ig,'\n')+'</textarea>')
                  .css({width:w,'min-height':h})
                  .insertAfter(this)
                  .focus().select()
                  .blur(
                     function(){
                        var val=$(this).val().replace(/\s*(\r\n|\r|\n){2,}/g,'$1')
                           .replace(/(<([^>]+)>)/ig,'');
                        cn.find('input.'+el.attr('class')).val(val);
                        val=val.replace(/(\r\n|\r|\n)/,'<br>');
                        el.html(val).show();
                        $(this).remove();
                     }
                  )
                  .keyup(
                     function(evt){
                        if (evt.which==27){
                           el.show();
                           $(this).remove();
                        }
                     }
                  )
               ;
            }
         );
      }
      function pTextReset(){
         var pData;
         if (user.pidEdit){
            pData=$('#'+user.pidEdit).find('.pTextData').html();
            if (pData==null)
               pData=$('#'+user.pidEdit).find('.pImages>p').html();
            if (pData==null)
               pData=$('#'+user.pidEdit).find('.pGroupRow>.pDocName>span').html();
            if (pData==null)
               pData='';
            pData=pData.replace(/<br>/ig,'\n').replace(/(<([^>]+)>)/ig,'');
         }else{
            pData='';
         }
         var ta=$('#postTxt');
         ta.val(pData).blur();
         if (pData.length>5 && user.pidEdit){
            ta.trigger('keyup.resize');
         }else{
            ta.removeAttr('style');
         }
         pText.link=false;
      }
      function chkEntry(){
         if (!pText.link){
            var t=$.trim($('#pText :input').val());
            var re=new RegExp('^(https?:\/\/|www\.)([a-zA-Z0-9_-]+[^.])(\.[a-zA-Z]{2,})([^ ]*[^.]$)');
            if (re.test(t)){
               pText.link=true;
               pText.value=t.replace(re,'$1$2$3$4');
               pText.element=$('#pText').detach();
               $('#pLinksTab>a').trigger('click');
            }
         }
      }
      function upSet(btnText,allowExt,mType,pane){
         var uploader=new qq.FileUploader(
            {
               element:$('#uploadBox')[0],
               buttonText:btnText,
               action:'/ajax/uploader.php?type='+mType,
               allowedExtensions:allowExt,
               onSubmit:
                  function(id,fn){
                     _ajax.running=true;;
                  },
               onComplete:
                  function(id,fn,resp){
                     _ajax.running=false;
                     if (resp.error){
                        showError(resp.error);
                     }else{
                        cbMedia(resp.filename,resp.name,fn,pane);
                     }
                  },
               multiple:false,
               showMessage:
                  function(message){
                     showError(message);
                  }
            }
         );
         $(pane+'Tab>a').bind('click.reset',
            function(){
               $('#uploadCont').find('.qq-upload-list').empty();
               $('#uploadCont:hidden').fadeIn();
               $('.newUp:visible').remove();$('#groupUp').hide().find('input').val('');
               $('button[name="submit_pd"]').attr('disabled','disabled');
               if (user.pidEdit){
                  var pBox=$('#'+user.pidEdit);
                  var mtype=($('#postTabs a.current').attr('href')=='#pDocs')?'document':'image';
                  var mfile={};
                  if (mtype=='image'){
                     mfile.path=pBox.find('.pImages>a').attr('href');
                     if (mfile.path!=null){
                        mfile.name=mfile.path.replace(/.*\/.*?_/,'');
                        mfile.desc=pBox.find('.pImages>p').html();
                        cbMedia(mfile.path,mfile.name,mfile.name,pane);
                        if (mfile.desc!=null){
                           mfile.desc=mfile.desc.replace(/<br>/ig,'\n').replace(/(<([^>]+)>)/ig,'');
                           $('.newUp:visible').last().find('textarea').val(mfile.desc).trigger('keyup.resize').blur();
                        }
                     }
                  }else{
                     pBox.find('.pDocsRow').each(
                        function(){
                           mfile.path=$(this).find('.pDocName>a.pdView').attr('href');
                           mfile.name=$(this).find('.pDocName>span.ellipsis').attr('title');
                           mfile.desc=$(this).find('.pDocDesc').html();
                           cbMedia(mfile.path,mfile.name,mfile.name,pane);
                           if (mfile.desc!=null){
                              mfile.desc=mfile.desc.replace(/<br>/ig,'\n').replace(/(<([^>]+)>)/ig,'');
                              $('.newUp:visible').last().find('textarea').val(mfile.desc).trigger('keyup.resize').blur();
                           }
                        }
                     );
                     if (pBox.find('.pGroupRow').length>0)
                        $('#groupUp input').val(pBox.find('.pGroupRow>.pDocName>span').html()).blur();
                  }
               }
            }
         );
         if (user.pidEdit) $(pane+'Tab>a').trigger('click.reset');
         return uploader;
      }
      function cbMedia(fpath,fname,oname,pane){
         if (mType=='image'){
            image='/image.php?image='+fpath+'&width=64&height=48';
            $('#uploadCont').hide().next().css('margin-top',0);
         }else{
            ext=getExt(fname);
            image='/image/icon/docs/'+ext+'.png';
         }
         if ($('.newUp:visible').length==1) $('#groupUp').slideDown().find('input').addClass(typeClass);
         var newUp=$('.newUp:last-child');
         newUp.clone().appendTo(pane);
         if (user.pidEdit){
            newUp.prepend('<div class="closeBtn" title="Remove this '+mType+'"></div>');
         }
         oldMask=newUp.find('textarea').attr('mask');
         fs=oname.substr(0,oname.lastIndexOf("."));
         newUp.find('input:hidden').val(fname).end()
            .find('.upImage img').attr({src:image,alt:fname,title:oname}).end()
            .find('.closeBtn').click(
               function(evt){
                  newUp.remove();
                  if ($('.newUp:visible').length==0){
                     $('button[name="submit_pd"]').attr('disabled','disabled');
                     if ($('#groupUp:visible').length>0){
                        $('#groupUp').slideUp().find('input').val('');
                     }
                     if (mType=='image'){
                        $('#uploadCont').show();
                     }
                  }
               }).end()
            .find('textarea').attr({mask:fs+': '+oldMask,title:'About '+fs}).addClass(typeClass).bind('keyup.resize',autoResize).end()
            .slideDown();
         $('button[name="submit_pd"]').removeAttr('disabled');
		$('.actionContDiv:hidden').slideDown();
      }
      //$('input:submit').button();
      inMenu=false;
      $('button.menuBtn').click(
         function(evt){
            var pos=$(this).position();
            $(this).next(':hidden').css(
               {
                  top:pos.top+$(this).outerHeight(),
                  left:pos.left-$(this).next(':hidden').outerWidth()+$(this).outerWidth()
               }
            ).end().next().slideToggle('fast');
            return false;
         }
      ).blur(
         function(evt){
            if (!inMenu)
               $(this).next(':visible').slideUp('fast');
         }
      );
      $('#classMenuUL>li').click(
         function(){
            $(this).siblings().removeClass('active').end()
               .addClass('active');
            $('input[name="pd_p_class"]').val(this.getAttribute('id').slice(5));
            $('button#classBtn').attr('title','Publish in '+$(this).text())
               .next().slideUp('fast').end()
               .children('span').html($(this).text());
         }
      ).parent().hover(
         function(){
            inMenu=!inMenu;   //prevent blur action if in menu
         }
      );
      $('#postForm,#postFormImg,#postFormVid').submit(function(){
         if (!_ajax.running){
            var postForm=$(this);
            $('.newUp:hidden').remove();  //remove empty input fields
            $('.groupUp:hidden').remove();
            url=postForm.attr("action");
            type=(postForm.attr("method")).toUpperCase();
            data=serializeData(this)+'&submit_pd=ajax&postid='+user.pidEdit;
            //console.log(url,type,data); requires firebug, else js error happens
            
            _ajax.startfn=function(){$('.actionContDiv').addClass('ajax_loader_l');};
            _ajax.complfn=function(){$('.actionContDiv').removeClass('ajax_loader_l');};
            $.post('/ajax/on_post.php',data,function(data,status){
               if (data.pid!='0'){
                  if (data.msg!='success') showError(data.msg);
                  if (data.pid>'0'){
					if (postForm.attr('id')=='postForm'){
	                     $('#postTxt').val('').blur();
	                     setTimeout(
	                        function(){
	                           pText.element=null;  //reset link checker
	                           pText.link=false;
	                           api = $("#postTabs").data("tabs");
	                           api.click(0);
	                        },150
	                     );
					 }else{
						$('#uploadPane').empty().removeAttr('style').next('.actionContDiv').hide();
					 }
                     loadPost(data.pid);
                  }else{
                     showError('A unexpected database error occurred');
                  }
               }else{
                  showError(data.msg);
               }
            },'json');
         }
         return false;
      });
      var click={};  // to pass by ref further down
      $('#prLinks').click(
         function(evt){
            if (!($(evt.target).is('#prLinks>div') || $(evt.target).is('#prLinks>ul>li>div'))) return;
            click.tgt=$(evt.target);click.add=$(evt.target).is('#prLinks>div');click.del=false;
            var id='addLinks';var de=$('#'+id);
            if (de.length==0){
               de=$('<div class="dialog"/>').attr('id',id).hide().appendTo('body').eq(0);
            }
            if (de.is(':empty')){
               click.tgt.addClass('ajax_loader_s');
               var param={uid:user.amid,add:click.add};
               de.load('/ajax/on_links.php',param,
                  function(response,status,xhr){
                     click.tgt.removeClass('ajax_loader_s');
                     if (status=='success'){
                        var dialog=de.overlay(
                           {
                              closeOnClick:false,
                              top:'center',
                              mask:{opacity:0.4},
                              load:true,
                              fixed:false,
                              onBeforeLoad:
                                 function(){
                                    if (click.add){
                                       click.title='';
                                       click.url='';
                                       click.action='add';
                                       de.find('#dlgBtnDel').hide().end()
                                          .find('#dlgMore,#dlgSocial').show()
                                          .find('input:text').val('').blur()
                                       ;
                                    }else{
                                       click.title=click.tgt.next().text();
                                       click.url=click.tgt.next().attr('href');
                                       click.action='edit';
                                       de.find('#dlgBtnDel').show().end()
                                          .find('#dlgMore,#dlgSocial').hide()
                                          .find('input:text').val('').blur()
                                       ;
                                    }
                                       $('#prSocial a[id|="btn"]').each(
                                          function(){
                                             var id=this.getAttribute('id').replace(/btn/,'url');
                                             de.find('input#'+id).val(this.getAttribute('href')).blur();
                                          }
                                       );
                                    de.find('#dlgBody').empty()
                                       .append(
                                          '<input type="hidden" name="uid" value="'+user.amid+'">'+
                                          '<input type="hidden" name="action" value="'+click.action+'">'+
                                          '<table><tr class="dataRow">'+
                                          '<th><label for="sLinkTitle">Link Title</label></th>'+
                                          '<td width="300"><div class="inputWrap"><input class="sLinkTitle" name="links[title][]" mask="Title of the link" value="'+click.title+'"></div></td>'+
                                          '</tr><tr class="dataRow">'+
                                          '<th><label for="sLinkUrl">Link Url</label></th>'+
                                          '<td><div class="inputWrap"><input class="sLinkUrl" name="links[url][]" mask="http://" value="'+click.url+'"></div></td>'+
                                          '</tr></table>')
                                       .find('input:text').addClass(typeClass)
                                    ;
                                    var links='';
                                    click.pos=(click.add)?1000:click.tgt.parent().index();
                                    $('#prLinks>ul>li>a').each(
                                       function(i){
                                          if (i==click.pos) return false;
                                          links+=
                                             '<input type="hidden" name="links[title][]" value="'+$(this).text()+'"/>'+
                                             '<input type="hidden" name="links[url][]" value="'+$(this).attr('href')+'"/>'
                                          ;
                                       }
                                    )
                                    de.find('#dlgBody').prepend(links);
                                    if (!click.add){
                                       links='';
                                       $('#prLinks>ul>li>a:gt('+click.pos+')').each(
                                          function(){
                                             links+=
                                                '<input type="hidden" name="links[title][]" value="'+$(this).text()+'"/>'+
                                                '<input type="hidden" name="links[url][]" value="'+$(this).attr('href')+'"/>'
                                             ;
                                          }
                                       );
                                       /*$('#prSocial a[id|="btn"]').each(
                                          function(){
                                             var id=this.getAttribute('id').replace(/btn-/,'');
                                             links+=
                                                '<input type="hidden" name="social['+id+']" value="'+this.getAttribute('href')+'"/>'
                                             ;
                                          }
                                       );*/
                                       de.find('#dlgBody').append(links);
                                    }
                                    de.find('#dlgHead').html(click.tgt.attr('title'));
                                 }
                           }
                        );
                        de.find('form').submit(
                           function(evt){
                              // validate
                              var valid=true;
                              $(this).find('input:text').each(
                                 function(){
                                    if (!$(this).hasClass('socUrl') && !inputVal(this)){
                                       if (click.add){
                                          $(this).closest('table').find('input:text').removeAttr('name').val('');
                                       }else if (!click.del){
                                          $(this).focus().addClass('invalid');
                                          valid=false;
                                          return false;  //skip rest
                                       }
                                    }else if ($(this).hasClass('sLinkUrl') || $(this).hasClass('socUrl')){
                                       if ($(this).val().indexOf('://')<0) $(this).val('http://'+$(this).val());
                                       /*if ($(this).val()!='http://' && !isValidURL($(this).val())){
                                          $(this).focus().addClass('invalid');
                                          valid=false;
                                          return false;
                                       }*/
                                    }
                                    $(this).removeClass('invalid');
                                    $(this).val($(this).val().replace(/<\/?[^>]+(>|$)/g,''));
                                 }
                              );
                              if (valid && !_ajax.running){
                                 param=serializeData(this);
                                 _ajax.startfn=function(){$('#dlgFoot').addClass('ajax_loader_s')};
                                 _ajax.complfn=function(){$('#dlgFoot').removeClass('ajax_loader_s')};
                                 $.post('/ajax/on_links.php',param,
                                    function(data,status){
                                       if (data=='success'){
                                          if (click.add){
                                             $('#prSocial>ul').empty();
                                             de.find('#dlgBody>table').each(
                                                function(){
                                                   if (inputVal($(this).find('input.sLinkUrl'))){
                                                      if ($('#prLinks>ul').length==0) $('<ul></ul>').appendTo('#prLinks');
                                                      $('<li><div title="Edit or delete this link"></div><a href="'+$(this).find('input.sLinkUrl').val()+'" target="_blank" rel="nofollow">'+$(this).find('input.sLinkTitle').val()+'</a></li>').appendTo('#prLinks>ul');
                                                   }
                                                }
                                             ).end()
                                             .find('#dlgSocial input:text').each(
                                                function(){
                                                   if (inputVal(this)){
                                                      if ($('#prSocial>ul').length==0) $('<ul></ul>').appendTo('#prSocial');
                                                      var id=this.getAttribute('id').replace(/url-/,'');
                                                      $('#prSocial>ul').append("<li><a id='btn-"+id+"' href='"+this.value+"' target='_blank' rel='nofollow' style='background-image:url(/image/icon/"+id+".png)'></a></li>");
                                                   }
                                                }
                                             );
                                          }else{
                                             click.tgt.next().attr('href',de.find('input.sLinkUrl').val()).html(de.find('input.sLinkTitle').val());
                                          }
                                          if (click.del) click.tgt.parent().remove();
                                       }else{
                                          showError(data);
                                       }
                                       dialog.overlay().close();
                                    }
                                 );
                              }
                              return false;
                           }
                        )
                        .find('button#dlgBtnClose').click(
                           function(evt){
                              evt.stopPropagation();
                              dialog.overlay().close();
                              return false;
                           }
                        ).end()
                        .find('button#dlgBtnDel').click(
                           function(evt){
                              de.find('#dlgBody input:text').removeAttr('name');
                              click.del=true;
                           }
                        ).end()
                        .find('#dlgMore').click(
                           function(){
                              if (click.add){
                                 de.find('input:text').removeClass('invalid');
                                 $('#dlgBody>table:first').clone()
                                    .find('input:text').val('').addClass(typeClass)
                                    .end()
                                    .appendTo('#dlgBody')
                                 ;
                              }
                           }
                        ).end()
                        .find('input:text').addClass(typeClass)
                        ;
                     }else{
                        showError('Network failure. Please try again.');
                     }
                  }
               );
            }else{
               de.data('overlay').load();
            }
         }
      );
   }
   $('#prClass>ul>li').click(
      function(evt){
         var cl=this;
         var oldClass=user.pclass;var oldType=user.ptype;
         setCrit(cl);
         //ensure we are in the ann tab
         if (ann.getIndex()!=0){
            annDirect=false;
            ann.click(0);
         }
         if (oldClass!=user.pclass || oldType!=user.ptype){
            loadPost();
         }
         if (oldClass!=user.pclass){
            $(this).siblings().removeClass('active open').end()
               .addClass('active open');
            // refresh publish class menu button
            $('#'+this.getAttribute('id').replace(/class/,'cmenu')).click();
            //todo: change mascot
         }
         var typeList=$('#typeList');
         if (typeList.length>0){
            // if exists slide up or down
            if (oldClass!=user.pclass){
               // detach and slide down if another class was clicked
               typeList.slideUp('fast',
                  function(){
                     typeList.detach().hide().appendTo(cl).slideDown()
                        .children('li.active').removeClass('active');
                  }
               );
            }else{
               typeList.slideToggle();
               $(this).toggleClass('open');
            }
         }else{
            // create list from tabs
            typeList=$('<ul/>').attr('id','typeList').get(0);
            $('#postTabs>li>a').each(
               function(i){
                  $('<li/>').attr('id','m'+this.getAttribute('href').replace(/.*#/,''))
                     .html(this.getAttribute('title'))
                     .appendTo(typeList)
                     .click(
                        function(evt){
                           evt.stopPropagation();
                           if (evt.target==this){
                              //ensure we are in the ann tab
                              if (ann.getIndex()!=0) ann.click(0);
                              oldType=user.ptype;
                              user.ptype=this.getAttribute('id').slice(1); //discard 'm'
                              if (oldType!=user.ptype){
                                 loadPost();
                                 $(this).siblings('.active').removeClass('active').end()
                                    .addClass('active open');
                              }
                           }
                        }
                     );
               }
            );
            $(typeList).hide().appendTo(cl).slideDown();
         }
      }
   );
   function setCrit(e){
      classId=$(e).attr('id');
      user.pclass=parseInt(classId.slice(5));
      if (isNaN(user.pclass)) user.pclass=0;
      user.ptype='';
   }
}
function loadMedia(p,o){
	showError('Loading media...');
}
function loadPost(p,o){
   var param={ul:user.login,live:user.live};
   if (user.pclass>0){
      param.pclass=user.pclass;
      if (user.ptype) param.ptype=user.ptype;
   }
   var ec={};
	var streamDiv=null;
	if (user.ptype=='pImages') streamDiv=$('#streamImg');
	else if (user.ptype=='pVideos') streamDiv=$('#streamVid');
	else streamDiv=$('#streamDiv');
	if (streamDiv==null){
		showError('Unable to find stream container');
		return false;
	}
   if (typeof o=='undefined'){
      if (typeof p!='undefined') param.postid=p;
      else{
         param.postid=0;
         ec=streamDiv.children();
      }
      var e=$('<div/>').prependTo(streamDiv)
         .hide('fast',function(){
            $('<div/>').addClass('ajax_loader_l')
               .appendTo(streamDiv)
         })
         .get(0);
   }else{
      var e=$('<div/>').appendTo(streamDiv)
         .hide('fast',function(){
            $('#morePost').remove();
            $('<div/>').addClass('ajax_loader_l')
               .attr('id','moreLoader')
               .insertBefore(this)
         })
         .get(0);
      param.offset=o;
   }
   // scroll up = window height-120 + any scrolled down
   var wht='+='+(document.body.offsetHeight-$(window).scrollTop()-120)+'px';
   // fadeout edited post
   var editPost='';
   if (user.pidEdit){
      editPost=user.pidEdit+'_edit';
      var pos=$('#'+user.pidEdit).css({opacity:0.3}).attr('id',editPost)
         .offset();
      scroll(pos.top-($(window).height()-$('#'+user.pidEdit+'_edit').height())/2);
      $('#exposeMask').click();  // only if editing
   }
   $(e).load(user.livedir+'/ajax/get_posts.php',param,
      function(response,status,xhr){
         if (status=='success'){
            onStream($(this));
            $(this).siblings('.placeholder, .ajax_loader_l').remove();
            if (editPost){
               $('#'+editPost).replaceWith(this);
               $(this).children().css({opacity:0,backgroundColor:'#fff5d1'})
                  .unwrap()
                  .animate({opacity:1},'fast','swing',
                     function(){
                        pos=$(this).position();
                        var bg=$('<div class="editBg" />').css(
                           {
                              position:'absolute',
                              top:pos.top,
                              left:pos.left,
                              height:this.offsetHeight,
                              width:this.offsetWidth
                           })
                           .appendTo('#streamDiv')
                        ;
                        $(this).removeAttr('style');
                        bg.animate({opacity:0},10000,'swing',function(){$(this).remove()});
                     }
                  );
            }else
               $(this).slideDown();
            if (typeof o!='undefined'){
               scroll(wht);
            }
            if ($(this).children('.placeholder').length>0){
               $(this).addClass('placeholder')
                  .children().removeClass('placeholder');
            }
            if (ec.length>0)
               ec.remove();
         }else{
            $(this).siblings('.ajax_loader_l').fadeOut('slow',function(){$(this).remove();$(e).remove();});
            // replace error with more info
            showError('An error occurred: '+xhr.status+'/'+xhr.statusText);
         }
      }
   );
}
function onStream(ps){
   if (!user.live){
      ps.find('#pAnnounce a.close').click(
         function(evt){
            $('#pAnnounce').css('visibility','hidden');
         }
      );
      var shareBtn={
         msg:['Make this post private','Share this post'],
         con:['share this post','make this post private'],
         lbl:['Private','Share']
      }
      ps.find('.posts').each(
         function(){
            var cbMsg='';
            var sbMsg=($(this).hasClass('private'))?1:0;
            if ($(this).find('.pImages').length>0) cbMsg=' and associated media';
            if ($(this).find('.pDocs').length>0) cbMsg=' and associated documents';
            $(this).find('li.actionDelete>a').attr('title','Delete this post'+cbMsg);
            $(this).find('li.actionShare>a').attr('title',shareBtn.msg[sbMsg]).html(shareBtn.lbl[sbMsg]);
            // temp
            $(this).find('li.actionDelete').before('<li class="actionEdit"><a title="Edit this post">Edit</a></li>');
         }
      );
      $('body').click(
         function(evt){
            $('.actionLinks:visible').prev('.actionLinksBtn').click();
         }
      );
      ps.find('.actionLinksBtn')
         .click(
            function(evt){
               evt.stopPropagation();
               $(this).toggleClass('active')
                  .next('.actionLinks').toggle().end()
                  .parent().toggleClass('active')
               ;
            }
         )
      ;
      ps.find('li.actionDelete>a').click(function(evt){
         if (confirm('Do you want to '+this.getAttribute('title').toLowerCase()+'?')){
            var pid=$(this).closest('.posts').attr('id');
            var param={postid:pid,action:'close'};
            $.post('/ajax/on_mod.php',param,function(data,status){
               if (data=='success'){
                  $('#'+pid).slideUp('slow',function(){
                     if ($(this).siblings().length>0){
                        $(this).remove();
                     }else{
                        $(this).parent().remove();
                     }
                  });
                  pid=pid.slice(3)+'_pid';
                  $('#filPane').find('tr[id^="'+pid+'"]').remove();
               }else{
                  showError(data);
               }
            });
         }
         $(this).closest('.actionBar').children('.actionLinksBtn').click();
         return false;
      });
      ps.find('li.actionShare>a').click(function(evt){
         var i=(shareBtn.msg[0] == this.getAttribute('title'))?1:0;
         if (confirm('Do you want to '+shareBtn.con[i]+'?')){
            var pid=$(this).closest('.posts').attr('id');
            var param={postid:pid,action:'share',privacy:i};
            $.post('/ajax/on_mod.php',param,function(data,status){
               if (data=='success'){
                  $('#'+pid).toggleClass('private')
                     .find('li.actionShare>a').attr('title',shareBtn.msg[i]).html(shareBtn.lbl[i]);
                  //todo: change posted date to now if shared
               }else{
                  showError(data);
               }
            });
         }
         $(this).closest('.actionBar').children('.actionLinksBtn').click();
         return false;
      });
      ps.find('li.actionEdit>a').click(function(evt){
         var te=$(this);
         if (typeof jQuery().expose=='function'){
            var aBar=$(this).closest('.actionBar');
            aBar.children('.actionLinksBtn').click();
            $('#postDiv').expose(
               {
                  color:'#999',
                  closeOnEsc:false,
                  onLoad:function(){
                     $(window).bind('scroll',
                        function(){
                           $('#exposeMask').height(document.body.offsetHeight);
                        }
                     );
                  },
                  onBeforeClose:function(){
                     $(window).unbind('scroll');
                     user.pidEdit=null;
                     $('#postTabs>li>a.current').trigger('click.reset');
                     $('.actionDiv>button#cancelEdit').remove();
                     $('.posts.editing').removeClass('editing');
                  }
               }
            );
            var pType;
            $('#postTabs>li>a').each(
               function(i){
                  pType=this.getAttribute('href').replace(/.*#/,'');
                  if (aBar.siblings('.'+pType).length>0){
                     pType=i;
                     return false;
                  }
               }
            );
            user.pidEdit=$(this).closest('.posts').addClass('editing').attr('id');
            api = $("#postTabs").data("tabs");
            scroll(80);

            api.click(pType);
            $("#postTabs>li>a:eq("+pType+")").click();
            $('<button type="button" id="cancelEdit" title="Cancel edit">Cancel</button>')
               .click(
                  function(evt){
                     if (!_ajax.running)
                        $('#exposeMask').click();
                     return false;
                  }
               )
               .appendTo('.actionDiv')
            ;
         }else{
            te.addClass('ajax_loader_s');
            _ajax.complfn=function(){te.removeClass('ajax_loader_s');};
            $.ajax(
               {
                  url:user.livedir+'/js/jquery.expose.min.js',
                  success:
                     function(){
                        setTimeout(
                           function(){
                              te.click();
                           },5
                        );
                     },
                  error:
                     function(jqXHR, textStatus, errorThrown){
                        showError(errorThrown+': Expose application did not load. Please try again.');
                     },
                  dataType:'script'
               }
            );
         }
         return false;
      });
   }else{
      ps.find('a.fbCommentBtn').click(
         function(evt){
            $('.shareArea').hide();
            var pid=this.getAttribute('href').replace(/.*#/,'#');
            fbc=$(pid); // fb comment box
            if (fbc.is(':empty')){
               fbc.show();
               pid=pid.slice(4);
               //fbc.html("<fb:comments migrated='1' href='http://"+document.domain+"/"+user.login+"&p="+pid+"' xid='pid"+pid+"' numposts='3' width='540' publish_feed='true'></fb:comments>");
               fbc.html("<fb:comments migrated=1 xid='pid"+pid+"' numposts='3' width='540' publish_feed='true'></fb:comments>");
               FB.XFBML.parse(fbc.get(0));
            }else{
               fbc.slideToggle();
            }
            evt.preventDefault();
         }
      ).bind({mouseover:tooltip,mouseout:tooltip});
      
      ps.find('a.shareBtn').click(
         function(evt){
            $(this).closest('.posts').find('.fbComments').hide();
            var sArea=$(this).closest('.posts').children('.shareArea');
            var loading='Loading...';
            if (sArea.length==0){
                  sArea=$('<div class="shareArea">'+
                     '<div class="notchUp"><div class="notchUpIn" /></div>'+
                     '<div class="sharePanel">'+loading+'</div>'+
                     '</div>'
                  )
                  .hide()
                  .appendTo($(this).closest('.posts'))
               ;
               var nPos=$(this).position().left-parseInt(sArea.css('margin-left'));
               sArea.children('.notchUp').css('left',nPos);
            }
            $('.shareArea').not(sArea.get(0)).hide();
            sArea.slideToggle();
            var sPanel=sArea.children('.sharePanel');
            setTimeout(function(){sPanel.find('input').select();},600);
            
            if (sPanel.text()==loading){
               pe=$(this).closest('.posts');
               var pData=pe.find('.pTextData').html();
               if (pData==null)
                  pData=pe.find('.pImages>p').html();
               if (pData==null)
                  pData=pe.find('.pGroupRow>.pDocName>span').html();
               if (pData==null)
                  pData=pe.find('.atcDesc').html();
               if (pData==null)
                  pData='';
               var param={url:$(this).attr('href'),title:pe.find('.prName').html(),text:pData.slice(0,100)};
               sPanel.load(user.livedir+'/ajax/share.php',param,
                  function(response,status,xhr){
                     if (status=='success'){
                        var burl='http://'+document.domain+'/'+user.login;
                        $(this).find('input')
                           .val(burl)
                           .select()
                        ;
                        $(this).find('.shareIcon-fb')
                           .click(
                              function(evt){
                                 window.open('http://www.facebook.com/sharer.php?u='+encodeURIComponent(param.url)+'\u0026t='+encodeURIComponent(param.title),'Facebook-share','height=440,width=620,scrollbars=yes');
                                 return false;
                              }
                           )
                           .mouseover(tooltip)
                           .mouseout(tooltip)
                        ;
                        $(this).find('.shareIcon-tw')
                           .click(
                              function(evt){
                                 window.open('http://twitter.com/share?url='+encodeURIComponent(param.url)+'\u0026via=Educator\u0026text='+encodeURIComponent(param.text),'Twitter-share','height=650,width=1024,scrollbars=yes');
                                 return false;
                              }
                           )
                           .mouseover(tooltip)
                           .mouseout(tooltip)
                        ;
                        $(this).find('.closeBtn')
                           .click(
                              function(evt){
                                 $(this).closest('.posts').find('a.shareBtn').click();
                              }
                           )
                        ;
                     }else{
                        // replace error with more info
                        showError('An error occurred: '+xhr.status+'/'+xhr.statusText);
                     }
                  }
               );
            }
            evt.preventDefault();
         }
      );
   }
   ps.find('.atcImages a').click(
      function(evt){
         var img=$(this).parent();
         img.siblings().remove('.ytVideo');
         var mleft=img.siblings('.atcInfo').css('margin-left');
         img.fadeOut()
            .before(
               '<div class="ytVideo ajax_loader_l" title="Click to close video">'+
                  '<div></div>'+
               '</div>')
            .siblings('.atcInfo').css('margin-left','0');
         var flash=$(this).attr('href');
         if (flash.indexOf('youtube.com')>0)
            flash+='?autoplay=1&rel=0';
         else if (flash.indexOf('vimeo.com')>0)
            flash+='&autoplay=1';
         else if (flash.indexOf('blip.tv')>0)
            flash+='?autostart=true';
         img.prev().click(
            function(evt){
               if ($(evt.target).is('.ytVideo')){
                  $(this).next().fadeIn('slow')
                     .siblings('.atcInfo').css('margin-left',mleft);
                  $(this).slideUp('slow',function(){$(this).remove()});
               }
            }
         ).children().hover(
            function(){
               $(this).parent().attr('title','');
            },
            function(){
               $(this).parent().attr('title','Click to close video');
            }
         ).css(
            {'width':'510px','height':'319px'}
         ).each(
            function(){    // onFail works this way only
               var fCont=this;
               flashembed(this,
                  {
                     src:flash,
                     version:[9,45],
                     expressInstall:'http://www.educator.com/media/playerProductInstall.swf',
                     onFail:function(){
                           $(fCont).css({'width':'500px','height':'auto','margin-top':'50px','margin-bottom':'50px'})
                              .addClass('flashInstall')
                              .parent()
                              .removeClass('ajax_loader_l')
                              .find('a').attr('target','_blank');
                           $(fCont).parent().next().fadeIn();  // atcImages
                        },
                     bgcolor:'#f4f4f4',
                     allowfullscreen:true,
                     allowscriptaccess:false
                  },
                  {
                     autoStart:1,
                     autostart:true,
                     autoplay:true,
                     playerVars:'autoPlay=yes',
                     width:510,
                     height:319
                  }
               )
            }
         );
         evt.preventDefault();
      }
   ).each(
      function(){
         var btn=$(this);
         var oImg=new Image();
         oImg.src=btn.next().attr('src');
         var setPos=function(){
            setTimeout(function(){
               if (!oImg.complete){
                  setPos();
               }else{
                  //btn.css('top',btn.next().innerHeight()-btn.height());
                  btn.animate({'top':btn.next().innerHeight()-btn.height()-3});
               }
            },500);
         };
         setPos();
      }
   );
}
function parseLink(u,e,f){
   if(!isValidURL(u)){
      alert('This is not a valid url.');
      return false;
   }else{
      $(e).addClass('ajax_loader_l');
      $.post('/ajax/fetch.php', {url:u}, function(response){
         //Set Content
         $(e).html('');
         atc_images=$('<div class="atcImages"/>').hide().appendTo(e).get(0);
         vidIcon=(response.video)?' pVideosIcon':'';
         $('<div class="atcInfo">'+
               '<h3 class="atcTitle">'+response.title+'</h3>'+
               '<label class="atcUrl ellipsis">'+u+'</label>'+
               '<div class="atcDesc">'+response.description+'</div>'+
            '</div>'+
            '<div class="atcImageNav'+vidIcon+'" >'+
               '<a href="#" class="prevBtn"><img src="/image/prev.png" alt="Prev"/></a><a href="#" class="nextBtn"><img src="/image/next.png" alt="Next"/></a>'+
            '</div>'+
            '<div class="atcImageNavInfo" >'+
               'Showing <span class="atcCurImage">1</span> of <span class="atcTotalImages">'+response.total_images+'</span> images'+
            '</div>'
            )
            .hide().appendTo(e);
         var total_images = response.total_images;
         var cur_image=$(e).find('.atcCurImage').get(0);
         var uid='img-'+(new Date).getTime();//$(e).attr('id');
         $(atc_images).html(' ');
         $.each(response.images, function(a,b){
            $('<img src="'+b.img+'" id="'+uid+(a+1)+'">').appendTo(atc_images).hide();
         });
         //Show first image
         $('img#'+uid+1).fadeIn();
         $(cur_image).html(1);
         //Attach the hidden data fields
         $('<input class="atcTitle" type="hidden" name="pd_data[utitle][]" value="'+response.title+'">'+
            '<input class="atcDesc" type="hidden" name="pd_data[udesc][]" value="'+response.description+'">'+
            '<input type="hidden" name="pd_data[uimage][]" value="'+$('img#'+uid+1).attr('src')+'" id="'+uid+'-data">'+
            '<input type="hidden" name="pd_data[uvideo][]" value="'+response.video+'">'
            ).appendTo(e);
         if (total_images>1){
            // next image
            $(e).find('.nextBtn').bind("click", function(ev){
               var total=parseInt($('span.atcTotalImages').html()); // in case post edit changes it
               var index = parseInt($(cur_image).html());
               $('img#'+uid+index).hide();
               new_index = (index < total)? ++index : 1;
               $(cur_image).html(new_index);
               $('input#'+uid+'-data').val($('img#'+uid+new_index).show().attr('src'));
               ev.preventDefault();
            });
            // prev image
            $(e).find('.prevBtn').bind("click", function(ev){
               var total=parseInt($('span.atcTotalImages').html()); // in case post edit changes it
               var index = parseInt($(cur_image).html());
               $('img#'+uid+index).hide();
               new_index = (index >1)? --index : total;
               $(cur_image).html(new_index);
               $('input#'+uid+'-data').val($('img#'+uid+new_index).show().attr('src'));
               ev.preventDefault();
            });
         }else{
            $(e).find('.atcImageNav').remove();
            $(e).find('.atcImageNavInfo').remove();
         }
         //Show content
         $(e).removeClass('ajax_loader_l').children('div').fadeIn('slow');
         f.call($(e));        
      },'json');
      return true;
   }
}
function isValidURL(url){
   var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
   if(RegExp.test(url)){
      return true;
   }else{
      return false;
   }
}
function resendEmail(e){
   $(e).removeClass('ajax_error_si').addClass('ajax_loader_si');
   $.post('/ajax/resend_email.php','',
      function(response,status,xhr){
         $(e).removeClass('ajax_loader_si');
         if (response=='success'){
            $(e).addClass('ajax_success_si')
               .html('Email sent')
               .removeAttr('href');
            response='Verification email sent';
         }else{
            $(e).addClass('ajax_error_si');
         }
         // todo: show a popup
         $(e).attr('title',response);
      }
   );
   return false;  // todo: prevent default
}
function onLive(e){
   var pub=!($(this).parent().hasClass('live'));
   if (pub){
      yes=confirm('This page is going live!');
   }else{
      yes=confirm('This page will no longer be available publicly.');
   }
   if (yes){
      $(this).unbind('click');
      $(this).addClass('ajax_loader_l').html('');
      $.post('/ajax/on_live.php','pub='+(pub?1:0),function(data,status){
         if (data!='success'){
            pub=!pub;
            showError(data);
         }
         onLiveBtn(pub);
      },'text');
   }
   e.preventDefault();
}
function initProfile(){
   var wizSC=0;
   if ($('#wizSchool').length>0){
      if (inputVal($('#pi_school')) && inputVal($('#pi_classes1'))){
         wizSC=0;
      }else{
         wizSC=1;
      }
   }
   // history doesn't work with standard jquery lib (and flowtools jquery lib doesn't agree with ie)
   var api=$("ul.tabs").tabs("div.panes > div",
      {
         effect:'fade',
         history:false,
         onBeforeClick:
            function(event,tabIndex){
				if (_ajax.running) {
					showError('Please wait for current request to finish.');
					return false;
				}
               var pane = this.getPanes().eq(tabIndex);
               var photoTab=1;
				var urlTab=2;
               /*if (wizSC>0 && tabIndex>0){
                  alert('Please enter name of your school.');
                  if (pane.attr('id')!='schoolPane'){
                     this.click(0);
                  }
                  return false
               }else if ($('#photoPane').find('img[src*="dummy"]').length>0 && tabIndex>photoTab){
                  alert('Please upload your profile picture first.');
                  if (pane.attr('id')!='photoPane'){
                     this.click(photoTab);
                  }
                  return false
               }else*/
				if (tabIndex>photoTab){
					frm=$('form#frmAddClass');
					var clean=true;
					frm.find('input:text').each(
						function(){
							if (this.defaultValue!=this.value){
								clean=false;
								return false;
							}
						}
					);
					if (clean) return true;
		            url=frm.attr("action");
		            type=(frm.attr("method")).toUpperCase();
					frm.children('span.error').remove();
					var pi1=$('#f_classes1');
					if (!pi1.val() || pi1.val()==pi1.attr('mask')){
						return;
					}
		            data=serializeData(frm.get(0))+'&submit_pi=ajax';
		            frm.find('input:text').addClass('ajax_loader_si');
		            $.post(url,data,
		               function(data,status){
							$('#ajax_error:visible').slideUp();
		                  frm.find('input:text').removeClass('ajax_loader_si');
		                  if (data=='success'){
							_ajax.running=false;
							frm.find('input:text').each(
								function(){
									this.defaultValue=this.value;
								}
							);
							onTab(1);
		                  }else{
		                     $('<span />').appendTo(frm).addClass('error').html(data);
		                  }      
		               },
		               'text'
		            );
					return false;
				}
               if (pane.attr('id')=='lastPane'){
                  onTab(20);
                  return false;
               }
            },
         onClick:
			function(event,tabIndex){
				window.location=this.getTabs().eq(tabIndex).attr('href');
				$('.actNext, .actPrev').removeClass('disabled');
				if (tabIndex==this.getTabs().length){
					$('.actNext').addClass('disabled');
				}else if (tabIndex==0){
					$('.actPrev').addClass('disabled');
				}
				$('.actPrev').html('Back to Step '+tabIndex)
            }
      }
   ).data('tabs');
   $('.actPrev').click(function(){if (!$(this).hasClass('disabled')) onTab(-1)});
   $('.actNext').click(function(){if (!$(this).hasClass('disabled')) onTab(1)});
   var onTab=function(dir){
      i=api.getIndex()+dir;t=api.getPanes();
      if (t.length>i && -1<i){
         //window.location=t.eq(i).attr('href');
         if (dir>0){api.next()}
         else {api.prev()}
      }else{
		if ($('#f_login').val()!=user.login){
			//update login
			var fl=$('#f_login');
			var newlogin=fl.addClass('ajax_loader_si').val();
			fl.next('span').remove();
			var msg='';
			if (newlogin.length<user.minl || newlogin.length>user.maxl) msg='Url must be between '+user.minl+' and '+user.maxl+' characters long.';
			if (!msg && newlogin.search(/^[0-9a-zA-Z_]+$/)==-1) msg='Url may contain only letters and numbers.';
			if (msg){
				$('<span />').insertAfter(fl).addClass('error').html(msg);
				fl.removeClass('ajax_loader_si');
				return false;
			}
			$.get('/account/ajax','do=check_uniq_login&login='+newlogin,
				function(data,status){
					$('#ajax_error:visible').slideUp();
					if (data!=true) {
						$('<span />').insertAfter(fl).addClass('error').html(data);
						fl.removeClass('ajax_loader_si');
					}else{
						$.post('/ajax/newlogin.php','login='+newlogin,
							function(data,status){
								fl.removeClass('ajax_loader_si');
								if (data=='success'){
									user.login=newlogin;
							         window.location='/'+user.login;
								}else{
									$('<span />').insertAfter(fl).addClass('error').html(data);
								}
							},
							'text'
						);
					}
				}
			);
		}else window.location='/'+user.login;
      }
   };

   $('#photoPane a.inputAddBtn').click(
      function(evt){
         var input=$(this).parent().prev('td').children('input:last').get(0);
         if (inputVal(input)){
            var id=input.getAttribute('id').replace(/\d+$/, function(n){return ++n});
            $(input).clone()
               .attr('id',id)
               .val('')
               .insertAfter(input)
               .attr('mask','Another '+$(input).attr('display'))
               .addClass(typeClass)
               .focus();
         }
      }
   );
   $("#prInfo,#prClass,#prHome").submit(
      function(){
         if (!_ajax.running){
            url=$(this).attr("action");
            type=($(this).attr("method")).toUpperCase();
            data=serializeData(this)+'&submit_pi=ajax';
            onSpin('show');
            //alert(url+" : "+type+" : "+data);
            if (this.getAttribute('id')=='prClass'){
               if (inputVal($('#pi_school')) && inputVal($('#pi_classes1'))){
                  wizSC=2;
               }else{
                  wizSC=1;
               }
            }
            $.post(url,data,
               function(data,status){
                  onSpin('hide');
                  if (data=='success'){
                     if (wizSC==2) wizSC=0;
                     onTab(1);
                  }else{
                     showError(data);
                  }      
               },
               'text'
            );
         }
         return false;
      }
   );
   var onSpin=function(a){
      if ($('#noWizProfile').length){
         var cont=api.getCurrentPane().children('h2');
      }else{
         var cont=api.getCurrentTab();
      }
      if (a=='show'){
         cont.append("<img src="+getImage(2)+">");
      }else{
         cont.children('img').remove();
      }
   };
   function upSet(btnText,iType,uPanel){
      var uploader=new qq.FileUploader(
         {
            element:uPanel.find('.uploadBtn')[0],
            buttonText:btnText,
            action:'/ajax/uploader.php?'+iType+'=1',
            allowedExtensions:['jpg','png','bmp','jpeg','gif'],
            onSubmit:
               function(id,fn){
                  uPanel.find('.prUploadBox>p').hide();
                  _ajax.running=true;
               },
            onComplete:
               function(id,fn,resp){
                  _ajax.running=false;
                  if (resp.error){
                     showError(resp.error);
                  }else{
                     var pClass='';
                     if (iType=='mascot'){
                        pClass=uPanel.find('.classNo').html();
                     }
                     setPhoto(uPanel.children('.prImageBox')[0],pClass,resp.name);
                  }
               },
            multiple:false,
            showMessage:
               function(message){
                  showError(message);
               }
         }
      );
      return uploader;
   }
   upSet('Upload your photo','profile',$('#prPhotoCont'));
   $('#classPane .prImageCont').each(
      function(){
         //upSet('Upload mascot','mascot',$(this));
      }
   );
   $('#photoPane input.submit').click(
      function(evt){
         if (_ajax.running){
            showError('Please wait for current operation to complete');
         }else{
            onTab(1);
         }
         evt.preventDefault();
         return false;
      }
   );
   $('a.removePhoto').live('click',
      function(evt){
         var pClass=$(this).closest('.prImageCont').find('.classNo').html();
         var ok=true;
         if (pClass){
            var src=$(this).prev('div').find('img').attr('src');
            ok=src.indexOf(pClass)>0;
         }
         if (ok){
            setPhoto($(this).closest('div').get(0),pClass);
         }else{
            alert('Please remove the mascot from it\'s own class');
         }
         evt.preventDefault();
      }
   ).each(
      function(){
         if ($(this).prev('div').find('img[src*="dummy"]').length>0){
            $(this).css('display','none').next().show(0);   // use css instead of hide, because tab plugin shows all first time
         }else{
            $(this).show(0).next().css('display','none');
         }
      }
   );
   function setPhoto(e,cl,fn){
      _ajax.startfn=function(){
         $(e).children('div').addClass('ajax_loader_l')
         .children('img').fadeTo('fast',0.1);
      };
      _ajax.complfn=function(){
         setTimeout(
            function(){
               $(e).find('img').fadeTo('slow',1,
                  function(){
                     $(this).parent().removeClass('ajax_loader_l');
                  }
               );
            },2000
         );
      };
      param={fn:fn};
      if (cl){
         param.sm=cl;
      }
      if (fn){
         $.post('/ajax/set_photo.php',param,function(data,status){
            if (data.status=='success'){
               $(e).find('img').attr('src',data.name+"?"+(new Date).getTime()).end()
                  .find('a.removePhoto').show(0).next().css('display','none');
            }else{
               showError(data.status);
            }
         },'json');
      }else{
         $.post('/ajax/set_photo.php',param,function(data,status){
            if (data.status=='success'){
               $(e).find('a.removePhoto').css('display','none').next().show(0);
               $(e).find('img').attr('src',getImage(cl?1:0)).end()
                  .next().children('p').show().end()
                  .find('.qq-upload-list li').remove();
            }else{
               showError(data.status);
            }
         },'json');
      }
   }
}
function showError(msg){
	if (!_ajax.error) return;
   if (typeof msg!='string')
      msg='<span>An unexpected error has occurred</span>';
   else
      msg='<span>'+msg+'</span>';
   $('#ajax_error').html(msg);
   $('#ajax_error:hidden').slideDown().delay(5*1000).slideUp('slow');
}
function showErrorIn(el){
   $(el).ajaxError(function(){
      $(this).unbind('ajaxError');
      $(this).hide()
         .html("An unexpected error occurred")
         .addClass("error")
         .fadeIn().delay(15*1000).fadeOut('slow');
   });
}
function file_del(fname,func,node){
   if (confirm('Are you sure you want to delete the file?')){
      $(node).parent().addClass('ajax_loader_s');
      $(node).css('visibility','hidden');
      $.post('/ajax/delfile.php','fn='+fname,func,'text');
   }
}
function f_filesize (filesize) {
   if (filesize >= 1073741824) {
      filesize = number_format(filesize / 1073741824, 2, '.', '') + ' Gb';
   } else {
      if (filesize >= 1048576) {
         filesize = number_format(filesize / 1048576, 2, '.', '') + ' Mb';
      } else {
         if (filesize >= 1024) {
            filesize = number_format(filesize / 1024, 0) + ' Kb';
         } else {
            filesize = number_format(filesize, 0) + ' bytes';
         };
      };
   };
   return filesize;
};
function number_format( number, decimals, dec_point, thousands_sep ) {
   var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
   var d = dec_point == undefined ? "," : dec_point;
   var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
   var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}
function typeClass(){
   var ph=this.getAttribute('mask');var ec='';
   if (ph){
      setPlaceHolder(this);
      if (!this.value){
         this.value=ph;
         ec=' placeholder';
      }
      if (!this.getAttribute('title')){
         this.setAttribute('title',ph);
      }
   }
   return ($(this).attr('type')+ec);
}
function setPlaceHolder(e){
   var phc='placeholder';
   $(e).focus(function(){
      if ($.trim(this.value)==$(this).attr('mask')){
         this.value='';
         $(this).removeClass(phc);
      }
   })
   .blur(function(){
      var val=$.trim(this.value); var mask=$(this).attr('mask');
      if (!val || val==mask){
         $(this).addClass(phc);
         this.value=mask;
      }else{
         $(this).removeClass(phc);
         this.value=val;
      }
   });
}
function serializeData(t){
   aInp=[];
   $(t).find(':input').each(function(){
      if ($(this).attr('mask') && $(this).val()==$(this).attr('mask')){
         $(this).val('');
         aInp.push($(this));
      }
   });
   data=$(t).serialize();
   $.map(aInp,function(o){o.blur();return null;});
   return data;
}
function getExt(fn){
   p=fn.lastIndexOf('.');
   if (p>0 && fn.length-p<6 && fn.length-p>1){
      return (fn.substring(p+1));
   }else{
      return ('none');
   }
}
function inputVal(input){
   $(input).focus(); // blank it if mask only
   return ($(input).val());
}
function remBadChars(str){
   if (!str)
      return ('');
   return (str.replace(/[^a-zA-Z0-9]+/g,'_'));
}
function getImage(n){
   if (n==0) return '/image/dummy.png';
   if (n==1) return '/image/dummy-logo.png';
   if (n==2) return '/css/spinner_s.gif';
   if (n==3) return '/css/spinner_l.gif';
}
function preload(aPre) {
   if (document.images) {
      var imageArray = new Array();
      var tmpArray=new Array();
      for (list in aPre){
         j=imageArray.length;
         tmpArray = aPre[list][0].split(',');
         for (var i=0;i<tmpArray.length;i++){
            imageArray[i+j]=aPre[list][1]+tmpArray[i];
         }
      }
      preloadImages.queue_images(imageArray);
   }
}
preloadImages = 
{
  count: 0 /* keep track of the number of images */
  ,loaded: 0 /* keeps track of how many images have loaded */
  ,onComplete: function(){} /* fires when all images have finished loadng */
  ,onLoaded: function(){} /*fires when an image finishes loading*/
  ,loaded_image: "" /*access what has just been loaded*/
  ,images: [] /*keeps an array of images that are loaded*/
  ,incoming:[] /*this is for the process queue.*/
  /* this will pass the list of images to the loader*/
  ,queue_images: function(images)
  {
    //make sure to reset the counters
    this.loaded = 0;
    
    //reset the images array also
    this.images = [];
    
    //record the number of images
    this.count = images.length;
    
    //store the image names
    this.incoming = images;
    
    //start processing the images one by one
    this.process_queue();
  }
  ,process_queue: function()
  {
    //pull the next image off the top and load it
    this.load_image(this.incoming.shift());
  }
  /* this will load the images through the browser */
  ,load_image: function(image)
  {
    var this_ref = this;
    var preload_image = new Image;
    
    preload_image.onload = function()
    {
      //store the loaded image so we can access the new info
      this_ref.loaded_image = preload_image;
      
      //push images onto the stack
      this_ref.images.push(preload_image);
      
      //note that the image loaded
      this_ref.loaded +=1;
      
      //fire the onloaded
      (this_ref.onLoaded)();
      
      //if all images have been loaded launch the call back
      if(this_ref.count == this_ref.loaded)
      {
        (this_ref.onComplete)(); 
      }
      //load the next image
      else
      {
        this_ref.process_queue();
      }
    }
    preload_image.src = image;
  }
}
function nl2br (str, is_xhtml) {
    // http://kevin.vanzonneveld.net
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';

    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function autoResize(){
   if (typeof this.measureDiv=='undefined'){
      var te=$(this)
      this.originalHeight=te.height()
      $('#measureDiv').remove();
      this.measureDiv=$('<div id="measureDiv" />')
         .css(
            {
               display:'none',
               width:te.width()-5,
               fontSize:te.css('fontSize'),
               lineHeight:te.css('lineHeight'),
               fontFamily:te.css('fontFamily'),
               wordWrap:'break-word'
            }
         )
         .appendTo('body')
   }
   var ht=this.measureDiv.html(nl2br(this.value.replace(/  /g,'&nbsp; '))+'m')
      .height()
   ht=(ht>this.originalHeight)?ht:this.originalHeight;
   this.style.height=ht+'px'
}
function scroll(h){
   if ($.browser.opera) $('html').animate(
      {scrollTop:h},1000);
   else $('html,body').animate(
      {scrollTop:h},1000);
}
function tooltip(evt){
   var te=evt.target;
   if (typeof te.ttip=='undefined'){
      te.ttip=te.getAttribute('title');
      te.setAttribute('title','');
   }
   $('.tooltip').remove();
   if (evt.type=='mouseover'){
      var tt=$('<div class="tooltip" />').html(te.ttip+'<span class="ttArrow" />')
         .hide()
         .appendTo('body')
      ;
      var pos=$(te).offset();
      pos.center=Math.round(tt.outerWidth()/2);
      pos.height=tt.outerHeight();
      tt.children('.ttArrow').css({left:pos.center-5,top:pos.height-3});
      pos.top=Math.round(pos.top-pos.height-4);
      pos.left=Math.round(pos.left-pos.center+te.offsetWidth/2);
      tt.css({top:pos.top,left:pos.left}).fadeIn('fast');
   }
}
/* 
  * To Title Case 2.1 – http://individed.com/code/to-title-case/
  * Copyright © 2008–2013 David Gouch. Licensed under the MIT License.
 */

String.prototype.toTitleCase = function(){
  var smallWords = /^(a|an|and|as|at|but|by|en|for|if|in|nor|of|on|or|per|the|to|vs?\.?|via)$/i;

  return this.replace(/[A-Za-z0-9\u00C0-\u00FF]+[^\s-]*/g, function(match, index, title){
    if (index > 0 && index + match.length !== title.length &&
      match.search(smallWords) > -1 && title.charAt(index - 2) !== ":" &&
      (title.charAt(index + match.length) !== '-' || title.charAt(index - 1) === '-') &&
      title.charAt(index - 1).search(/[^\s-]/) < 0) {
      return match.toLowerCase();
    }

    if (match.substr(1).search(/[A-Z]|\../) > -1) {
      return match;
    }

    return match.charAt(0).toUpperCase() + match.substr(1);
  });
};