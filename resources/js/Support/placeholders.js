/**
 * Fills {stadt} and {leistung} into editable copy.
 *
 * The city pages have to say "Gutachter in Düsseldorf anfragen" rather than
 * "Gutachter anfragen" — both because it reads as though the page is about that
 * city and because a search engine is looking for exactly those words. Writing
 * the wording twice, once dynamic in the template and once editable in the
 * admin panel, would guarantee the two drift apart. So the editable text keeps
 * the placeholders and they are filled in here.
 *
 * Unknown placeholders are left alone rather than blanked: an operator who
 * types {ort} should see their mistake, not a sentence with a hole in it.
 *
 * @param {string|null|undefined} text
 * @param {Record<string, string|null|undefined>} values
 */
export function fill (text, values = {}) {
    if (! text) return ''

    return String(text).replace(/\{([a-z_]+)\}/gi, (whole, key) => {
        const value = values[key.toLowerCase()]

        return value == null || value === '' ? whole : String(value)
    })
}
