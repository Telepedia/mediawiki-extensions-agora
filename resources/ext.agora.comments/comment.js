const { generateAvatar } = require( './utils.js' );
const moment = require( 'moment' );
class Comment {

    constructor( data ) {
        this.id = data.id;
        this.html = data.html;
        this.wikitext = data.wikitext;
        this.author = data.author;
        this.timestamp = data.timestamp;
        this.parent = data.parent || null;
        this.isDeleted = data.isDeleted;
        this.deletedActor = data.deletedActor || null;

        this.children = ( data.children || [] ).map( childData => new Comment( childData ) );
    }

    get isTopLevel() {
        return this.parent === null;
    }

    addChild ( comment ) {
        this.children.push( comment );
    }

    /**
     * Get a user avatar, properly formatted and styled
     * @returns {string}
     */
    getAvatar() {
        return generateAvatar( this.author.avatar );
    }

    /**
     * Get a link to this users user page
     */
    getLinkToUserPage() {
        const a = document.createElement( 'a' );
        // NS_USER is not defined, just assume the User namespace is 2
        a.href = new mw.Title( this.author.name, 2 ).getUrl();
        a.textContent = this.author.name;

        return a.outerHTML;
    }

    /**
     * Get the time that this comment was posted, formatted for display
     * @returns {string}
     */
    getFormattedTime() {
        return moment(this.timestamp, "YYYYMMDD").fromNow();
    }
}

module.exports = Comment;