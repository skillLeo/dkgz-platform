import {
    Camera, Car, ClipboardCheck, Clock, Euro, FileSearch, FileText, Gauge,
    Hammer, History, Landmark, Scale, Search, Shield, ShieldCheck, Sparkles,
    Truck, Wrench,
} from 'lucide-vue-next'

/**
 * The icons a service may be given, by the name stored on it.
 *
 * The map used to be keyed by slug and written into the homepage, which broke
 * the moment a service was renamed: the slug follows the name, so the icon
 * silently fell back to a generic mark and nobody would notice until they
 * looked. Keying it by the `icon` column instead means the choice survives a
 * rename, and putting the list here means the homepage and the services page
 * cannot disagree about which icon a service has.
 *
 * The German labels are what the admin panel shows when picking one.
 */
export const SERVICE_ICONS = {
    'file-text': { component: FileText, label: 'Dokument' },
    'car': { component: Car, label: 'Fahrzeug' },
    'shield-check': { component: ShieldCheck, label: 'Haftpflicht' },
    'shield': { component: Shield, label: 'Kasko' },
    'wrench': { component: Wrench, label: 'Reparatur' },
    'hammer': { component: Hammer, label: 'Werkstatt' },
    'euro': { component: Euro, label: 'Wert' },
    'history': { component: History, label: 'Oldtimer' },
    'clipboard-check': { component: ClipboardCheck, label: 'Prüfung' },
    'camera': { component: Camera, label: 'Beweissicherung' },
    'scale': { component: Scale, label: 'Recht' },
    'search': { component: Search, label: 'Untersuchung' },
    'file-search': { component: FileSearch, label: 'Begutachtung' },
    'gauge': { component: Gauge, label: 'Technik' },
    'clock': { component: Clock, label: 'Kurzgutachten' },
    'truck': { component: Truck, label: 'Nutzfahrzeug' },
    'landmark': { component: Landmark, label: 'Versicherung' },
    'sparkles': { component: Sparkles, label: 'Aufbereitung' },
}

/** Every icon, for the picker in the admin panel. */
export const ICON_CHOICES = Object.entries(SERVICE_ICONS)
    .map(([value, { label }]) => ({ value, label }))

/**
 * The component for a service, falling back to the generic document mark so a
 * service with no icon, or one naming an icon that has since been removed,
 * still draws something.
 */
export function iconFor (service) {
    const name = typeof service === 'string' ? service : service?.icon

    return SERVICE_ICONS[name]?.component ?? FileText
}
