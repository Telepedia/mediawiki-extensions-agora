<template>
	<div class="agora-comments-list">
		<single-comment
			v-for="comment in comments"
			:key="comment.id"
			:comment="comment"
		></single-comment>
	</div>
</template>

<script>
const { useCommentStore } = require("./../store.js");
const { defineComponent, computed, onMounted } = require( 'vue' );
const SingleComment = require( './SingleComment.vue' );
module.exports = defineComponent( {
  name: "CommentList",
  components: {
	SingleComment,
  },
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

  p {
    margin: 0;
  }
}

.comment__body {
  padding: 18px;
  background: @background-color-neutral;
  width: 100%;
  box-sizing: border-box;
}

.comment__body-header {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 14px;
}

.comment__body-details {
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-grow: 1;
}

.comment__body-actions:hover {
  cursor: pointer;
}

.comment__body-details .time::before {
  content: "•";
  margin: 0 8px;
  font-size: 1rem;
  line-height: 1;
}

.comment__body-interactions {
  display: flex;
  justify-content: end;

  &-reply {
    display: flex;
    align-items: center;
    gap: 5px;

    &:hover {
      cursor: pointer;
    }
  }
}

.agora-actions-list {
  margin-left: 0;
  width: max-content;

  li {
    list-style: none;
  }
}

.comment__body-actions-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;

  &:hover {
    cursor: pointer;

    .agora-comment__popover {
      display: block;
    }
  }
}

.agora-actions-list {
  margin: 0;
  padding: 0;
  width: max-content;
  min-width: 140px;

  li {
    list-style: none;
    padding: 6px 12px;
    white-space: nowrap;

    &:hover {
      background-color: @background-color-neutral-subtle;
    }
  }
}

.comment__body-deleted {
  background-color: @background-color-neutral;
}

.comment__body-deleted-wrapper {
  padding: 12px 0 11px 0;
  margin: 0 24px;
  border-bottom: 1px solid @border-color-base;
  box-sizing: border-box;
  display: flex;

  .comment__body-deleted-text {
    flex-grow: 1;
  }
}


.comment__restore-icon {
  color: green;
  opacity: 0.5;

  &:hover {
    cursor: pointer;
    opacity: 1;
  }
}
</style>
