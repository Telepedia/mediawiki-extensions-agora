const { TOOLBAR_CONFIG } = require( './config.js' );

// first draft; this is adapted from VEForAll and "Yappin" from Weird Gloop
( function ( mw, OO, ve ) {
    'use strict';

    mw.agora.ve.Target = function ( node, html ) {
        let config = {};
        config.toolbarConfig = {};
        config.toolbarConfig.actions = false;
        this.toolbarPosition = 'bottom';
        this.$node = node;
        mw.agora.ve.Target.parent.call( this, config );
        this.init( html );
    };

    OO.inheritClass( mw.agora.ve.Target, ve.init.mw.Target );

    mw.agora.ve.Target.prototype.init = function ( html ) {
        this.createWithContent( html );
    }

    mw.agora.ve.Target.static.name = 'agora';

    mw.agora.ve.Target.static.toolbarGroups = ( function () {
        return TOOLBAR_CONFIG;
    }() );

    mw.agora.ve.Target.prototype.createWithContent = function ( html ) {
        const target = this;

        this.addSurface(
            ve.dm.converter.getModelFromDom(
                ve.createDocumentFromHtml( html )
            )
        );

        $( this.$node ).before( this.$element );

        $( this.$node ).hide()
            .removeClass( 'oo-ui-texture-pending' ).prop( 'disabled', false );

        this.setDir();

        target.once( 'surfaceReady', function () {
          target.getSurface().getView().focus()
        } );

        target.getToolbar().onWindowResize();
        target.onToolbarResize();
        target.onContainerScroll();

        target.emit( 'editor-ready' );
    };

    mw.agora.ve.Target.prototype.getPageName = function () {
        return mw.config.get( 'wgPageName' );
    }

    mw.agora.ve.Target.prototype.setDir = function () {
        const view = this.surface.getView();
        const dir = $( 'body' ).is( '.rtl' ) ? 'rtl' : 'ltr';

        if ( view ) {
            view.getDocument().setDir( dir );
        }
    }

    ve.init.mw.targetFactory.register( mw.agora.ve.Target );
}( mediaWiki, OO, ve ) );