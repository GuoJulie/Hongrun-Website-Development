/*----------------------------------------------------
 *
 * Obit - A Visual Composer Extension Plugin
 * by TrueThemes
 *
 * http://codecanyon.net/user/TrueThemes/portfolio
*
----------------------------------------------------*/
/*--------------------------------------------------------------
Fire up Functions on Page Load
--------------------------------------------------------------*/
jQuery(document).ready(function () {
    "use strict";
    //flexslider
    truethemes_flex_testimonial();
    //animated elements
    truethemes_animation_init();
    truethemes_counter();
    truethemes_progress_bar();
    truethemes_progress_bar_vertical();
    truethemes_circle_loader();
    truethemes_circle_loader_icon();
    //everything else
    truethemes_accordions();
    truethemes_notify_boxes();
    truethemes_cleanUp();
    //prettyPhoto
    jQuery("a[data-gal^='prettyPhoto']").prettyPhoto({social_tools: false});

    //prevents bootstrap-tabs from jumping when clicked
    jQuery('.nav-tabs li a, .nav-pills li a').click( function(e) {
        history.pushState( null, null, $(this).attr('href') );
    });
});
/*--------------------------------------------------------------
Flexslider used for Testimonials
--------------------------------------------------------------*/
function truethemes_flex_testimonial() {
    jQuery('.orbit-testimonial-1-flexslider').flexslider({
            animation: "slide",
            namespace: "orbit-"
        });
    jQuery('.orbit-testimonial-2-flexslider').flexslider({
            animation: "slide",
            controlNav: "thumbnails",
            namespace: "orbit-"
        });
}
/*--------------------------------------------------------------
Functions used for "counter elements"
--------------------------------------------------------------*/
(function ($) {
    "use strict";
    $.fn.countTo = function (options) {
        options = $.extend({}, $.fn.countTo.defaults, options || {});
        var loops = Math.ceil(options.speed / options.refreshInterval),
            increment = (options.to - options.from) / loops;
        return $(this).each(function () {
            var _this = this,
                loopCount = 0,
                value = options.from,
                interval = setInterval(updateTimer, options.refreshInterval);

            function updateTimer() {
                value += increment;
                loopCount++;
                $(_this).html(value.toFixed(options.decimals));
                if (typeof (options.onUpdate) === 'function') {
                    options.onUpdate.call(_this, value)
                }
                if (loopCount >= loops) {
                    clearInterval(interval);
                    value = options.to;
                    if (typeof (options.onComplete) === 'function') {
                        options.onComplete.call(_this, value)
                    }
                }
            }
        })
    };
    $.fn.countTo.defaults = {
        from: 0,
        to: 100,
        speed: 1000,
        refreshInterval: 100,
        decimals: 0,
        onUpdate: null,
        onComplete: null
    }
})(jQuery);
/*--------------------------------------------------------------
Initialize waypoint() animation classes
--------------------------------------------------------------*/
function truethemes_animation_init() {
    jQuery(".tt_in_from_center, .tt_in_from_left, .tt_in_from_right, .tt_in_from_top, .tt_in_from_bottom").waypoint(function () {
        if (!jQuery(this).hasClass("tt_animate_start")) {
            var e = jQuery(this);
            setTimeout(function () {
                e.addClass("tt_animate_start")
            }, 20)
        }
    }, {
        offset: "85%",
        triggerOnce: !0
    })
}
/*--------------------------------------------------------------
Animated Number Counter
--------------------------------------------------------------*/
function truethemes_counter() {
    "use strict";
    if (jQuery('.vision-counter.vision-zero').length) {
        jQuery('.vision-counter.vision-zero').each(function () {
            if (!jQuery(this).hasClass('executed')) {
                jQuery(this).addClass('executed');
                jQuery(this).appear(function () {
                    jQuery(this).parent().css('opacity', '1');
                    var $max = parseFloat(jQuery(this).text());
                    jQuery(this).countTo({
                        from: 0,
                        to: $max,
                        speed: 1500,
                        refreshInterval: 100
                    })
                }, {
                    accX: 0,
                    accY: -200
                })
            }
        })
    }
}
/*--------------------------------------------------------------
Animated Progress Bar
--------------------------------------------------------------*/
function truethemes_progress_bar() {
    "use strict";
    if (jQuery('.vision-progress-section').length) {
        jQuery('.vision-progress-section').each(function () {
            jQuery(this).appear(function () {
                truethemes_progress_bar_counter(jQuery(this));
                var number = jQuery(this).find('.progress-bar').data('number');
                jQuery(this).find('.progress-bar').css('width', '0%');
                jQuery(this).find('.progress-bar').animate({
                    'width': number + '%'
                }, 1500);
                jQuery(this).find('.progress_number_wrapper').css('width', '0%');
                jQuery(this).find('.progress_number_wrapper').animate({
                    'width': number + '%'
                }, 1500)
            }, {
                accX: 0,
                accY: -200
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Progress Bar Counter
--------------------------------------------------------------*/
function truethemes_progress_bar_counter($this) {
    "use strict";
    var number = parseFloat($this.find('.progress-bar').data('number'));
    if ($this.find('.vision-progress-number span').length) {
        $this.find('.vision-progress-number span').each(function () {
            jQuery(this).parents('.progress_number_wrapper').css('opacity', '1');
            jQuery(this).countTo({
                from: 0,
                to: number,
                speed: 1500,
                refreshInterval: 50
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Progress Bar - Vertical
--------------------------------------------------------------*/
function truethemes_progress_bar_vertical() {
    "use strict";
    if (jQuery('.vision-progress-section-vertical').length) {
        jQuery('.vision-progress-section-vertical').each(function () {
            jQuery(this).appear(function () {
                truethemes_progress_bar_vertical_counter(jQuery(this));
                var number = jQuery(this).find('.progress-bar-vertical').data('number');
                jQuery(this).find('.progress-bar-vertical').css('height', '0%');
                jQuery(this).find('.progress-bar-vertical').animate({
                    height: number + '%'
                }, 1500)
            }, {
                accX: 0,
                accY: -200
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Progress Bar Counter - Vertical
--------------------------------------------------------------*/
function truethemes_progress_bar_vertical_counter($this) {
    "use strict";
    if ($this.find('.vision-progress-number span').length) {
        $this.find('.vision-progress-number span').each(function () {
            var $max = parseFloat(jQuery(this).text());
            jQuery(this).countTo({
                from: 0,
                to: $max,
                speed: 1500,
                refreshInterval: 50
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Circle Loader
--------------------------------------------------------------*/
function truethemes_circle_loader() {
    "use strict";
    if (jQuery('.vision-circle-number').length) {
        jQuery('.vision-circle-number').each(function () {
            //variables for easyPieChart
            var $trackColor = jQuery(this).data('trackcolor');
            var $barColor   = jQuery(this).data('barcolor');
            var $lineWidth  = jQuery(this).data('linewidth');
            var $lineCap    = jQuery(this).data('linecap');
  
            jQuery(this).appear(function () {
                truethemes_circle_loader_counter(jQuery(this));
                jQuery(this).parent().css('opacity', '1');
                jQuery(this).easyPieChart({
                    animate: 1500,
                    size: 174,
                    trackColor: $trackColor,
                    barColor: $barColor,
                    lineWidth: $lineWidth,
                    lineCap: $lineCap,
                    scaleColor: false
                })
            }, {
                accX: 0,
                accY: -200
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Pie Chart (icon version)
--------------------------------------------------------------*/
function truethemes_circle_loader_icon() {
    "use strict";
    if (jQuery('.vision-circle-icon').length) {
        jQuery('.vision-circle-icon').each(function () {
            //variables for easyPieChart
            var $trackColor = jQuery(this).data('trackcolor');
            var $barColor   = jQuery(this).data('barcolor');
            var $lineWidth  = jQuery(this).data('linewidth');
            var $lineCap    = jQuery(this).data('linecap');
  
            jQuery(this).appear(function () {
                jQuery(this).parent().css('opacity', '1');
                jQuery(this).easyPieChart({
                    animate: 1500,
                    size: 174,
                    trackColor: $trackColor,
                    barColor: $barColor,
                    lineWidth: $lineWidth,
                    lineCap: $lineCap,
                    scaleColor: false
                })
            }, {
                accX: 0,
                accY: -200
            })
        })
    }
}
/*--------------------------------------------------------------
Animated Circle Loader (number counter)
--------------------------------------------------------------*/
function truethemes_circle_loader_counter($this) {
    "use strict";
    jQuery($this).css('opacity', '1');
    var $max = parseFloat(jQuery($this).find('.vision-circle-number').text());
    jQuery($this).find('.vision-circle-number').countTo({
        from: 0,
        to: $max,
        speed: 1500,
        refreshInterval: 50
    })
}
/*--------------------------------------------------------------
Accordions
--------------------------------------------------------------*/
function truethemes_accordions(){
    var accordions = jQuery('.vision-accordion');
    if(accordions.length < 1){
        return;
    }
    accordions.each(function(){
        var that = jQuery(this);
        var handlers = jQuery(this).children('dt');
        handlers.click(function(){
            // If statement added to allow closing all accordion elements.
            if(jQuery(this).hasClass('current')){
                jQuery(this).removeClass('current').next().slideUp();
                return;
            }
            that.children('dt.current').removeClass('current').next().slideUp();
            jQuery(this).toggleClass('current');
            jQuery(this).next('dd').slideToggle();
        });
    });
}
/*--------------------------------------------------------------
Notification Boxes
--------------------------------------------------------------*/
function truethemes_notify_boxes() {

jQuery(document).ready(function(){

    jQuery('.closeable').closeThis({
        animation: 'fadeAndSlide',  // set animation
        animationSpeed: 400         // set animation speed
    });
    
});

(function($)
{
    $.fn.closeThis = function(options)
    {
        var defaults = {
            animation: 'slide',
            animationSpeed: 300
        };
        
        var options = $.extend({}, defaults, options);
        
        return this.each(function()
        {
            var message = $(this);
            
            message.css({cursor: 'pointer'});
            
            message.click(function()
            {
                hideMessage(message);
            });
            
            function hideMessage(object)
            {
                switch(options.animation)
                {
                    case 'fade':
                        fadeAnimation(object);
                        break;
                    case 'slide':
                        slideAnimation(object);
                        break;
                    case 'size':
                        sizeAnimation(object);
                        break;
                    case 'fadeThenSlide':
                        fadeAndSlideAnimation(object);
                        break;
                    default:
                        fadeAndSlideAnimation(object);
                }
            }
            
            function fadeAnimation(object)
            {
                object.fadeOut(options.animationSpeed);
            }
            
            function slideAnimation(object)
            {
                object.slideUp(options.animationSpeed);
            }
            
            function sizeAnimation(object)
            {
                object.hide(options.animationSpeed);
            }
            
            function fadeAndSlideAnimation(object)
            {
                object.fadeTo(options.animationSpeed, 0, function() { slideAnimation(message) } );
            }
            
        });
    }
})(jQuery);

}
/*--------------------------------------------------------------
Cleanup
--------------------------------------------------------------*/
function truethemes_cleanUp() {
    jQuery(".tt-contentbox-content").find('br:first').remove();
    jQuery("body").find('p:empty').remove();
    jQuery(".vision-vector-list").find('br').remove();
}
/*--------------------------------------------------------------
Tabs 1 + Tabs 3
--------------------------------------------------------------*/
function truethemes_orbit_tabs(tab_id){
     var tab_ids = new Array();
     var active_tab_id = new Array();
     var nav_tab_title = new Array();
     var fontawesome_icon_input = new Array();
     var Tab = jQuery('#'+tab_id+'');
     jQuery(Tab).find('div').each(function() {
       tab_ids.push(this.id);
     });
     jQuery(Tab).find('div').each(function() {
       var t = jQuery(this).attr('data-title');
       nav_tab_title.push(t);
     });  
     jQuery(Tab).find('div').each(function() {
       var t = jQuery(this).attr('data-icon');
       fontawesome_icon_input.push(t);
     });   
     jQuery(Tab).find('div').each(function() {
       if (jQuery(this).hasClass('active')) {
         active_tab_id.push(this.id);
       }
     });
     var cList = jQuery('ul.'+tab_id+'');
     jQuery.each(tab_ids, function(i) {
       if (tab_ids[i] != '') {
         if (tab_ids[i] == active_tab_id) {
           var li = jQuery('<li/>').addClass('active').appendTo(cList);
         } else {
           var li = jQuery('<li/>').appendTo(cList);
         }
         var a = jQuery('<a/>')
           .attr('href', '#' + tab_ids[i] + '')
           .attr('role', 'tab')
           .attr('data-toggle', 'tab')
           .text(''+ nav_tab_title[i] + '')
           .appendTo(li);
         var s = jQuery('<span/>')
           .addClass('fa '+ fontawesome_icon_input[i] +'')
           .prependTo(a);  
       }
     });
}
/*--------------------------------------------------------------
Tabs 2
--------------------------------------------------------------*/
function truethemes_orbit_tabs_2(tab_id){
     var tab_ids = new Array();
     var active_tab_id = new Array();
     var nav_tab_title = new Array();
     var Tab = jQuery('#tab2-'+tab_id+'');
     jQuery(Tab).find('div').each(function() {
       tab_ids.push(this.id);
     });
     jQuery(Tab).find('div').each(function() {
       var t = jQuery(this).attr('data-title');
       nav_tab_title.push(t);
     });
     jQuery(Tab).find('div').each(function() {
       if (jQuery(this).hasClass('active')) {
         active_tab_id.push(this.id);
       }
     });
     var cList = jQuery('ul.'+tab_id+'');
     jQuery.each(tab_ids, function(i) {
       if (tab_ids[i] != '') {
         if (tab_ids[i] == active_tab_id) {
           var li = jQuery('<li/>').addClass('active').appendTo(cList);
         } else {
           var li = jQuery('<li/>').appendTo(cList);
         }
         var a = jQuery('<a/>')
           .attr('href', '#' + tab_ids[i] + '')
           .attr('role', 'tab')
           .attr('data-toggle', 'tab')
           .text(''+ nav_tab_title[i] + '')
           .appendTo(li);
       }
     });
}