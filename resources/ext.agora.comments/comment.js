class Comment {

    constructor( data ) {
        this.id = data.id;
        this.html = data.html;
        this.wikitext = data.wikitext;
        this.actor = data.actor;
        this.created = data.created;
        this.parent = data.parent || null;
        this.deletedActor = data.deletedActor || null;

        this.children = ( data.children || [] ).map( childData => new Comment( childData ) );
    }

    get isDeleted() {
        return this.deletedActor !== null;
    }

    get isTopLevel() {
        return this.parent === null;
    }

    addChild ( comment ) {
        this.children.push( comment );
    }
}

module.exports = Comment;