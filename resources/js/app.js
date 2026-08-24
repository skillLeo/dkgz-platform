import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { startAnalytics } from './Support/analytics.js';

const appName = import.meta.env.VITE_APP_NAME || 'DKGZ Deutsche KFZ-Gutachterzentrale';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .mount(el);

        // Nothing is requested from Google until the banner has been answered
        // with yes, here or on an earlier visit.
        startAnalytics(props.initialPage.props.app?.analytics_id ?? null);
    },

    progress: {
        // navy-700. The only progress affordance in the product.
        color: '#14294A',
        showSpinner: false,
    },
});
