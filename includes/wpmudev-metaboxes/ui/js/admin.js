jQuery.validator.addMethod( 'alphanumeric', function( value, element ) {
    return this.optional( element ) || new RegExp( '[a-z0-9]{' + value.length + '}', 'ig' ).test( value );
}, WPMUDEV_Metaboxes_Validation_Messages.alphanumeric_error_msg );

jQuery.validator.addMethod( 'lessthan', function( value, element, param ) {
    var $elm = jQuery( element );
    var $parent = ( $elm.closest( '.wpmudev-subfield-group' ).length > 0 ) ? $elm.closest( '.wpmudev-subfield-group' ) : $elm.closest( '.wpmudev-field' );
    return this.optional( element ) || value <= $parent.find( param ).val();
}, jQuery.validator.format( WPMUDEV_Metaboxes_Validation_Messages.lessthan_error_msg ) );

( function( $ ) {
    $( document ).ready( function() {
        // Preload working indicator
        $( 'body' ).append( '<img class="wpmudev-metabox-working-indicator" style="display:none" src="' + WPMUDEV_Metaboxes.spinner_url + '" alt="" />' );
    } );

    $.fn.isWorking = function( isLoading ) {
        var $spinner = $( '.wpmudev-metabox-working-indicator' );

        return this.each( function() {
            var $this = $( this );

            if ( isLoading ) {
                if ( $this.hasClass( 'working' ) ) {
                    return;
                }

                if ( $this.is( 'input, select, textarea' ) ) {
                    $this.prop( 'disabled', true );
                }

                $this.addClass( 'working' );
                $spinner.insertAfter( $this );
                $spinner.show();
            } else {
                if ( $this.is( 'input, select, textarea' ) ) {
                    $this.prop( 'disabled', false );
                }

                $this.removeClass( 'working' );
                $spinner.hide();
            }
        } );
    };
}( jQuery ) );

