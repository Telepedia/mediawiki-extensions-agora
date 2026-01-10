<template>
  <div id="agora-input-box">
    <div v-html="avatar"></div>
    <div class="agora-input-body">
      <div v-if="store.isEditorOpen" class="agora-editor-container">
        <textarea ref="inputContainer" rows="5" class="ve-mount-point"></textarea>
        <div class="agora-editor-actions">
          <button @click="cancelEditor">Cancel</button>
          <button @click="saveComment">Post</button>
        </div>
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
  <h3>{{ $i18n( 'agora-comments-header', totalCount ).text() }}</h3>
</template>

<script>
const { defineComponent, ref, nextTick } = require( 'vue' );
const { useCommentStore } = require( './store.js' );
module.exports = defineComponent( {
  name: 'Agora',
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

    const cancelEditor = () => {
      console.log('Cancel editor');
    }

    const saveComment = () => {
      console.log('Save comment');
    }

    let inputContainer = ref( null );

    const activateEditor = async () => {
      store.toggleEditorOpen();
      await nextTick();

      try {
        editorInstance = new mw.agora.ve.Editor(
            inputContainer.value,
            inputContainer.value.value
        );
      } catch ( error ) {
        console.error( 'Failed to initialize editor:', error );
      }
    };

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
  flex-direction: row;
  align-items: center;
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

.agora-input-box-placeholder {
  font-size: 16px;
  line-height: 1;

  &:hover {
    cursor: pointer;
  }
}
</style>