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
 */

/**
 * How each article changes with the gender of the noun after it.
 *
 * The keys are the masculine forms, because masculine is the one gender where
 * all four cases look different — so writing the article in the masculine says
 * unambiguously which case the sentence is in, and the feminine and neuter
 * forms follow from it. That is the whole rule an operator has to know: put the
 * article inside the braces and write it as if the service were "der Gutachter".
 *
 * @type {Record<string, [string, string, string]>} masculine → [m, f, n]
 */
const ARTICLES = {
    // Definite.
    der: ['der', 'die', 'das'],
    den: ['den', 'die', 'das'],
    dem: ['dem', 'der', 'dem'],
    des: ['des', 'der', 'des'],

    // Indefinite, and its negation.
    ein: ['ein', 'eine', 'ein'],
    einen: ['einen', 'eine', 'ein'],
    einem: ['einem', 'einer', 'einem'],
    eines: ['eines', 'einer', 'eines'],
    kein: ['kein', 'keine', 'kein'],
    keinen: ['keinen', 'keine', 'kein'],
    keinem: ['keinem', 'keiner', 'keinem'],

    // Possessive — always the polite "Ihr" in this copy.
    ihr: ['Ihr', 'Ihre', 'Ihr'],
    ihren: ['Ihren', 'Ihre', 'Ihr'],
    ihrem: ['Ihrem', 'Ihrer', 'Ihrem'],
    ihres: ['Ihres', 'Ihrer', 'Ihres'],

    // Prepositions that swallow the article. These are the ones that bite
    // most often, because "zum Gutachten" and "zur Beweissicherung" sit in
    // the same sentence on two different pages.
    zum: ['zum', 'zur', 'zum'],
    beim: ['beim', 'bei der', 'beim'],
    vom: ['vom', 'von der', 'vom'],
    im: ['im', 'in der', 'im'],
    ins: ['ins', 'in die', 'ins'],
    fuers: ['für den', 'für die', 'für das'],
    aufs: ['auf den', 'auf die', 'auf das'],
}

const COLUMN = { m: 0, f: 1, n: 2 }

/** Keeps the operator's capitalisation, so an article can open a sentence. */
function matchCase (written, inflected) {
    if (written[0] !== written[0].toUpperCase()) return inflected

    return inflected[0].toUpperCase() + inflected.slice(1)
}

/**
 * The article in front of a value, bent to that value's gender.
 *
 * Returns null when the word is not an article we know, which leaves the
 * placeholder standing rather than guessing.
 */
function article (written, gender) {
    const forms = ARTICLES[written.toLowerCase()]

    if (! forms) return null

    return matchCase(written, forms[COLUMN[gender] ?? COLUMN.n])
}

/**
 * @param {string|null|undefined} text
 * @param {Record<string, string|null|undefined>} values
 *   Alongside each value, `<key>_genus` may carry 'm', 'f' or 'n' — the gender
 *   the articles in front of that value are bent to.
 */
export function fill (text, values = {}) {
    if (! text) return ''

    // Either "{leistung}" or "{dem leistung}" — the article, when there is
    // one, comes along inside the braces so it can be bent with the noun.
    return String(text).replace(/\{(?:([\p{L}]+)\s+)?([a-z_]+)\}/giu, (whole, written, key) => {
        const value = values[key.toLowerCase()]

        if (value == null || value === '') return whole
        if (! written) return String(value)

        const bent = article(written, values[`${key.toLowerCase()}_genus`])

        return bent === null ? whole : `${bent} ${value}`
    })
}
