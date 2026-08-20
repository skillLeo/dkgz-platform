/**
 * Stands in for @inertiajs/vue3 while smoke-rendering pages. The real package
 * needs a live Inertia app context that only exists in the browser; the pages
 * only need usePage(), useForm(), Link and Head to behave sensibly.
 */
import { h, reactive } from 'vue'

export const page = reactive({
    props: {
        auth: {
            user: {
                name: 'Testende Person',
                first_name: 'Testende',
                last_name: 'Person',
                email: 'test@example.test',
                phone: '+49 211 4470012',
                permissions: [],
                roles: [],
                is_assessor: true,
                assessor: {
                    id: 1,
                    is_available: true,
                    partner_id: 'DKGZ-SV-0001',
                    approval_status: 'approved',
                    company_name: 'Testbüro',
                },
            },
        },
        app: { phone: '+49 179 4480169', office_hours: 'Mo–Fr 08:00–18:00', name: 'DKGZ' },
        flash: {},
        errors: {},
        filters: {},
        content: {},
    },
    url: '/',
    component: 'Test',
})

export const usePage = () => page

export const router = {
    post () {}, get () {}, put () {}, patch () {}, delete () {}, visit () {}, reload () {},
    on: () => () => {},
}

export const useForm = (initial = {}) => {
    const form = reactive({
        ...(typeof initial === 'function' ? initial() : initial),
        errors: {},
        hasErrors: false,
        processing: false,
        progress: null,
        recentlySuccessful: false,
        wasSuccessful: false,
        post () { return this }, get () { return this }, put () { return this },
        patch () { return this }, delete () { return this },
        reset () { return this }, clearErrors () { return this },
        setError () { return this }, transform () { return this }, cancel () {},
        defaults () { return this },
    })

    return form
}

export const Link = {
    name: 'Link',
    props: { href: { type: String, default: '#' }, as: { type: String, default: 'a' } },
    setup (props, { slots }) {
        return () => h(props.as === 'button' ? 'button' : 'a', { href: props.href }, slots.default?.())
    },
}

export const Head = { name: 'Head', setup: () => () => null }

export const Deferred = { name: 'Deferred', setup: (props, { slots }) => () => slots.default?.() }
export const WhenVisible = { name: 'WhenVisible', setup: (props, { slots }) => () => slots.default?.() }
export const createInertiaApp = () => {}
export const usePoll = () => ({ start () {}, stop () {} })
export const useRemember = (value) => value
