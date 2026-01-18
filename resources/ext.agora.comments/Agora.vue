<template>
  <div id="agora-input-box">
    <div class="agora-input-row" :class="{ 'agora-input-row--editor-open': store.isEditorOpen }">
      <div v-html="avatar"></div>
      <div class="agora-input-body">
        <div v-if="store.isEditorOpen" class="agora-editor-container">
          <div ref="inputContainer" class="ve-mount-point"></div>
        </div>
        <div
            v-else
            class="agora-input-box-placeholder"
            @click="activateEditor"
        >
          {{ $i18n( 'agora-comments-input-placeholder', store.pageTitle ).text() }}
        </div>
      </div>
    </div>
    <div v-if="store.isEditorOpen" class="agora-editor-actions">
      <cdx-button weight="quiet" @click="cancelEditor">Cancel</cdx-button>
      <cdx-button action="progressive" weight="primary" @click="saveComment">Post</cdx-button>
    </div>
  </div>
  <h3>{{ $i18n( 'agora-comments-header', totalCount ).text() }}</h3>
  <comment-list></comment-list>
</template>

<script>
const restClient = require( 'telepedia.fetch' );
const { defineComponent, ref, nextTick, onMounted } = require( 'vue' );
const { useCommentStore } = require( './store.js' );
const { CdxButton } = require( './../codex.js' );
const CommentList = require( './components/CommentList.vue' );
module.exports = defineComponent( {
  name: 'Agora',
  components: {
    CdxButton,
    CommentList
  },
  setup() {
    console.log("Agora initialised....");

    // initialise the store
    const store = useCommentStore();
    store.initFromMW();

    const avatar = document.createElement( 'div' );
    avatar.classList.add( 'agora-avatar' );

    const avatarImage = document.createElement( 'img' );
    avatarImage.classList.add( 'agora-avatar__img' );
    avatarImage.src = store.currentUserAvatar;
    avatarImage.loading = "lazy";
    avatarImage.title = "User Avatar";
    avatarImage.alt = "User Avatar";

    avatar.appendChild( avatarImage );
    const avatarHtml = avatar.outerHTML;

    let inputContainer = ref( null );
    let editorInstance = null;

    const activateEditor = async () => {
      store.toggleEditorOpen();
      await nextTick();

      try {
        const target = new mw.agora.ve.Target();
        target.$element = $( inputContainer.value );
        target.loadContent( '' );
        // store a reference here to the target so we can access it later
        editorInstance = target;
      } catch ( error ) {
        console.error( 'Failed to initialize editor:', error );
      }
    };

    const cancelEditor = () => {
      if ( editorInstance ) {
        // destroy the editor instance and do some cleanup
        editorInstance.destroy();

        // no longer need a local reference to the editor; will be re-initialised when the editor is opened again
        editorInstance = null;
      }
      store.isEditorOpen = false;
    }

    /**
     * Save the comment to the backend. Here we only send the wikitext to the API as the source of truth. The backend
     * will parse using Parsoid, save the HTML to the database, and then return both the HTML and the Wikitext for
     * addition to the frontend. This also has the benefit that Parsoid will sanitise the input
     * @returns {Promise<void>}
     */
    const saveComment = async () => {
      const surface = editorInstance.getSurface();
      const doc = surface.getModel().getDocument();
      const wikitext = await editorInstance.getWikitextFragment( doc );

      if ( wikitext.length === 0 ) {
        mw.notify( mw.message("agora-error-empty-comment"), { type: "error" } );
        return;
      }

      const res = await restClient.post( '/comments/v0/comment', {
        articleId: mw.config.get( 'wgRelevantArticleId' ),
        wikitext: wikitext,
        token: mw.user.tokens.get( 'csrfToken' )
      } );
    }

    return {
      totalCount: store.totalComments,
      store: store,
      avatar: avatarHtml,
      activateEditor,
      saveComment,
      cancelEditor,
      inputContainer
    }
  }
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
#agora-container {
  margin-top: 15px;
}

#agora-input-box {
  background: @background-color-base;
  border: 1px solid @border-color-subtle;
  border-radius: 3px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.agora-input-row {
  display: flex;
  flex-direction: row;
  align-items: center;

  &.agora-input-row--editor-open {
    align-items: flex-start;
  }
}

.agora-avatar {
  color: inherit;
  height: 30px;
  margin-right: 12px;
  min-width: 30px;
  width: 30px;
}

.agora-avatar__img {
  border-radius: 50%;
  border: 2px solid @border-color-base;
  box-sizing: border-box;
  display: inline-block;
  fill: currentcolor;
  height: 100%;
  min-width: 100%;
  object-fit: contain;
  width: 100%;
}

.agora-input-body {
  flex: 1;
}

.agora-input-box-placeholder {
  font-size: 16px;
  line-height: 1;

  &:hover {
    cursor: pointer;
  }
}

.agora-editor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.agora-input-row.agora-input-row--editor-open {
    padding-bottom: 18px;
    border-bottom: 1px solid @border-color-base;
}

.agora-editor-container {
  p {
    font-size: 16px;
  }

  .oo-ui-toolbar-bar {
    width: fit-content;
    box-shadow: none;
    border-radius: 3px;
    border: 1px solid @border-color-muted;
  }
}



.ve-mount-point {
  min-height: 8em;
}

</style>