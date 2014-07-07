<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head id="Head1">
   <title>Educator-Connect - Calendar</title>
   <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
   <link href="css/dailog.css" rel="stylesheet" type="text/css" />
   <link href="css/calendar.css" rel="stylesheet" type="text/css" /> 
   <link href="css/dp.css" rel="stylesheet" type="text/css" />   
   <link href="css/alert.css" rel="stylesheet" type="text/css" /> 
   <link href="css/main.css" rel="stylesheet" type="text/css" /> 

   <script src="src/jquery.min.js" type="text/javascript"></script>  
   <script src="src/Plugins/Common.js" type="text/javascript"></script>    
   <script src="src/Plugins/datepicker_lang_US.js" type="text/javascript"></script>     
   <script src="src/Plugins/jquery.datepicker.js" type="text/javascript"></script>
   <script src="src/Plugins/jquery.alert.js" type="text/javascript"></script>    
   <script src="src/Plugins/jquery.ifrmdailog.js" defer="defer" type="text/javascript"></script>
   <script src="src/Plugins/wdCalendar_lang_US.js" type="text/javascript"></script>    
   <script src="src/Plugins/jquery.calendar.js" type="text/javascript"></script>   
   <script src="/js/jquery-qtip/jquery.qtip-1.0.0-rc3.min.js" type="text/javascript"></script>

   <script type="text/javascript">
     user=parent.user;
     if (!user){
        alert('This mode is not supported');
     }else{
      $(document).ready(function() {
        var view="month";          
        
         var DATA_FEED_URL = "php/datafeed.php";
         var op = {
             view: view,
             theme:0,
             showday: new Date(),
             EditCmdhandler:Edit,
             DeleteCmdhandler:Delete,
             ViewCmdhandler:showTip,    
             onWeekOrMonthToDay:wtd,
             onBeforeRequestData: cal_beforerequest,
             onAfterRequestData: cal_afterrequest,
             onRequestDataError: cal_onerror, 
             autoload:true,
             url: DATA_FEED_URL + "?method=list",  
             quickAddUrl: DATA_FEED_URL + "?method=add", 
             quickUpdateUrl: DATA_FEED_URL + "?method=update",
             quickDeleteUrl: DATA_FEED_URL + "?method=remove"        
         };
         if (user.live){
            op.readonly=true;
            $('#faddbtn').next('.btnseparator').remove().end().remove();
         }
         $('.ftitle').html(user.name+"'s Calendar");
         var $dv = $("#calhead");
         //var _MH = document.documentElement.clientHeight;
         var dvH = $dv.height() + 2;
         //op.height = _MH - dvH;
         op.height = 560-dvH;
         op.eventItems =[];

         var p = $("#gridcontainer").bcalendar(op).BcalGetOp();
         if (p && p.datestrshow) {
             $("#txtdatetimeshow").text(p.datestrshow);
         }
         $("#caltoolbar").noSelect();
         
         $("#hdtxtshow").datepicker({ picker: "#txtdatetimeshow", showtarget: $("#txtdatetimeshow"),
         onReturn:function(r){                          
                         var p = $("#gridcontainer").gotoDate(r).BcalGetOp();
                         if (p && p.datestrshow) {
                             $("#txtdatetimeshow").text(p.datestrshow);
                         }
                  } 
         });
         function cal_beforerequest(type)
         {
             var t="Loading data...";
             switch(type)
             {
                 case 1:
                     t="Loading data...";
                     break;
                 case 2:                      
                 case 3:  
                 case 4:    
                     t="The request is being processed ...";                                   
                     break;
             }
             $("#errorpannel").hide();
             $("#loadingpannel").html(t).show();    
         }
         function cal_afterrequest(type)
         {
             switch(type)
             {
                 case 1:
                     $("#loadingpannel").hide();
                     break;
                 case 2:
                 case 3:
                 case 4:
                     $("#loadingpannel").html("Success!");
                     window.setTimeout(function(){ $("#loadingpannel").hide();},2000);
                 break;
             }              
            
         }
         function cal_onerror(type,data)
         {
             $("#errorpannel").show();
         }
         function Edit(data)
         {
            var eurl="edit.php?id={0}&start={2}&end={3}&isallday={4}&title={1}&amid="+user.amid;   
             if(data)
             {
                 var url = StrFormat(eurl,data);
                 OpenModelWindow(url,{ width: 550, height: 300, caption:"Manage the Calendar",onclose:function(){
                    $("#gridcontainer").reload();
                 }});
             }
         }
         function View(data)
         {
             var str = "";
             $.each(data, function(i, item){
                 str += "[" + i + "]: " + item + "\n";
             });
             alert(str);               
         }
         function Delete(data,callback)
         {           
             
             $.alerts.okButton="OK";  
             $.alerts.cancelButton="Cancel";  
             hiConfirm("Are you sure you want to delete this event?", 'Confirm',function(r){ r && callback(0);});           
         }
         function wtd(p)
         {
            if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }
             $("#caltoolbar div.fcurrent").each(function() {
                 $(this).removeClass("fcurrent");
             })
             $("#showdaybtn").addClass("fcurrent");
         }
         //to show day view
         $("#showdaybtn").click(function(e) {
             //document.location.href="#day";
             $("#caltoolbar div.fcurrent").each(function() {
                 $(this).removeClass("fcurrent");
             })
             $(this).addClass("fcurrent");
             var p = $("#gridcontainer").swtichView("day").BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }
         });
         //to show week view
         $("#showweekbtn").click(function(e) {
             //document.location.href="#week";
             $("#caltoolbar div.fcurrent").each(function() {
                 $(this).removeClass("fcurrent");
             })
             $(this).addClass("fcurrent");
             var p = $("#gridcontainer").swtichView("week").BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }

         });
         //to show month view
         $("#showmonthbtn").click(function(e) {
             //document.location.href="#month";
             $("#caltoolbar div.fcurrent").each(function() {
                 $(this).removeClass("fcurrent");
             })
             $(this).addClass("fcurrent");
             var p = $("#gridcontainer").swtichView("month").BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }
         });
         
         $("#showreflashbtn").click(function(e){
             $("#gridcontainer").reload();
         });
         
         //Add a new event
         $("#faddbtn").click(function(e) {
             var url ="edit.php?amid="+user.amid;
             OpenModelWindow(url,{ width: 550, height: 300, caption: "Create New Event"});
         });
         //go to today
         $("#showtodaybtn").click(function(e) {
             var p = $("#gridcontainer").gotoDate().BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }


         });
         //previous date range
         $("#sfprevbtn").click(function(e) {
             var p = $("#gridcontainer").previousRange().BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }

         });
         //next date range
         $("#sfnextbtn").click(function(e) {
             var p = $("#gridcontainer").nextRange().BcalGetOp();
             if (p && p.datestrshow) {
                 $("#txtdatetimeshow").text(p.datestrshow);
             }
         });
         
         function showTip(data){
            //console.log(this);return;

            // Destroy currrent tooltip if present
            if($(this).data("qtip")){
               $(this).qtip("destroy");
            }
            
            var displayData = this.getAttribute('title');
            
            // use the user specified colors from the agenda item.
            var backgroundColor = this.style.color;
            var foregroundColor = 'white';
            var myStyle = {
               border: {
                  width: 2,
                  radius: 5
               },
               padding: 5, 
               textAlign: "left",
               tip: true,
               name: "red" // other style properties are inherited from dark theme		
            };
            if(backgroundColor != null && backgroundColor != ""){
               myStyle["backgroundColor"] = backgroundColor;
            }
            if(foregroundColor != null && foregroundColor != ""){
               myStyle["color"] = foregroundColor;
            }
            // apply tooltip
            $(this).qtip({
               content: displayData,
               position: {
                  corner: {
                     tooltip: "bottomMiddle",
                     target: "topMiddle"			
                  },
                  adjust: { 
                     mouse: true,
                     x: 0,
                     y: -15
                  },
                  target: "mouse"
               },
               show: { 
                  when: { 
                     event: 'mouseover'
                  }
               },
               style: myStyle
            });

         };
         
      });

     }
   </script>    
   </head>
   <body>
   <div>

   <div id="calhead" style="padding-left:1px;padding-right:1px;">          
         <div class="cHead"><div class="ftitle">Connect Calendar</div>
         <div id="loadingpannel" class="ptogtitle loadicon" style="display: none;">Loading data...</div>
          <div id="errorpannel" class="ptogtitle loaderror" style="display: none;">Sorry, could not load your data, please try again later</div>
         </div>          
         
         <div id="caltoolbar" class="ctoolbar">
           <div id="faddbtn" class="fbutton">
             <div><span title='Click to create new event' class="addcal">
             New Event                
             </span></div>
         </div>
         <div class="btnseparator"></div>
          <div id="showtodaybtn" class="fbutton">
             <div><span title='Click to go back to today ' class="showtoday">
             Today</span></div>
         </div>
           <div class="btnseparator"></div>

         <div id="showdaybtn" class="fbutton">
             <div><span title='Day' class="showdayview">Day</span></div>
         </div>
           <div  id="showweekbtn" class="fbutton">
             <div><span title='Week' class="showweekview">Week</span></div>
         </div>
           <div  id="showmonthbtn" class="fbutton fcurrent">
             <div><span title='Month' class="showmonthview">Month</span></div>

         </div>
         <div class="btnseparator"></div>
           <div  id="showreflashbtn" class="fbutton">
             <div><span title='Refresh view' class="showdayflash">Refresh</span></div>
             </div>
          <div class="btnseparator"></div>
         <div id="sfprevbtn" title="Previous"  class="fbutton">
           <span class="fprev"></span>

         </div>
         <div id="sfnextbtn" title="Next" class="fbutton">
             <span class="fnext"></span>
         </div>
         <div class="fshowdatep fbutton">
                 <div>
                     <input type="hidden" name="txtshow" id="hdtxtshow" />
                     <span id="txtdatetimeshow">Today &or;</span>

                 </div>
         </div>
         
         <div class="clear"></div>
         </div>
   </div>
   <div style="padding:1px;">

     <div class="t1 chromeColor">
         &nbsp;</div>
     <div class="t2 chromeColor">
         &nbsp;</div>
     <div id="dvCalMain" class="calmain printborder">
         <div id="gridcontainer" style="overflow-y: visible;">
         </div>
     </div>
     <div class="t2 chromeColor">

         &nbsp;</div>
     <div class="t1 chromeColor">
         &nbsp;
     </div>   
     </div>

   </div>

</body>
</html>
