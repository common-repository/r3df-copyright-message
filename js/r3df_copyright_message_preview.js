( function( $ ) {
    wp.customize( 'r3df_copyright_message[use_custom]', function( value ) {
        value.bind( function( newval ) {
            if ( 'custom' == newval ) {
                parent.wp.customize.control('r3df_copyright_message[custom_message]').activate();
                $( '#r3df-copyright-message' ).html( wp.customize.instance('r3df_copyright_message[custom_message]').get() );
            } else {
                parent.wp.customize.control('r3df_copyright_message[custom_message]').deactivate();
                $( '#r3df-copyright-message' ).html( r3df_copyright_message.default );
            }
        } );
    } );

    wp.customize( 'r3df_copyright_message[custom_message]', function( value ) {
        value.bind( function( newval ) {
            $( '#r3df-copyright-message' ).html( newval );
        } );
    } );

    wp.customize( 'r3df_copyright_message[location]', function( value ) {
        value.bind( function( newval, oldval) {
            if ( 'other' != newval ) {
                $( '#r3df-copyright-message' ).insertBefore( $( '.r3df-cm-marker[data-action='+newval+']') );
                $( 'body' ).addClass( 'r3df-cm-l-'+newval ).removeClass( 'r3df-cm-l-'+oldval );
            } //else {
               // use -- parent.wp.customize.control('r3df_copyright_message[other_hook]').get();
            //}
        } );
    } );

} )( jQuery );
