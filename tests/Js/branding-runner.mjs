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

import { runBrandingCheck, runSealCheck } from '../../storage/framework/testing/render-entry.mjs'

const results = await runBrandingCheck()
const missing = results.filter((r) => ! r.found)

results.forEach((r) => console.log(`${r.found ? 'ok  ' : 'FAIL'} Logo   ${r.name}`))
console.log(`${results.length} Oberflächen geprüft, ${missing.length} ohne Logo`)

const seals = await runSealCheck()
const sealless = seals.filter((r) => ! r.found)

seals.forEach((r) => console.log(`${r.found ? 'ok  ' : 'FAIL'} Siegel ${r.name}`))
console.log(`${seals.length} Oberflächen geprüft, ${sealless.length} ohne Siegel`)

const ok = missing.length === 0 && sealless.length === 0 && results.length > 0 && seals.length > 0

process.exit(ok ? 0 : 1)
