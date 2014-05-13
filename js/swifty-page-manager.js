/*

Swifty Page Manager

*/

var $SPMTree,
    SPMOpts,
    $SPMMsg;

var SPM = ( function ( $, document ) {
    var spm = {};
    var $SPMTips;

    spm.init = function () {
        $SPMTips = $( '.spm-tooltip' ).clone().tooltip();

        this.startListeners();
    };

    spm.startListeners = function () {
        $( document ).on( 'mouseenter', '.spm-tooltip-button', spm.openTooltipHandler );
        $( document ).on( 'mouseleave', '.spm-tooltip-button', spm.closeTooltipHandler );
        $( document ).on( 'click', '.spm-tooltip-button', spm.openTooltipHandler );
        $( document ).on( 'click', spm.closeTooltipHandler );

        $( document ).on( 'click', 'a.spm_open_all', function ( /*ev*/ ) {
            $SPMTips.tooltip( 'close' );

            spm.getWrapper( this ).find( '.spm-tree-container' ).jstree( 'open_all' );

            return false;
        } );

        $( document ).on( 'click', 'a.spm_close_all', function ( /*ev*/ ) {
            $SPMTips.tooltip( 'close' );

            spm.getWrapper( this ).find( '.spm-tree-container' ).jstree( 'close_all' );

            return false;
        } );

        $( document ).on( 'click', '.spm-page-tree-element', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );
            var id = $li.attr( 'id' ).replace( '#', '' );

            $SPMTips.tooltip( 'close' );

            spm.resetPageTree();
            spm.preparePageActionButtons( $li );

            $.cookie( 'jstree_select', id );

            return false;
        } );

        $( document ).on( 'keyup', 'input[name=post_title].spm-new-page', function ( ev ) {
            var $li = $( this ).closest( 'li' );
            var isCustomUrl = $li.find( 'input[name=spm_is_custom_url]' ).val();
            var path;

            if ( isCustomUrl && isCustomUrl == '0' ) {
                path = spm.generatePathToPage( $li );

                setTimeout( function () {
                    $.post(
                        ajaxurl,
                        {
                            'action': 'spm_sanitize_url',
                            'url': ev.currentTarget.value
                        }
                    ).done( function ( url ) {
                        $( 'input[name=post_name]' ).val( spm.stripUrl( path + url ) );
                    } );
                }, 100 );
            }

            return false;
        } );

        $( document ).on( 'keyup', 'input[name=post_name]', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );
            var isCustomUrl = $li.find( 'input[name=spm_is_custom_url]' ).val();

            if ( isCustomUrl && isCustomUrl == '0' ) {
                $li.find( 'input[name=spm_is_custom_url]' ).val( '1' );
            }

            return false;
        } );

        $( document ).on( 'change', 'input[name=add_mode].spm-new-page', function ( /*ev*/ ) {
            var $li = $( this ).closest( 'li' );
            var isCustomUrl = $li.find( 'input[name=spm_is_custom_url]' ).val();
            var path, url;

            if ( !spm.validateSettings( $li ) ) {
                return false;
            }

            if ( isCustomUrl && isCustomUrl == '0' ) {
                path = spm.generatePathToPage( $li );
                url = $li.find( 'input[name=post_title]' ).val() !== ''
                    ? $li.find( 'input[name=post_name]' ).val().replace( /.*\/(.+)$/g, '$1' )
                    : '';

                $( 'input[name=post_name]' ).val( spm.stripUrl( path + url ) );
            }

            return false;
        } );

        $( document ).on( 'click', '.spm-page-button', function ( /*ev*/ ) {
            var $button = $( this );
            var $li = $button.closest( 'li' );
            var action = $button.data( 'spm-action' );

            $SPMTips.tooltip( 'close' );

            switch ( action ) {
                case 'add':
                case 'settings':
                case 'delete':
                case 'publish':
                    if ( $li.data( 'cur-action' ) && $li.data( 'cur-action' ) === action ) {
                        spm.resetPageTree();
                    } else {
                        spm.resetPageTree();
                        spm.preparePageActions( $li, action );
                        $li.data( 'cur-action', action );
                    }

                    if ( action === 'settings' ) {
                        spm.getPageSettings( $li );
                    }

                    break;
                case 'edit':
                    window.location = $li.data( 'editlink' );

                    break;
                case 'view':
                    window.location = $li.data( 'permalink' );

                    break;
                default:
                    $( '.spm-container:visible' ).remove();

                    break;
            }

            return false;
        } );

        $( document ).on( 'click', '.spm-do-button', function ( /*ev*/ ) {
            var $button = $( this );
            var $li = $button.closest( 'li' );
            var spmAction = $button.data( 'spm-action' );
            var $inlineEdit = $( 'input#_inline_edit' );

            $SPMTips.tooltip( 'close' );

            switch ( spmAction ) {
                case 'save':
                    var curAction = $li.data( 'cur-action' );
                    var settings;

                    if ( !spm.validateSettings( $li ) ) {
                        return false;
                    }

                    settings = {
                        'action': 'spm_save_page',
                        'post_type': 'page',
                        'post_title': $li.find( 'input[name=post_title]' ).val(),
                        'add_mode': $li.find( 'input[name=add_mode]:checked' ).val(),   // after | inside
                        'post_status': $li.find( 'input[name=post_status]:checked' ).val(),   // draft | publish
                        'page_template': $li.find( 'select[name=page_template]' ).val(),
                        'post_name': $li.find( 'input[name=post_name]' ).val() || $li.find( 'input[name=spm_url]' ).val(),
                        'spm_is_custom_url': $li.find( 'input[name=spm_is_custom_url]' ).val(),
                        'spm_show_in_menu': $li.find( 'input[name=spm_show_in_menu]:checked' ).val() || 'show',   // show | hide
                        'spm_page_title_seo': $li.find( 'input[name=spm_page_title_seo]' ).val(),
                        'spm_header_visibility': $li.find( 'input[name=spm_header_visibility]:checked' ).val(),   // show | hide
                        'spm_sidebar_visibility': $li.find( 'input[name=spm_sidebar_visibility]:checked' ).val(),   // left | right | hide
                        '_inline_edit': $inlineEdit.val()
                    };

                    if ( curAction === 'add' ) {   // Page creation
                        $.extend( true, settings, {
                            'parent_id': $li.attr( 'id' )
                        } );
                    } else if ( curAction === 'settings' ) {   // Page editing
                        $.extend( true, settings, {
                            'post_ID': $li.data( 'post_id' )
                        } );
                    }

                    $.post(
                        ajaxurl,
                        settings
                    ).done( function() {
                        window.location.reload();
                    } );

                    break;
                case 'delete':
                case 'publish':
                    $.post(
                        ajaxurl,
                        {
                            'action': 'spm_' + spmAction + '_page',
                            'post_ID': $li.data( 'post_id' ),
                            '_inline_edit': $inlineEdit.val()
                        }
                    ).done( function() {
                        window.location.reload();
                    } );

                    break;
                case 'cancel':
                    $( '.spm-container:visible' ).remove();

                    break;
                case 'less':
                case 'more':
                    $( '.spm-advanced-feature' )[ spmAction === 'less' ? 'hide' : 'show' ]();
                    $( '.spm-less' )[ spmAction === 'less' ? 'hide' : 'show' ]();
                    $( '.spm-more' )[ spmAction === 'less' ? 'show' : 'hide' ]();

                    break;
                case 'add':
                    $.post(
                        ajaxurl,
                        {
                            'action': 'spm_save_page',
                            'post_type': 'page',
                            'post_title': 'Home',
                            'post_status': 'draft',
                            'add_mode': 'after',
                            'page_template': 'default',
                            'spm_show_in_menu': 'show',
                            '_inline_edit': $inlineEdit.val()
                        },
                        function () {
                            window.location.reload();
                        }
                    );

                    break;
            }

            return false;
        } );
    };

    spm.openTooltipHandler = function ( ev ) {
        var $toolTipBtn = $( this ).hasClass( 'spm-tooltip-button' )
                        ? $( this )
                        : $( this ).closest( '.spm-tooltip-button' );
        var $toolTipClass = '.' + $toolTipBtn.attr( 'rel' );
        var $toolTip = $SPMTips.filter( $toolTipClass );

        if ( ev.type === 'click' && /(spm-tooltip-button|fa)/.test( ev.target.className ) ) {
            $( document ).off( 'mouseenter', '.spm-tooltip-button' );
            $( document ).off( 'mouseleave', '.spm-tooltip-button' );
        }

        $SPMTips.tooltip( 'close' );

        $toolTip.tooltip( {
            items: $toolTip[0],
            content: $toolTip.html(),
            position: { of: $toolTipBtn[0], my: "left bottom", at: "right top", collision: "flipfit" }
        } ).tooltip( 'open' );

        ev.stopImmediatePropagation();

        return false;
    };

    spm.closeTooltipHandler = function ( ev ) {
        if ( ev.type === 'click' && !/(spm-tooltip-button|fa)/.test( ev.target.className ) ) {
            $( document ).on( 'mouseleave', '.spm-tooltip-button', spm.closeTooltipHandler );
            $( document ).on( 'mouseenter', '.spm-tooltip-button', spm.openTooltipHandler );
        }

        $SPMTips.tooltip( 'close' );
    };

    spm.getLocationOrigin = function () {
        if ( !window.location.origin ) {
            return window.location.protocol + "//" +
                   window.location.hostname + ( window.location.port ? ':' + window.location.port : '' );
        }

        return window.location.origin;
    };

    spm.generatePathToPage = function ( $li ) {
        var addMode = $li.find( 'input[name=add_mode]:checked' ).val();
        var path = $li.data( 'permalink' ).replace( this.getLocationOrigin(), '' );
        var $parentLi = '';

        if ( addMode === 'after' ) {
            $parentLi = $li.parent( 'ul' ). closest( 'li' );

            if ( $parentLi.length ) {
                path = $parentLi.data( 'permalink' ).replace( this.getLocationOrigin(), '' );
            } else {
                path = '';
            }
        }

        return path;
    };

    spm.stripUrl = function ( url ) {
        return url.replace( /^\/|\/$/g, '' );
    };

    spm.adaptTreeLinkElements = function ( a ) {
        $( a ).css( {
            width: '100%'
        } ).attr( {
            href: 'javascript:;',
            class: 'spm-page-tree-element'
        } );
    };

    spm.resetPageTree = function () {
        var $tree = $( '.spm-tree-container' );

        $tree.find( 'li' ).data( 'cur-action', '' );
        $tree.find( '.spm-container' ).remove();
        $tree.find( 'a.jstree-clicked' ).removeClass( 'jstree-clicked' );
    };

    spm.validateSettings = function ( $li ) {
        var postStatusParent = $li.data( 'post_status' );
        var addMode = $li.find( 'input[name=add_mode]:checked' ).val();
        var isOk = 1;

        // If status is draft then it's not possible to add sub pages
        if ( postStatusParent === "draft" && addMode === "inside" ) {
            jAlert( spm_l10n.no_sub_page_when_draft );

            $li.find( 'input[name=add_mode]' ).val( [ 'after' ] );

            isOk = 0;
        }

        return isOk;
    };

    spm.getPostType = function ( el ) {
        return this.getWrapper( el ).find( '[name=spm_meta_post_type]' ).val();
    };

    spm.getWrapper = function ( el ) {
        return $( el ).closest( '.spm_wrapper' );
    };

    spm.preparePageActionButtons = function ( $li ) {
        var $a = $li.find( '> a' );
        var isDraft = $a.find( '.post_type_draft' ).length;
        var $tree = $li.closest( '.spm-tree-container' );
        var $tmpl = this.getPageActionButtonsTmpl();

        $tree.find( '.spm-page-actions-tmpl' ).remove();

        if ( !isDraft ) {
            $tmpl.find( '[data-spm-action=publish]' ).hide();
        }

        $a.addClass( 'jstree-clicked' ).append( $tmpl );
    };

    spm.getPageActionButtonsTmpl = function () {
        return $( '.spm-page-actions-tmpl.__TMPL__' ).clone( true ).removeClass( '__TMPL__ spm-hidden' );
    };

    spm.preparePageActions = function ( $li, action ) {
        var self = this;
        var selector = {
            'add': 'spm-page-add-edit-tmpl',
            'settings': 'spm-page-add-edit-tmpl',
            'delete': 'spm-page-delete-tmpl',
            'publish': 'spm-page-publish-tmpl'
        }[ action ];
        var $tmpl = this.getPageActionsTmpl( selector );
        var isSwifty = $tmpl.find( 'input[name=is_swifty]' ).val();

        if ( action === 'add' ) {
            $tmpl.find( 'input[name=spm_show_in_menu][value=show]' ).prop( 'checked', true );

            $tmpl.find( 'input[name=add_mode]' ).each( function() {
                var labelText = $( this ).next().text();

                $( this ).next().text( labelText + ' "' + self.getLiText( $li ) + '"' );
            } );

            $tmpl.find( 'input[name=add_mode]' ).val( [ 'after' ] );
            $tmpl.find( 'input[name=post_status]' ).val( [ 'draft' ] );
            $tmpl.find( 'input[name=post_name]' ).val( '' );

            $tmpl.find( 'input[name=add_mode]' ).addClass( 'spm-new-page' );
            $tmpl.find( 'input[name=post_title]' ).addClass( 'spm-new-page' );
        }

        if ( action === 'settings' ) {
            $tmpl.find( 'input[name=add_mode]' ).closest( '.inline-edit-group' ).hide();
        }


        if ( +isSwifty ) {
            $tmpl.find( '.spm-advanced-feature' ).hide();
        }

        $tmpl.find( '.spm-less' ).hide();
        $tmpl.find( '.spm-more' ).show();

        $li.data( 'cur-action', action );
        $li.find( '> a' ).after( $tmpl.removeClass( 'spm-hidden' ) );
    };

    spm.getLiText = function ( $li ) {
        var liText = '';

        $li.find( '> a' ).contents().filter( function() {
            if ( this.nodeType === 3 ) {
                liText = this.nodeValue;
            }
        } );

        return liText;
    };

    spm.getPageActionsTmpl = function ( selector ) {
        return $( '.' + selector + '.__TMPL__' ).clone( true ).removeClass( '__TMPL__' );
    };

    spm.pageTreeLoaded = function ( ev ) {
        var $container = $( ev.target );
        var cookieVal = $.cookie( 'jstree_select' );

        spm.adaptTreeLinkElements( $container.find( 'a' ) );

        $( 'a.jstree-clicked' ).removeClass( 'jstree-clicked' );

        if ( cookieVal ) {
            if ( !/^\#/.test( cookieVal ) ) {
                cookieVal = '#' + cookieVal;
            }

            spm.preparePageActionButtons( $( cookieVal ) );
            $container.find( cookieVal + ' > a' ).addClass( 'jstree-clicked' );
        } else {
            spm.preparePageActionButtons( $container.find( 'li:first' ) );
            $container.find( 'li:first > a' ).addClass( 'jstree-clicked' );
        }
    };

    spm.getPageSettings = function( $li ) {
        $.post(
            ajaxurl,
            {
                'action': 'spm_post_settings',
                'post_ID': $li.data( 'post_id' )
            }
        );
    };

    spm.setLabel = function( $li, label ) {
        var aFirst = $li.find( 'a:first' );

        aFirst.find( 'ins' ).first().after( label );
    };

    spm.bindCleanNodes = function () {
        $SPMTree.bind( 'move_node.jstree', function ( ev, data ) {
            var $nodeBeingMoved = $( data.rslt.o );
            var $nodeR = $( data.rslt.r );
            var $nodeRef = $( data.rslt.or );
            var nodePosition = data.rslt.p;
            var selectedLang = spm.getWPMLSelectedLang( $nodeBeingMoved );
            var nodeId, refNodeId;

            if ( nodePosition === 'before' ) {
                nodeId = $nodeBeingMoved.attr( 'id' );
                refNodeId = $nodeRef.attr( 'id' );
            } else if ( nodePosition === 'after' ) {
                nodeId = $nodeBeingMoved.attr( 'id' );
                refNodeId = $nodeR.attr( 'id' );
            } else if ( nodePosition === 'inside' || nodePosition === 'last' ) {
                nodePosition = 'inside';
                nodeId = $nodeBeingMoved.attr( 'id' );
                refNodeId = $nodeR.attr( 'id' );
            }

            // Update parent or menu order
            $.post( ajaxurl, {
                'action': 'spm_move_page',
                'node_id': nodeId,
                'ref_node_id': refNodeId,
                'type': nodePosition,
                'icl_post_language': selectedLang
            }, function ( /*data, textStatus*/ ) {
                window.location.reload();
            } );
        } );

        $SPMTree.bind( 'clean_node.jstree', function ( ev, data ) {
            var obj = ( data.rslt.obj );

            if ( obj && obj !== -1 ) {
                obj.each( function ( i, el ) {
                    var $li = $( el );
                    var rel = $li.data( 'rel' );
                    var postStatus = $li.data( 'post_status' );
                    var postStatusToShow = spm_l10n[ 'status_' + postStatus + '_ucase' ];

                    // Check that we haven't added our stuff already
                    if ( $li.data( 'done_spm_clean_node' ) ) {
                        return;
                    } else {
                        $li.data( 'done_spm_clean_node', true );
                    }

                    // Add protection type
                    if ( rel === 'password' ) {
                        spm.setLabel(
                            $li,
                            '<span class="post_protected" title="' + spm_l10n.password_protected_page + '">&nbsp;</span>'
                        );
                    }

                    // Post_status can be any value because of plugins like Edit flow
                    // Check if we have an existing translation for the string, otherwise use the post status directly
                    if ( !postStatusToShow ) {
                        postStatusToShow = postStatus;
                    }

                    if ( postStatus !== 'publish' ) {
                        spm.setLabel(
                            $li,
                            '<span class="post_type post_type_' + postStatus + '">' + postStatusToShow + '</span>'
                        );
                    }

                    if ( $li.hasClass( 'spm-show-page-in-menu-no' ) ) {
                        spm.setLabel(
                            $li,
                            '<span class="page-in-menu">' + spm_l10n.hidden_page + '</span>'
                        );
                    }
                } );
            }
        } );
    };

    return spm;

}( jQuery, document ) );

