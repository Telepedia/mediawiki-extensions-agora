( function ( $, mw, OO, ve ) {
    'use strict';

    mw.agora.ve.Editor = function ( $node, content ) {
        let modules = null;

        OO.EventEmitter.call( this );
        this.$node = $( $node );

        modules = mw.config.get( 'wgVisualEditorConfig' ).pluginModules.filter( mw.loader.getState );

        mw.loader.using( modules, this.init.bind( this, content || '' ) );
    };

    OO.mixinClass( mw.agora.ve.Editor, OO.EventEmitter );

    mw.agora.ve.Editor.prototype.initCallbacks = [];

    mw.agora.ve.Editor.prototype.createTarget = function () {
        let self = this, $wrapperNode, maxHeight;

        this.target = new mw.agora.ve.Target( this.$node, $( this.$node ).val() );

        return this.target;
    };

    mw.agora.ve.Editor.prototype.init = function ( content ) {
        this.target = this.createTarget();

        $.each( this.initCallbacks, function ( k, callback ) {
            callback.apply( this );
        }.bind( this ) );
    };

    mw.agora.ve.Editor.prototype.destroy = function () {
        if ( this.target ) {
            this.target.destroy();
        }

        this.$node.show();
    };

    mw.agora.ve.Editor.prototype.getRawContent = function () {
        let doc, html;

        if ( !this.target ) {
            return '';
        }

        doc = ve.dm.converter.getDomFromModel( this.dmDoc );

        html = ve.properInnerHtml( $( doc.documentElement ).find( 'body' )[ 0 ] );
        return html;
    };

    mw.agora.ve.Editor.static.format = 'html';
    mw.agora.ve.Editor.static.name = 'visualeditor';
}( jQuery, mediaWiki, OO, ve ) );