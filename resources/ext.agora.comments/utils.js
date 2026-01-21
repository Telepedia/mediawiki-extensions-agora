/**
 * Generate an avatar from the provided src (url)
 * @param src the url of the avatar
 */
function generateAvatar( src ) {
    const avatar = document.createElement( 'div' );
    avatar.classList.add( 'agora-avatar' );

    const avatarImage = document.createElement( 'img' );
    avatarImage.classList.add( 'agora-avatar__img' );
    avatarImage.src = src;
    avatarImage.loading = "lazy";
    avatarImage.title = "User Avatar";
    avatarImage.alt = "User Avatar";

    avatar.appendChild( avatarImage );
    return avatar.outerHTML;
}

module.exports = {
    generateAvatar
}