( function( $ ) {
    window.onload = function() {
        /* initializing conditional logic here instead of document.ready() to prevent
         issues with wysiwyg editor not getting proper height */
        initConditionals();
        $( '.wpmudev-postbox' ).find( ':checkbox, :radio, select' ).change( initConditionals );
    }

    $( document ).on( 'wpmudev_repeater_field/after_add_field_group', function( e ) {
        initConditionals();
    } );

    jQuery( document ).ready( function( $ ) {
        initValidation();
        initRowShading();
        initToolTips();
        initPostboxAccordions();
    } );

    var initPostboxAccordions = function() {
        $( '#mp-main-form' ).find( '.wpmudev-postbox' ).find( '.hndle, .handlediv' ).click( function() {
            var $this = $( this ),
                $postbox = $this.closest( '.wpmudev-postbox' );

            $postbox.toggleClass( 'closed' );
            $( document ).trigger( 'postbox-toggled', $postbox );

            $.post( ajaxurl, {
                "action": "wpmudev_metabox_save_state",
                "closed": $postbox.hasClass( 'closed' ),
                "id": $postbox.attr( 'id' )
            } );
        } );
    }

    var initToolTips = function() {
        $( '.wpmudev-field' ).on( 'click', '.wpmudev-metabox-tooltip', function() {
            var $this = $( this ),
                $button = $this.find( '.wpmudev-metabox-tooltip-button' );

            if ( $button.length == 0 ) {
                $this.children( 'span' ).append( '<a class="wpmudev-metabox-tooltip-button" href="#">x</a>' );
            }

            $this.children( 'span' ).css( 'display', 'block' ).position( {
                my: "left center",
                at: "right center",
                of: $this,
                using: function( pos, feedback ) {
                    $( this ).css( pos ).removeClass( 'right left' ).addClass( feedback.horizontal );
                }
            } );
        } );

        $( '.wpmudev-field' ).on( 'click', '.wpmudev-metabox-tooltip-button', function( e ) {
            e.preventDefault();
            e.stopPropagation();
            $( this ).parent().fadeOut( 250 );
        } );
    }

    var initRowShading = function() {
        $( '.wpmudev-postbox' ).each( function() {
            var $rows = $( this ).find( '.wpmudev-field:visible' );
            $rows.filter( ':odd' ).addClass( 'shaded' );
            $rows.filter( ':even' ).removeClass( 'shaded' );
        } );

        $( '.wpmudev-field-section' ).each( function() {
            var $this = $( this ),
                shaded = $this.hasClass( 'shaded' ) ? true : false;

            if ( shaded ) {
                $this.nextUntil( '.wpmudev-field-section' ).addClass( 'shaded' );
            } else {
                $this.nextUntil( '.wpmudev-field-section' ).removeClass( 'shaded' );
            }
        } )
    }

    var testConditionals = function( conditionals, $obj ) {
        var numValids = 0;

        $.each( conditionals, function( i, conditional ) {
            if ( conditional.name.indexOf( '[' ) >= 0 && $obj.closest( '.wpmudev-subfield-group' ).length ) {
                var nameParts = conditional.name.split( '[' );
                var $input = $obj.closest( '.wpmudev-subfield-group' ).find( '[name^="' + nameParts[0] + '"][name*="[' + nameParts[1].replace( ']', '' ) + ']"]' );
            } else {
                var $input = $( '[name="' + conditional.name + '"]' );
            }

            if ( !$input.is( ':radio' ) && !$input.is( ':checkbox' ) && !$input.is( 'select' ) ) {
                // Conditional logic only works for radios, checkboxes and select dropdowns
                return;
            }

            var val = getInputValue( $input );

            if ( $.inArray( val, conditional.value ) >= 0 ) {
                numValids++;
            }
        } );

        return numValids;
    };

    var parseConditionals = function( elm ) {
        var conditionals = [ ];
        $.each( elm.attributes, function( i, attrib ) {
            if ( attrib.name.indexOf( 'data-conditional-name' ) >= 0 ) {
                var index = attrib.name.replace( 'data-conditional-name-', '' );

                if ( conditionals[index] === undefined ) {
                    conditionals[index] = { };
                }

                conditionals[index]['name'] = attrib.value;
            }

            if ( attrib.name.indexOf( 'data-conditional-value' ) >= 0 ) {
                var index = attrib.name.replace( 'data-conditional-value-', '' );

                if ( conditionals[index] === undefined ) {
                    conditionals[index] = { };
                }

                conditionals[index]['value'] = attrib.value.split( '||' );
            }
        } );

        return conditionals;
    };

    var getInputValue = function( $input ) {
        if ( $input.is( 'select' ) ) {
            var val = $input.val();
        }

        if ( $input.is( ':checkbox' ) ) {
            var val = ( $input.prop( 'checked' ) ) ? $input.val() : "-1";
        }

        if ( $input.is( ':radio' ) ) {
            var val = $input.filter( ':checked' ).val();
        }

        return val;
    }

    var initConditionals = function() {
        $( '.wpmudev-field-has-conditional, .wpmudev-metabox-has-conditional' ).each( function() {
            var $this = $( this ),
                operator = $this.attr( 'data-conditional-operator' ),
                action = $this.attr( 'data-conditional-action' ),
                numValids = 0;

            if ( operator === undefined || action === undefined ) {
                // Skip elements that don't have conditional attributes defined
                return;
            }

            operator = operator.toUpperCase();
            action = action.toLowerCase();

            var conditionals = parseConditionals( this );

            if ( $this.hasClass( 'wpmudev-metabox-has-conditional' ) ) {
                $container = $this;
            } else {
                $container = ( $this.closest( '.wpmudev-subfield' ).length ) ? $this.closest( '.wpmudev-subfield' ) : $this.closest( '.wpmudev-field' )
            }

            if ( action == 'show' ) {
                if ( operator == 'AND' ) {
                    if ( testConditionals( conditionals, $this ) != conditionals.length ) {
                        hideContainer( $container );
                    } else {
                        showContainer( $container );
                    }
                } else {
                    if ( testConditionals( conditionals, $this ) == 0 ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                }
            }

            if ( action == 'hide' ) {
                if ( operator == 'AND' ) {
                    if ( testConditionals( conditionals, $this ) == conditionals.length ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                } else {
                    if ( testConditionals( conditionals, $this ) > 0 ) {
                        $container.hide().next( 'p.submit' ).hide();
                    } else {
                        $container.fadeIn( 500 ).next( 'p.submit' ).fadeIn( 500 )
                    }
                }
            }

            initRowShading();
        } );


        $( '.meta-box-sortables.store-settings_page_store-settings-payments' ).fadeTo( 0, 100 );

    };

    var hideContainer = function( $container ) {
        /**
         * Triggers right before a field container is hidden
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object to be hidden.
         */
        $( document ).trigger( 'wpmudev_metaboxes/before_hide_field_container', [ $container ] );

        $container.hide().next( 'p.submit' ).hide();

        /**
         * Triggers right after a field container is hidden
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object that was hidden.
         */
        $( document ).trigger( 'wpmudev_metaboxes/after_hide_field_container', [ $container ] );

    };

    var showContainer = function( $container ) {
        /**
         * Triggers right before a field container is show
         *
         * @since 3.0
         * @access public
         * @param jQuery $container The jQuery object to be shown.
         */
        $( document ).trigger( 'wpmudev_metaboxes/before_show_field_container', [ $container ] );

        $container.fadeIn( 500, function() {
            /**
             * Triggers right after a field container is fully shown
             *
             * @since 3.0
             * @access public
             * @param jQuery $container The jQuery object that was shown.
             */
            $( document ).trigger( 'wpmudev_metaboxes/after_show_field', [ $container ] );
        } ).next( 'p.submit' ).fadeIn( 500 )
    };

    var initValidation = function() {
        var $form = $( "form#post, form#mp-main-form, form.bulk-form" );

        $form.find( '[data-custom-validation]' ).each( function() {
            var $this = $( this );
            var atts = this.attributes;
            var rule = { };

            $.each( atts, function( index, attr ) {
                if ( attr.name.indexOf( 'data-rule-custom-' ) >= 0 ) {
                    rule.name = attr.name.replace( 'data-rule-custom-', '' );
                    rule.val = attr.value;
                }
            } );

            rule.message = $this.attr( 'data-msg-' + rule.name );

            $.validator.addMethod( ruleName, function( value, element, params ) {
                return this.optional( element ) || new RegExp( rule.val + '{' + value.length + '}', 'ig' ).test( value );
            }, rule.message );
        } );

        //initialize the form validation		
        var validator = $form.validate( {
            errorPlacement: function( error, element ) {
                error.appendTo( element.parent() );
            },
            focusInvalid: false,
            highlight: function( element, errorClass ) {
                var $elm = $( element );
                var $tabWrap = $elm.closest( '.wpmudev-field-tab-wrap' );

                if ( $tabWrap.length > 0 ) {
                    var slug = $tabWrap.attr( 'data-slug' );
                    var $tabWrapParent = $elm.closest( '.wpmudev-subfield-group, .wpmudev-fields' );
                    var $tabLink = $tabWrapParent.find( '.wpmudev-field-tab-label-link' ).filter( '[href="#' + slug + '"]' );
                    $tabLink.addClass( 'has-error' );
                }
            },
            unhighlight: function( element, errorClass, validClass ) {
                var $elm = $( element );
                var $tabWrap = $elm.closest( '.wpmudev-field-tab-wrap' );

                if ( $tabWrap.length > 0 ) {
                    if ( $tabWrap.find( 'label.error' ).filter( ':visible' ).length > 0 ) {
                        // There are other errors in this tab group - bail
                        return;
                    }

                    var slug = $tabWrap.attr( 'data-slug' );
                    var $tabWrapParent = $elm.closest( '.wpmudev-subfield-group, .wpmudev-fields' );
                    var $tabLink = $tabWrapParent.find( '.wpmudev-field-tab-label-link' ).filter( '[href="#' + slug + '"]' );
                    $tabLink.removeClass( 'has-error' );
                }
            },
            ignore: function( index, element ) {
                var $elm = $( element );
                // ignore all elements that are hidden or disabled
                return ( $elm.is( ':hidden' ) || $elm.prop( 'disabled' ) );
            },
            wrapper: "div"
        } );

        $form.on( 'invalid-form.validate', function() {
            var errorCount = validator.numberOfInvalids();
            var msg = WPMUDEV_Metaboxes.form_error_msg;

            if ( errorCount == 1 ) {
                msg = msg.replace( /%s1/g, errorCount + ' ' + WPMUDEV_Metaboxes.error ).replace( /%s2/g, WPMUDEV_Metaboxes.has );
            } else {
                msg = msg.replace( /%s1/g, errorCount + ' ' + WPMUDEV_Metaboxes.errors ).replace( /%s2/g, WPMUDEV_Metaboxes.have );
            }

            alert( msg );
        } );

        $form.find( '#publish, #save-post,.save-bulk-form, [type="submit"]' ).click( function( e ) {
            if ( !$form.valid() ) {
                e.preventDefault();
            }
        } );
    }

}( jQuery ) );