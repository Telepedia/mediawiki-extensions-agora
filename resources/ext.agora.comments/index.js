const Vue = require( 'vue' ),
    Agora = require( './Agora.vue' ),
    Pinia = require( 'pinia' );

const store = Pinia.createPinia();

/**
 * Add the container that Vue will bind to here; fu jQuery its 2026
 */
function setupAgora() {
    const mwBody = document.getElementById( 'bodyContent' );
    const agoraContainer = document.createElement( 'div' );
    agoraContainer.id = 'agora-container';
    mwBody.appendChild( agoraContainer );

    Vue.createMwApp( Agora )
        .use( store )
        .mount( agoraContainer );
}

mw.agora = mw.agora || {};
mw.agora.ve = mw.agora.ve || {};

( function () {
    // Run our code to manipulate the DOM after the wikipage is ready and the hook is fired
    mw.hook( 'wikipage.content' ).add( setupAgora );
} )();
