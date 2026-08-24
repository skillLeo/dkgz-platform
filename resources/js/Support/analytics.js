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

/** Injects gtag.js and configures the property. Runs at most once. */
function load (measurementId) {
    if (loaded || ! measurementId) return

    loaded = true

    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`
    document.head.appendChild(script)

    window.dataLayer = window.dataLayer || []
    function gtag () { window.dataLayer.push(arguments) }
    window.gtag = gtag

    gtag('js', new Date())
    // IP anonymisation is the default in GA4 and cannot be turned off, but
    // saying so explicitly documents the intent for anyone reading this later.
    gtag('config', measurementId, { anonymize_ip: true })
}

/**
 * @param {string|null} measurementId  The property to report to, or null when
 *   the operator has not configured one — in which case nothing ever loads and
 *   the banner never appears.
 */
export function startAnalytics (measurementId) {
    if (! measurementId) return

    if (storedConsent() === 'accepted') {
        load(measurementId)

        return
    }

    // Otherwise wait for the banner. The event fires only on acceptance.
    window.addEventListener(
        'dkgz:analytics-consent',
        () => load(measurementId),
        { once: true },
    )
}
