<template>
  <div class="agora-comments-list">
    <div class="comment-wrapper" v-for="comment in comments" :key="comment.id">
      <div class="comment-parent">
        <div class="comment-parent__body">
          <div v-html="comment.html"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const { useCommentStore } = require("./../store.js");
const { defineComponent, computed, onMounted } = require( 'vue' );
module.exports = defineComponent( {
  name: "CommentList",
  setup() {
    const store = useCommentStore();

    const comments = computed( () => store.comments );

    onMounted(async () => {
      try {
        await store.fetchComments();
      } catch ( e ) {
        console.error( e );
      }
    } );

    return {
      comments
    }
  }
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
.comment-wrapper {
  margin-bottom: 18px;
  background: @background-color-neutral;
}

.comment-parent__body {
  padding: 18px;
}
</style>