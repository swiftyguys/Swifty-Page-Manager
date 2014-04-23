/*

SwiftyPages

*/

var $swiftyPagesTree,
    swiftyPagesTreeOptions,
    $swiftyPagesMessage;

var SwiftyPages = ( function ( $, document, undefined ) {
    var ss = {};

    ss.init = function () {
        this.startListeners();
    };

    ss.startListeners = function () {
        $( document ).on( 'click', 'a.swiftypages_view_all', function ( /*ev*/ ) {
            ss.setView( 'all', this );

            return false;
        } );

        $( document ).on( 'click', 'a.swiftypages_view_public', function ( /*ev*/ ) {
            ss.setView( 'public', this );

            return false;
        } );

        $( document ).on( 'click', 'a.swiftypages_open_all', function ( /*ev*/ ) {
            ss.getWrapper( this ).find( '.swiftypages_container' ).jstree( 'open_all' );

            return false;
        } );

        $( document ).on( 'click', 'a.swiftypages_close_all', function ( /*ev*/ ) {
            ss.getWrapper( this ).find( '.swiftypages_container' ).jstree( 'close_all' );

            return false;
        } );

        $( document ).on( 'click', 'a.swiftypages_switch_lang', function ( /*ev*/ ) {
            var $wrapper = ss.getWrapper( this );

            // Mark clicked link as selected
            $wrapper.find( 'ul.swiftypages_switch_langs a' ).removeClass( 'current' );
            $( this ).addClass( 'current' );

            // Determine selected language, based on classes on the link
            var re = /swiftypages_switch_language_code_([\w-]+)/;
            var matches = re.exec( $( this ).attr( 'class' ) );
            var langCode = matches[1];

            // Add selected lang to hidden input
            $wrapper.find( '[name=swiftypages_meta_wpml_language]' ).val( langCode );

            // Update post count
            // Post counts are stored on the links for all | public | trash
            var $ulSelectView = $wrapper.find( '.swiftypages-subsubsub-select-view' );

            $ulSelectView.find( 'li.swiftypages_view_is_status_view a' ).each( function ( i, aTag ) {
                // Check if this link has a data attr with count for the selected lang
                var $a = $( aTag );
                var linkCount = $a.data( 'post-count-' + langCode );

                if ( 'undefined' === typeof( linkCount ) ) {
                    linkCount = 0;
                }

                $a.find( '.count' ).text( '(' + linkCount + ')' );
            } );

            // Set the view = reload the tree
            ss.setView( ss.getCurrentView( this ), this );

            return false;
        } );

        $( document ).on( 'click', '.ss-page-tree-element', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );

            ss.resetPageTree();
            ss.preparePageActionButtons( $li );

            return false;
        } );

        $( document ).on( 'click', '.ss-page-button', function ( /*ev*/ ) {
            var $button = $( this );
            var $li = $button.closest( 'li' );
            var action = $button.data( 'ss-action' );

            switch ( action ) {
                case 'add':
                case 'settings':
                case 'delete':
                    if ( $li.data( 'cur-action' ) && $li.data( 'cur-action' ) === action ) {
                        ss.resetPageTree();
                    } else {
                        $li.find( 'span.ss-container' ).remove();
                        ss.preparePageActions( $li, action );
                        $li.data( 'cur-action', action );
                    }

                    if ( action === 'settings' ) {
                        $.post(
                            ajaxurl,
                            {
                                'action': 'swiftypages_post_settings',
                                'post_ID': $li.data( 'post_id' )
                            }
                        );
                    }

                    break;
                case 'edit':
                    document.location = $li.data( 'editlink' );

                    break;
                case 'view':
                    document.location = $li.data( 'permalink' );

                    break;
                case 'publish':
                    $.post(
                        ajaxurl,
                        {
                            'action': 'swiftypages_publish_page',
                            'post_ID': $li.data( 'post_id' ),
                            '_inline_edit': $( 'input#_inline_edit' ).val()
                        }
                    ).done( function() {
                        location.reload();
                    } );

                    break;
                default:
                    $( '.ss-container:visible' ).remove();

                    break;
            }

            return false;
        } );

        $( document ).on( 'click', '.save.ss-button', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );
            var action = $li.data( 'cur-action' );
            var settings = {
                'action': 'swiftypages_save_page',
                'post_type': 'page',
                'post_title': $li.find( 'input[name=post_title]' ).val(),
                'post_name': $li.find( 'input[name=post_name]' ).val(),
                'add_mode': $li.find( 'input[name=add_mode]:checked' ).val(),   // after | inside
                'post_status': $li.find( 'input[name=post_status]:checked' ).val(),   // draft | publish
                'ss_show_in_menu': $li.find( 'input[name=ss_show_in_menu]:checked' ).val() || 'show',   // show | hide
                'ss_page_title_seo': $li.find( 'input[name=ss_page_title_seo]' ).val(),
                'ss_header_visibility': $li.find( 'input[name=ss_header_visibility]:checked' ).val(),   // show | hide
                'ss_sidebar_visibility': $li.find( 'input[name=ss_sidebar_visibility]:checked' ).val(),   // left | right | hide
                '_inline_edit': $( 'input#_inline_edit' ).val()
            };

            if ( action === 'add' ) {   // Page creation
                $.extend( true, settings, {
                    'parent_id': $li.attr( 'id' )
                } );
            } else if ( action === 'settings' ) {   // Page editing
                $.extend( true, settings, {
                    'post_ID': $li.data( 'post_id' )
                } );
            }

            $.post(
                ajaxurl,
                settings
            ).done( function() {
                location.reload();
            } );

            return false;
        } );

        $( document ).on( 'click', '.delete.ss-button', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );

            $.post(
                ajaxurl,
                {
                    'action': 'swiftypages_delete_page',
                    'post_ID': $li.data( 'post_id' ),
                    '_inline_edit': $( 'input#_inline_edit' ).val()
                }
            ).done( function() {
                location.reload();
            } );

            return false;
        } );

        $( document ).on( 'click', '.cancel.ss-button', function ( /*ev*/ ) {
            $( '.ss-container:visible' ).remove();

            return false;
        } );

        $( document ).on( 'click', '.more.ss-button', function ( /*ev*/ ) {
            $( '.ss-advanced-container' ).show();
            $( '.ss-less' ).show();
            $( '.ss-more' ).hide();

            return false;
        } );

        $( document ).on( 'click', '.less.ss-button', function ( /*ev*/ ) {
            $( '.ss-advanced-container' ).hide();
            $( '.ss-less' ).hide();
            $( '.ss-more' ).show();

            return false;
        } );

        $( document ).on( 'click', '.ss-noposts-add', function( /*ev*/ ) {
            $.post(
                ajaxurl,
                {
                    'action': 'swiftypages_save_page',
                    'post_type': 'page',
                    'post_title': 'Home',
                    'add_mode': 'after',
                    '_inline_edit': $( 'input#_inline_edit' ).val()
                },
                function () {
                    window.location.reload();
                }
            );

            return false;
        } );
    };

    ss.adaptTreeLinkElements = function ( a ) {
        $( a ).css( {
            width: '100%'
        } ).attr( {
            href: 'javascript:;',
            class: 'ss-page-tree-element'
        } );
    };

    ss.resetPageTree = function () {
        var $tree = $( '.swiftypages_container' );

        $tree.find( 'li' ).data( 'cur-action', '' );
        $tree.find( '.ss-container' ).remove();
        $tree.find( 'a.jstree-clicked' ).removeClass( 'jstree-clicked' );
    };

    ss.getPostType = function ( el ) {
        return this.getWrapper( el ).find( '[name=swiftypages_meta_post_type]' ).val();
    };

    ss.getWPMLSelectedLang = function ( el ) {
        return this.getWrapper( el ).find( '[name=swiftypages_meta_wpml_language]' ).val();
    };

    ss.getWrapper = function ( el ) {
        return $( el ).closest( '.swiftypages_wrapper' );
    };

    ss.getCurrentView = function ( el ) {
        var $wrapper = this.getWrapper( el );

        if ( $wrapper.find( '.swiftypages_view_all' ).hasClass( 'current' ) ) {
            return 'all';
        } else if ( $wrapper.find( '.swiftypages_view_public' ).hasClass( 'current' ) ) {
            return 'public';
        } else {
            return false; // like unknown
        }
    };

    ss.setView = function( view, el ) {
        var $wrapper = $( el ).closest( '.swiftypages_wrapper' );
        var treeOptionsTmp = $.extend( true, {}, swiftyPagesTreeOptions );

        $swiftyPagesMessage.hide();

        $wrapper.find( '.swiftypages_view_all, .swiftypages_view_public, .swiftypages_view_trash' ).removeClass( 'current' );
        $wrapper.find( '.swiftypages_container' ).jstree( 'destroy' ).html( '' );

        SwiftyPages.bindCleanNodes();

        // Mark selected link
        if ( view === 'all' ) {
            $wrapper.find( '.swiftypages_view_all' ).addClass( 'current' );
        } else if ( view === 'public' ) {
            $wrapper.find( '.swiftypages_view_public' ).addClass( 'current' );
        } else if ( view === 'trash' ) {
            $wrapper.find( '.swiftypages_view_trash' ).addClass( 'current' );
        } else {

        }

        treeOptionsTmp.json_data.ajax.url = ajaxurl + '?action=swiftypages_get_childs'
                                                    + '&view=' + view +
                                                    + '&post_type=' + SwiftyPages.getPostType( el )
                                                    + '&lang=' + SwiftyPages.getWPMLSelectedLang( el );

        // Reload tree
        $wrapper.find( '.swiftypages_container' ).bind( 'loaded.jstree open_node.jstree', SwiftyPages.pageTreeLoaded );
        $wrapper.find( '.swiftypages_container' ).jstree( treeOptionsTmp );
    };

    ss.preparePageActionButtons = function ( $li ) {
        var $a = $li.find( '> a' );
        var isDraft = $a.find( '.post_type_draft' ).length;
        var $tree = $li.closest( '.swiftypages_container' );
        var $tmpl = this.getPageActionButtonsTmpl();

        $tree.find( '.ss-page-actions-tmpl' ).remove();

        if ( !isDraft ) {
            $tmpl.find( '[data-ss-action=publish]' ).hide();
        }

        $a.addClass( 'jstree-clicked' ).append( $tmpl );
    };

    ss.getPageActionButtonsTmpl = function () {
        return $( '.ss-page-actions-tmpl.__TMPL__' ).clone( true ).removeClass( '__TMPL__ ss-hidden' );
    };

    ss.preparePageActions = function ( $li, action ) {
        var selector = {
            'add': 'ss-page-add-edit-tmpl',
            'settings': 'ss-page-add-edit-tmpl',
            'delete': 'ss-page-delete-tmpl',
            'edit': 'ss-page-edit-tmpl'
        }[ action ];
        var $tmpl = this.getPageActionsTmpl( selector );

        if ( action === 'add' ) {
            $tmpl.find( 'input[name=ss_show_in_menu][value=show]' ).prop( 'checked', true );
        }

        if ( action === 'settings' ) {
            $tmpl.find( '.ss-label:eq(2)' ).hide();
        }

        $tmpl.find( '.ss-advanced-container' ).hide();
        $tmpl.find( '.ss-less' ).hide();
        $tmpl.find( '.ss-more' ).show();

        $li.data( 'cur-action', action );
        $li.find( '> a' ).after( $tmpl.removeClass( 'ss-hidden' ) );
    };

    ss.getPageActionsTmpl = function ( selector ) {
        return $( '.' + selector + '.__TMPL__' ).clone( true ).removeClass( '__TMPL__' );
    };

    ss.pageTreeLoaded = function ( ev ) {
        var $container = $( ev.target );

        ss.adaptTreeLinkElements( $container.find( 'a' ) );
        ss.preparePageActionButtons( $container.find( 'li:first' ) );

        setTimeout( function () {
            $( 'a.jstree-clicked' ).removeClass( 'jstree-clicked' );
            $container.find( 'li:first > a' ).addClass( 'jstree-clicked' );
        }, 1 );
    };

    ss.getPageSettings = function( $li ) {
        $.post(
            ajaxurl,
            {
                'action': 'swiftypages_post_settings',
                'post_ID': $li.data( 'post_id' )
            }
        );
    };

    ss.bindCleanNodes = function () {
        $swiftyPagesTree.bind( 'move_node.jstree', function ( ev, data ) {
            var nodeBeingMoved = $( data.rslt.o );
            var nodeR = $( data.rslt.r );
            var nodeRef = $( data.rslt.or );
            var nodePosition = data.rslt.p;
            var selectedLang = ss.getWPMLSelectedLang( nodeBeingMoved );
            var nodeId, refNodeId;

            if ( nodePosition === 'before' ) {
                nodeId = nodeBeingMoved.attr( 'id' );
                refNodeId = nodeRef.attr( 'id' );
            } else if ( nodePosition === 'after' ) {
                nodeId = nodeBeingMoved.attr( 'id' );
                refNodeId = nodeR.attr( 'id' );
            } else if ( nodePosition === 'inside' ) {
                nodeId = nodeBeingMoved.attr( 'id' );
                refNodeId = nodeR.attr( 'id' );
            }

            // Update parent or menu order
            $.post( ajaxurl, {
                'action': 'swiftypages_move_page',
                'node_id': nodeId,
                'ref_node_id': refNodeId,
                'type': nodePosition,
                'icl_post_language': selectedLang
            }, function ( /*data, textStatus*/ ) {
                if ( nodePosition === 'inside' && nodeR.hasClass( 'swiftypages_show_page_in_menu_no' ) ) {
                    nodeBeingMoved.removeClass( 'swiftypages_show_page_in_menu_yes' )
                                  .addClass( 'swiftypages_show_page_in_menu_no' );
                }
            } );
        } );

        $swiftyPagesTree.bind( 'clean_node.jstree', function ( ev, data ) {
            var obj = ( data.rslt.obj );

            if ( obj && obj !== -1 ) {
                obj.each( function ( i, el ) {
                    var $li = $( el );
                    var aFirst = $li.find( 'a:first' );
                    var rel = $li.data( 'rel' );
                    var postStatus = $li.data( 'post_status' );
                    var postStatusToShow = swiftypages_l10n[ 'status_' + postStatus + '_ucase' ];

                    // Check that we haven't added our stuff already
                    if ( $li.data( 'done_swiftypages_clean_node' ) ) {
                        return;
                    } else {
                        $li.data( 'done_swiftypages_clean_node', true );
                    }

                    // Add protection type
                    if ( rel === 'password' ) {
                        aFirst.find( 'ins' ).after(
                            '<span class="post_protected" title="' + swiftypages_l10n.password_protected_page + '">&nbsp;</span>'
                        );
                    }

                    // Post_status can be any value because of plugins like Edit flow
                    // Check if we have an existing translation for the string, otherwise use the post status directly
                    if ( !postStatusToShow ) {
                        postStatusToShow = postStatus;
                    }

                    if ( postStatus !== 'publish' ) {
                        aFirst.find( 'ins' ).first().after(
                            '<span class="post_type post_type_' + postStatus + '">' + postStatusToShow + '</span>'
                        );
                    }
                } );
            }
        } );
    };

    return ss;

}( jQuery, document ) );


