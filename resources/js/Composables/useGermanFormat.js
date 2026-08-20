/**
 * German formatting for the client.
 *
 * Must stay byte-identical to App\Support\Formatter on the server — the two are
 * covered by the same fixture table in tests/Unit/FormatterTest.php and
 * tests/Feature/FormatParityTest.php.
 */
export function useGermanFormat() {
    /** 127550 → "1.275,50 €" */
    const money = (cents, withSymbol = true) => {
        const value = Number.isFinite(cents) ? cents : 0
        const sign = value < 0 ? '-' : ''
        const abs = Math.abs(value)

        const formatted = (abs / 100)
            .toFixed(2)
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.')

        return withSymbol ? `${sign}${formatted} €` : `${sign}${formatted}`
    }

    /** 127550 → "1.275,50" — for input fields */
    const amount = (cents) => money(cents, false)

    /** "1.275,50" | "1275,50" | "1275.50" → 127550 */
    const parseMoney = (input) => {
        if (input === null || input === undefined || input === '') return null
        if (typeof input === 'number') return Math.round(input * 100)

        let raw = String(input).trim().replace(/[€\s  ]/g, '')

        // German notation: dot groups thousands, comma separates decimals.
        if (raw.includes(',')) {
            raw = raw.replace(/\./g, '').replace(',', '.')
        }

        const parsed = Number(raw)

        return Number.isFinite(parsed) ? Math.round(parsed * 100) : null
    }

    const pad = (n) => String(n).padStart(2, '0')

    const toDate = (value) => {
        if (!value) return null
        const date = value instanceof Date ? value : new Date(value)

        return Number.isNaN(date.getTime()) ? null : date
    }

    /** → "17.08.2026" */
    const date = (value) => {
        const d = toDate(value)
        if (!d) return ''

        return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`
    }

    /** → "17.08.2026, 14:32 Uhr" */
    const dateTime = (value) => {
        const d = toDate(value)
        if (!d) return ''

        return `${date(d)}, ${pad(d.getHours())}:${pad(d.getMinutes())} Uhr`
    }

    /**
     * → "vor 12 Min." / "vor 3 Std." / "vor 2 Tagen"
     * Abbreviated the way the design writes it, and capped at days: anything
     * older is shown as a date, because "vor 5 Wochen" tells a partner nothing
     * they can act on.
     */
    const relativeTime = (value, now = new Date()) => {
        const d = toDate(value)
        if (!d) return ''

        const seconds = Math.floor((now.getTime() - d.getTime()) / 1000)
        if (seconds < 60) return 'gerade eben'

        const minutes = Math.floor(seconds / 60)
        if (minutes < 60) return `vor ${minutes} Min.`

        const hours = Math.floor(minutes / 60)
        if (hours < 24) return `vor ${hours} Std.`

        const days = Math.floor(hours / 24)
        if (days <= 7) return days === 1 ? 'vor 1 Tag' : `vor ${days} Tagen`

        return date(d)
    }

    /** → "17.08. · 08:42" — the compact stamp on a timeline step. */
    const stamp = (value) => {
        const d = toDate(value)
        if (!d) return ''

        return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}. · ${pad(d.getHours())}:${pad(d.getMinutes())}`
    }

    /** → "heute 18:00" / "18.08. 12:00" — the acceptance deadline in a list. */
    const deadline = (value, now = new Date()) => {
        const d = toDate(value)
        if (!d) return ''

        const clock = `${pad(d.getHours())}:${pad(d.getMinutes())}`
        const sameDay = (a, b) => a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate()

        if (sameDay(d, now)) return `heute ${clock}`

        const tomorrow = new Date(now.getTime())
        tomorrow.setDate(tomorrow.getDate() + 1)
        if (sameDay(d, tomorrow)) return `morgen ${clock}`

        return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}. ${clock}`
    }

    /** → "14:32 Uhr" */
    const time = (value) => {
        const d = toDate(value)
        if (!d) return ''

        return `${pad(d.getHours())}:${pad(d.getMinutes())} Uhr`
    }

    /** "01794480169" → "+49 179 4480169" */
    const phone = (value) => {
        if (!value) return ''

        const digits = String(value).replace(/[^0-9+]/g, '')
        let national

        if (digits.startsWith('+49')) national = digits.slice(3)
        else if (digits.startsWith('0049')) national = digits.slice(4)
        else if (digits.startsWith('0')) national = digits.slice(1)
        else return String(value)

        if (!national) return String(value)

        // Metropolitan two-digit area codes keep their real length; everything
        // else groups after three, which is how the design renders it.
        const twoDigit = ['30', '40', '69', '89']
        const prefixLength = twoDigit.includes(national.slice(0, 2)) ? 2 : 3

        if (national.length <= prefixLength) return `+49 ${national}`

        return `+49 ${national.slice(0, prefixLength)} ${national.slice(prefixLength)}`.trim()
    }

    /** 15 → "15 %" · 12.5 → "12,5 %" */
    const percent = (value) => {
        if (value === null || value === undefined || value === '') return ''

        const number = Number(value)
        if (!Number.isFinite(number)) return ''

        const formatted = number % 1 === 0
            ? String(number)
            : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '').replace('.', ',')

        return `${formatted} %`
    }

    /** 2411724 → "2,4 MB" — decimal units, matching the design */
    const fileSize = (bytes) => {
        if (bytes === null || bytes === undefined) return ''
        if (bytes < 1000) return `${bytes} B`

        const units = ['KB', 'MB', 'GB']
        let value = bytes / 1000
        let unit = 0

        while (value >= 1000 && unit < units.length - 1) {
            value /= 1000
            unit += 1
        }

        const decimals = value >= 100 ? 0 : 1
        let formatted = value.toFixed(decimals).replace('.', ',')

        if (decimals === 1 && formatted.endsWith(',0')) {
            formatted = formatted.slice(0, -2)
        }

        return `${formatted} ${units[unit]}`
    }

    /** "DKGZ-2026-04817" stays as-is; used to mark reference numbers as mono. */
    const reference = (value) => (value ? String(value) : '')

    return {
        money, amount, parseMoney, date, dateTime, time, relativeTime, deadline, stamp,
        phone, percent, fileSize, reference,
    }
}
