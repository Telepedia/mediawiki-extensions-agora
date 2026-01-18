const { defineStore } = require( 'pinia' );
const restClient = require( 'telepedia.fetch' );
const Comment = require( './comment.js' );

const useCommentStore = defineStore( 'comments', {
    state: () => ( {
        pageId: null,
        pageTitle: null,
        totalComments: null,
        canPost: false,
        isModerator: false,
        sortMode: 'newest',
        showDeleted: false,
        comments: null,
        isEditorOpen: false,
        currentUserAvatar: null
    } ),

    actions: {
        initFromMW() {
            this.pageId = mw.config.get( 'wgArticleId' );
            this.totalComments = mw.config.get( 'wgAgora' ).commentCount;

            // @TODO: dynamically get these from stuff to be added to mw.config later
            this.canPost = false;
            this.isModerator = false;
            this.showDeleted = false;
            this.currentUserAvatar = mw.config.get( 'wgAgora' ).userAvatar;
            this.pageTitle = mw.config.get( 'wgPageName' );
        },

        setSortMode( mode ) {
            this.sortMode = mode;
        },

        toggleShowDeleted() {
            this.showDeleted = !this.showDeleted;
        },

        toggleEditorOpen() {
            this.isEditorOpen = !this.isEditorOpen;
        },

        async fetchComments() {
            const response  =  await restClient.get( `/comments/v0/comments/${this.pageId}`);
            const comments = [];

            for ( const data of response ) {
                comments.push( new Comment( data ) );
            }

            this.comments = comments;
        }
    }
} );

module.exports = {
    useCommentStore
}
