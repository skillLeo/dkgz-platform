// A few components reach for browser globals at module scope; give them the
// smallest possible stand-ins so the render itself is what gets tested.
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

import { run } from '../../storage/framework/testing/render-entry.mjs'

const { total, failures } = await run()

console.log(`${total} Seiten gerendert, ${failures.length} fehlgeschlagen`)
failures.forEach((failure) => console.log(`  FAIL ${failure}`))
process.exit(failures.length === 0 ? 0 : 1)
