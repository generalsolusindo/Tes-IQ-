// Prefer the image stored on the question/option (what admins manage on the
// review page); fall back to the bundled static visual only when none is set.
export function preferImage(image, fallback) {
    return image?.url ? image : fallback;
}