// Begin onDomReady
jQuery( function ( $ ) {
    SwiftyPages.init();

    $swiftyPagesTree = $( '.swiftypages_container' );
    $swiftyPagesMessage = $( '.swiftypages_message' );

    // Override css
    var height = '36';
    var height2 = '34';
    var ins_height = '36';
    var css = '' +
        '.jstree ul, .jstree li { display:block; margin:0 0 0 0; padding:0 0 0 0; list-style-type:none; } ' +
        '.jstree li { display:block; min-height:' + height + 'px; line-height:' + height + 'px; white-space:nowrap; margin-left:18px; min-width:18px; } ' +
        '.jstree-rtl li { margin-left:0; margin-right:18px; } ' +
        '.jstree > ul > li { margin-left:0px; } ' +
        '.jstree-rtl > ul > li { margin-right:0px; } ' +
        '.jstree ins { display:inline-block; text-decoration:none; width:18px; height:' + height + 'px; margin:0 0 0 0; padding:0; } ' +
        '.jstree a { display:inline-block; line-height:' + height2 + 'px; height:' + height2 + 'px; color:black; white-space:nowrap; text-decoration:none; padding:1px 2px; margin:0; } ' +
        '.jstree a:focus { outline: none; } ' +
        '.jstree a > ins { height:' + ins_height + 'px; width:16px; } ' +
        '.jstree a > .jstree-icon { margin-right:3px; } ' +
        '.jstree-rtl a > .jstree-icon { margin-left:3px; margin-right:0; } ' +
        'li.jstree-open > ul { display:block; } ' +
        'li.jstree-closed > ul { display:none; } ' +
        '#vakata-dragged { background-color: white; };' +
        '';

    $.vakata.css.add_sheet( {
        str: css,
        title: 'jstree_swiftypages'
    } );

    swiftyPagesTreeOptions = {
        plugins: [ 'themes', 'json_data', 'cookies', 'dnd', 'types', 'ui' ],
        core: {
            'html_titles': true
        },
        'json_data': {
            'ajax': {
                'url': ajaxurl + '?action=swiftypages_get_childs&view=' + $.data( document, 'swiftypages_view' ),
                // this function is executed in the instance's scope (this refers to the tree instance)
                // the parameter is the node being loaded (may be -1, 0, or undefined when loading the root nodes)
                'data': function ( n ) {
                    // the result is fed to the AJAX request `data` option
                    if ( n.data ) {
                        return {
                            'id': n.data( 'post_id' )
                        };
                    }
                },
                'success': function ( data /*, status*/ ) {
                    // If data is null or empty = show message about no nodes
                    if ( data === null || !data ) {
                        $swiftyPagesMessage.html( '<p>' + swiftypages_l10n.no_pages_found + '</p>' );
                        $swiftyPagesMessage.show();
                    }
                },
                'error': function ( data, status ) {
                }
            }
        },
        'themes': {
            'theme': 'wordpress',
            'dots': true,
            'icons': true
        },
        'dnd': {
        }
    };

    if ( $swiftyPagesTree.length > 0 ) {
        SwiftyPages.bindCleanNodes();
    }

    $swiftyPagesTree.each( function ( i, el ) {
        var $el = $( el );
        var treeOptionsTmp = $.extend( true, {}, swiftyPagesTreeOptions );
        var postType = SwiftyPages.getPostType( el );
        var swiftypages_jsondata = $.data( document, 'swiftypages_jsondata' );

        treeOptionsTmp.json_data.ajax.url = treeOptionsTmp.json_data.ajax.url + '&post_type=' + postType + '&lang=' + SwiftyPages.getWPMLSelectedLang( el );
        treeOptionsTmp.json_data.data = swiftypages_jsondata[ postType ];

        $el.bind( 'loaded.jstree', SwiftyPages.pageTreeLoaded );
        $el.jstree( treeOptionsTmp );
    } );
} );  // End onDomReady
