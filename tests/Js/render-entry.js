/**
 * SSR entry for the page-render smoke test. Built by Vite so single-file
 * components resolve exactly as they do in the real bundle.
 */
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'

const modules = import.meta.glob('../../resources/js/Pages/**/*.vue')

/**
 * A value that satisfies whatever a template asks of it. Collections yield
 * exactly one element, so row templates render rather than only empty states.
 */
const anything = (depth = 0) => {
    if (depth > 5) return 'Text'

    return new Proxy(function fallback () {}, {
        get (target, key) {
            if (key === Symbol.toPrimitive) return () => 'Text'
            if (key === Symbol.iterator) return function * () { yield anything(depth + 1) }
            if (key === 'then' || key === 'constructor' || key === 'nodeType') return undefined
            if (typeof key === 'symbol') return undefined
            if (key.startsWith('__v_')) return false
            if (key === 'length') return 1
            if (key === 'toJSON') return () => ({})
            if (key === 'toString') return () => 'Text'
            if (key === 'map') return (fn) => [fn(anything(depth + 1), 0)]
            if (['filter', 'slice', 'concat', 'sort', 'reverse', 'flat'].includes(key)) {
                return () => [anything(depth + 1)]
            }
            if (key === 'flatMap') return (fn) => [fn(anything(depth + 1), 0)].flat()
            if (key === 'forEach') return (fn) => { fn(anything(depth + 1), 0) }
            if (key === 'find') return () => anything(depth + 1)
            if (['some', 'every', 'includes'].includes(key)) return () => true
            if (key === 'join') return () => 'Text'
            if (key === 'replace') return () => 'Text'
            if (key === 'split') return () => ['Text']

            return anything(depth + 1)
        },
        apply: () => anything(depth + 1),
        has: () => true,
    })
}

/** One value per declared prop, typed the way the component says it wants it. */
const propsFor = (component) => {
    const declared = component?.props ?? {}
    const names = Array.isArray(declared) ? declared : Object.keys(declared)
    const result = {}

    for (const name of names) {
        const spec = Array.isArray(declared) ? {} : (declared[name] ?? {})
        const types = [].concat(spec.type ?? [])
        const primary = types.find((type) => type && type !== null)

        if (primary === Boolean) result[name] = types.includes(String) ? 'Text' : true
        else if (primary === Number) result[name] = 1
        else if (primary === String) result[name] = 'Text'
        else if (primary === Function) result[name] = () => 'Text'
        else if (primary === Array) result[name] = [anything()]
        else result[name] = anything()
    }

    return result
}

const shared = {
    auth: {
        user: {
            name: 'Testende Person',
            first_name: 'Testende',
            last_name: 'Person',
            email: 'test@example.test',
            permissions: [],
            roles: [],
            is_assessor: true,
            assessor: { id: 1, is_available: true, partner_id: 'DKGZ-SV-0001', approval_status: 'approved' },
        },
    },
    app: { phone: '+49 179 4480169', office_hours: 'Mo–Fr 08:00–18:00', name: 'DKGZ' },
    flash: {},
    errors: {},
    filters: {},
    content: {},
}

export async function run () {
    const failures = []
    const names = Object.keys(modules).sort()

    for (const path of names) {
        const name = path.replace(/^.*\/Pages\//, '').replace(/\.vue$/, '')

        try {
            const mod = await modules[path]()
            const app = createSSRApp({ render: () => h(mod.default, propsFor(mod.default)) })

            app.config.warnHandler = () => {}
            app.config.globalProperties.$page = { props: shared, url: '/', component: name }
            app.config.globalProperties.$inertia = { post () {}, get () {}, visit () {}, delete () {} }

            await renderToString(app)
        } catch (error) {
            failures.push(`${name}: ${String(error?.message ?? error).split('\n')[0]}`)
        }
    }

    return { total: names.length, failures }
}
