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
        hideDeleted: false,
        comments: null,
        isEditorOpen: false,
        currentUserAvatar: null
    } ),

    actions: {
        initFromMW() {
            this.pageId = mw.config.get( 'wgArticleId' );
            this.totalComments = mw.config.get( 'wgAgora' ).commentCount;
            this.isModerator = mw.config.get('wgAgora').isMod;
            this.currentUserAvatar = mw.config.get( 'wgAgora' ).userAvatar;
            this.pageTitle = mw.config.get( 'wgPageName' );

            // @TODO: dynamically get these from stuff to be added to mw.config later
            this.canPost = false;
            this.hideDeleted = false;
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

        /**
         * Fetch the comments from the API
         * @returns {Promise<void>}
         */
        async fetchComments() {
            let url = `/comments/v0/comments/${this.pageId}`;
            if ( this.isModerator ) {
                url += `?hideDeleted=${this.hideDeleted}`;
            }

            const response = await restClient.get( url );

            this.comments = response.comments.map(
                data => new Comment( data )
            );

            this.commentCount = response.comments.length;
        }
    }
} );

module.exports = {
    useCommentStore
}
