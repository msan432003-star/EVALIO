/*=============================================================
    Examiner Panel - Custom UI JavaScript
=============================================================*/

(function ($) {
    "use strict";
    var mainApp = {

        main_fun: function () {
            /*====================================
            METIS MENU 
            ======================================*/
            if ($('#main-menu').length > 0 && typeof $.fn.metisMenu === 'function') {
                $('#main-menu').metisMenu();
            }

            /*====================================
              LOAD APPROPRIATE MENU BAR
           ======================================*/
            $(window).bind("load resize", function () {
                if ($(this).width() < 768) {
                    $('div.sidebar-collapse').addClass('collapse');
                } else {
                    $('div.sidebar-collapse').removeClass('collapse');
                }
            });

            /*====================================
            SAFE MORRIS CHARTS INITIALIZATION (if present)
            ======================================*/
            if (typeof Morris !== 'undefined') {
                if ($('#morris-bar-chart').length > 0) {
                    Morris.Bar({
                        element: 'morris-bar-chart',
                        data: [{ y: '2006', a: 100, b: 90 }, { y: '2007', a: 75, b: 65 }, { y: '2008', a: 50, b: 40 }],
                        xkey: 'y',
                        ykeys: ['a', 'b'],
                        labels: ['Series A', 'Series B'],
                        hideHover: 'auto',
                        resize: true
                    });
                }

                if ($('#morris-donut-chart').length > 0) {
                    Morris.Donut({
                        element: 'morris-donut-chart',
                        data: [{ label: "Mock Tests", value: 40 }, { label: "Certifications", value: 60 }],
                        resize: true
                    });
                }
            }
        },

        initialization: function () {
            mainApp.main_fun();
        }
    };

    // Initializing
    $(document).ready(function () {
        mainApp.main_fun();
    });

}(jQuery));
