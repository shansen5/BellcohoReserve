// Ensure the documents is ready before executing scripts
jQuery(function($) {
    
    var processFile = "reports.inc.php";
    
    var fx = {
        // Checks for a modal window and returns it, or else
        // creates a new one and returns that
        "initModal" : function() {
            // If no elements are matched, the length
            // property will return 0
            if ($(".modal-window").length == 0 ) {
                // Creates a div, adds a class and appends it
                // to the body tag
                return $("<div>")
                    .hide()
                    .addClass("modal-window")
                    .appendTo("body");
            } else {
                //Returns existing modal window
                return $(".modal-window");
            }
        },
        
        "boxout" : function( event ) {
            if ( event != undefined ) {
                event.preventDefault();
            }
            
            $( "a" ).removeClass( "active" );
            
            $( ".modal-window, .modal-overlay" )
                .fadeOut( "slow", function() {
                    $( this ).remove();
                })
        },
        
        "boxin" : function( data, modal ) {
            $( "<div>" )
                .hide()
                .addClass( "modal-overlay" )
                .click( function( event ) {
                    fx.boxout( event );
                })
                .appendTo( "body" );
                
                modal
                    .hide()
                    .append( data )
                    .appendTo( "body" );
                    
                $( ".modal-window, .modal-overlay" )
                    .fadeIn( "slow" );
                
        }
    };
    
    
    $("#unitreserverpt").ajaxForm({
        success: function( ) {
            $(this).addClass("active");
            var unit_id = $("#unit_id").val();
            var from_date = $("#fromDate").val();
            var to_date = $("#toDate").val();
            
            var result;
            $.post( "reports.inc.php", 
                   { report: 'unitreserverpt',
                     unit: $("#unit_id").val(), 
                     from: $("#fromDate").val(), 
                     to: $("#toDate").val() 
                   },
                 function( data ) {
                     result = data;
                     modal = fx.initModal();
                     fx.boxin( result, modal );
            
                     $("<a>")
                          .attr( "href", "#" )
                          .addClass( "modal-close-btn" )
                          .html( "&times;" )
                          .click( function( event ) {
                              event.preventDefault();
                              $( ".modal-window" )
                                  fx.boxout( event );
                          })
                         .appendTo( modal );
                 });
            // var data = unit_id + ", " + from_date + ", " + to_date ;
        },
        error: function( msg ) {
            modal.append( msg );
        }
    });
});


