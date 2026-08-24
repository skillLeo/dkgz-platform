/**
 * Google Analytics, loaded only once somebody has agreed to it.
 *
 * The tag Google hands out goes straight into <head> and starts sending before
 * the visitor has been asked anything. In Germany that is the thing the consent
 * banner exists to prevent: under the TDDDG and the GDPR, analytics may not run
 * until consent is given. So the same tag is here instead, and nothing is
 * requested from Google until the banner has been answered with yes — on this
 * visit or a previous one.
 *
 * Declining is remembered too. A visitor who said no is not asked again on the
 * next page, and nothing loads.
 */
const CONSENT_KEY = 'dkgz-cookie-consent'

let loaded = false

/** What the visitor decided last time, if anything. */
function storedConsent () {
    try {
        return window.localStorage.getItem(CONSENT_KEY)
    } catch {
        // Storage blocked. Treat it as undecided: the banner will ask again,
        // and nothing loads until it is answered.
        return null
    }
}

/**
 * Injects gtag.js once and configures every property given to it.
 *
 * Analytics and Ads are both gtag properties, so they share one script tag and
 * one dataLayer — loading gtag.js twice would double every event. The script is
 * requested with the first id; the rest are configured on top.
 *
 * @param {string[]} ids
 */
function load (ids) {
    if (loaded || ids.length === 0) return

    loaded = true

    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ids[0])}`
    document.head.appendChild(script)

    window.dataLayer = window.dataLayer || []
    function gtag () { window.dataLayer.push(arguments) }
    window.gtag = gtag

    gtag('js', new Date())

    for (const id of ids) {
        // IP anonymisation is the default in GA4 and cannot be turned off, but
        // saying so explicitly documents the intent for anyone reading later.
        gtag('config', id, { anonymize_ip: true })
    }
}

/**
 * @param {Array<string|null|undefined>} measurementIds  The properties to
 *   report to. Empty when the operator has configured none, in which case
 *   nothing ever loads and the banner never appears.
 */
export function startAnalytics (measurementIds) {
    const ids = (Array.isArray(measurementIds) ? measurementIds : [measurementIds])
        .filter(Boolean)

    if (ids.length === 0) return

    if (storedConsent() === 'accepted') {
        load(ids)

        return
    }

    // Otherwise wait for the banner. The event fires only on acceptance.
    window.addEventListener(
        'dkgz:analytics-consent',
        () => load(ids),
        { once: true },
    )
}
