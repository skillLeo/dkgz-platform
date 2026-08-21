globalThis.window ??= {
    addEventListener () {}, removeEventListener () {}, matchMedia: () => ({ matches: false, addEventListener () {}, removeEventListener () {} }),
    location: { href: 'http://localhost/', pathname: '/', search: '' },
    localStorage: { getItem: () => null, setItem () {}, removeItem () {} },
    scrollTo () {}, setTimeout, clearTimeout,
}
globalThis.document ??= {
    addEventListener () {}, removeEventListener () {}, documentElement: { style: { setProperty () {} }, classList: { add () {}, remove () {} } },
    body: { classList: { add () {}, remove () {} }, style: {} },
    createElement: () => ({ style: {}, setAttribute () {}, appendChild () {} }),
    querySelector: () => null, querySelectorAll: () => [],
}
globalThis.navigator ??= { userAgent: 'node', language: 'de-DE' }

import { runBrandingCheck } from '../../storage/framework/testing/render-entry.mjs'

const results = await runBrandingCheck()
const missing = results.filter((r) => ! r.found)

results.forEach((r) => console.log(`${r.found ? 'ok  ' : 'FAIL'} ${r.name}`))
console.log(`${results.length} Oberflächen geprüft, ${missing.length} ohne Logo`)

process.exit(missing.length === 0 && results.length > 0 ? 0 : 1)