// Begin onDomReady
jQuery( function ( $ ) {
    SPM.init();

    $SPMTree = $( '.spm-tree-container' );
    $SPMMsg = $( '.spm-message' );

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
        title: 'jstree_spm'
    } );

    SPMOpts = {
        plugins: [ 'themes', 'json_data', 'cookies', 'dnd', 'crrm', 'types', 'ui' ],
        core: {
            'html_titles': true
        },
        'json_data': {
            'ajax': {
                'url': ajaxurl + '?action=spm_get_childs&status=' + $.data( document, 'spm_status' ),
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
                        $SPMMsg.html( '<p>' + spm_l10n.no_pages_found + '</p>' );
                        $SPMMsg.show();
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
        "crrm" : {
            "move" : {
                "check_move" : function ( m ) {
                    var p = this._get_parent( m.o );

                    if ( !p ) {
                        return false;
                    }

                    p = p == -1 ? this.get_container() : p;

                    if ( p === m.np ) {
                        return true;
                    }

                    if ( m.p === "inside" || m.p === "last" ) {
                        return !$( m.cr[0] ).find( '.post_type_draft' ).length;
                    }

                    if ( m.p === "before" || m.p === "after" ) {
                        return true;
                    }

                    if ( p[0] && m.np[0] && p[0] === m.np[0] ) {
                        return true;
                    }

                    return false;
                }
            }
        }
    };

    if ( $SPMTree.length > 0 ) {
        SPM.bindCleanNodes();
    }

    $SPMTree.each( function ( i, el ) {
        var $el = $( el );
        var treeOptionsTmp = $.extend( true, {}, SPMOpts );
        var postType = SPM.getPostType( el );
        var spmJsonData = $.data( document, 'spm_json_data' );

        treeOptionsTmp.json_data.ajax.url = treeOptionsTmp.json_data.ajax.url + '&post_type=' + postType;
        treeOptionsTmp.json_data.data = spmJsonData[ postType ];

        $el.bind( 'loaded.jstree', SPM.pageTreeLoaded );
        $el.jstree( treeOptionsTmp );
    } );
} );  // End onDomReady