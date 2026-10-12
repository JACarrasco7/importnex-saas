/**
 * Resuelve la URL publica de una foto de coche.
 * Acepta tanto un string (path o URL absoluta) como un objeto
 * CarPhoto ({url, photo_type, ...}). Si no hay URL valida, devuelve null.
 *
 * @param {string|object|null|undefined} photo
 * @returns {string|null}
 */
export function photoUrl(photo) {
    if (!photo) return null;
    const raw = typeof photo === 'string' ? photo : photo.url;
    if (!raw) return null;
    if (raw.startsWith('http')) return raw;
    return `/storage/${raw}`;
}
